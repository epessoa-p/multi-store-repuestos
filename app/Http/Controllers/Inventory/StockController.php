<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\InventoryMovement;
use App\Models\Motos\MotoModel;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockController extends Controller
{
    /** Listado editable de inventario (tabs de almacén + filtro categorías) */
    public function index(Request $request)
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $warehouses = Warehouse::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get();

        // Tab activo: "all" (consolidado, por defecto) o un id de almacén
        $activeWarehouse = $request->get('warehouse', 'all');
        $isAll = $activeWarehouse === 'all' || !$warehouses->firstWhere('id', (int) $activeWarehouse);
        $whId  = $isAll ? null : (int) $activeWarehouse;

        $products = Product::with(['category', 'brand', 'photos'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // Mapa de stock por almacén (1 sola consulta) o total
        $stockMap = $whId ? $this->warehouseStockMap($cid, $whId) : null;

        $categories = ProductCategory::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $brands = ProductBrand::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('inventory.stock.index', compact(
            'products', 'warehouses', 'categories', 'brands', 'activeWarehouse', 'isAll', 'whId', 'stockMap'
        ));
    }

    /** Actualiza costo o precio (AJAX) */
    public function updateField(Product $product, Request $request)
    {
        $this->authorizeProduct($product);

        $validated = $request->validate([
            'field' => 'required|in:cost,price',
            'value' => 'required|numeric|min:0',
        ]);

        $product->update([$validated['field'] => $validated['value']]);

        $cost  = (float) $product->cost;
        $price = (float) $product->price;
        $margin = $cost > 0 ? round((($price - $cost) / $cost) * 100) : 0;

        return response()->json([
            'ok'     => true,
            'cost'   => $cost,
            'price'  => $price,
            'margin' => $margin,
        ]);
    }

    /** Fija el stock de un almacén (AJAX) creando el movimiento de ajuste */
    public function setQuantity(Product $product, Request $request)
    {
        $this->authorizeProduct($product);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity'     => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($product, $validated) {
                $this->setWarehouseStock($product, (int) $validated['warehouse_id'], (float) $validated['quantity']);
            });

            $product->refresh();
            return response()->json([
                'ok'              => true,
                'warehouse_stock' => $product->stockInWarehouse((int) $validated['warehouse_id']),
                'current_stock'   => (float) $product->current_stock,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al ajustar stock', ['product' => $product->id, 'msg' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'Error al ajustar el stock.'], 500);
        }
    }

    /** Descarga la plantilla Excel de inventario */
    public function template()
    {
        $headers = ['Nombre producto', 'Categoría', 'Marca', 'Modelo(s)', 'Notas', 'Costo', 'Precio', 'Cantidad'];
        $example = ['(10) Carburador TRUENO', 'Carburacion y aire(999)', 'FULLER', 'CG150, CG200', 'Repuesto original', '104', '140', '5'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($example, null, 'A2');

        // Estilo cabecera
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'plantilla_inventario.xlsx';
        $tmp = storage_path('app/' . $fileName);
        (new Xlsx($spreadsheet))->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }

    /** Vista de migración de inventario */
    public function import()
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;
        $warehouses = Warehouse::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get();

        return view('inventory.stock.import', compact('warehouses'));
    }

    /** Procesa la migración de inventario desde Excel */
    public function processImport(Request $request)
    {
        $request->validate([
            'file'         => 'required|file|mimes:xlsx,xls|max:5120',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $user      = auth()->user();
        $companyId = $user->is_super_admin ? $request->company_id : $user->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $warehouse = Warehouse::findOrFail($request->warehouse_id);
        if (!$user->is_super_admin && $warehouse->company_id !== $companyId) {
            abort(403);
        }

        try {
            $path     = $request->file('file')->store('imports', 'local');
            $fullPath = Storage::disk('local')->path($path);
            $rows     = IOFactory::load($fullPath)->getActiveSheet()->toArray(null, true, true, true);

            $created = 0; $updated = 0; $errors = [];

            DB::transaction(function () use ($rows, $companyId, $warehouse, &$created, &$updated, &$errors) {
                foreach ($rows as $rowNum => $row) {
                    if ($rowNum === 1) continue; // cabecera

                    $name = $this->cleanText((string) ($row['A'] ?? ''));
                    if ($name === '') continue;

                    try {
                        $prodNum = $this->parseParenNumber((string) ($row['A'] ?? ''));
                        $catRaw  = (string) ($row['B'] ?? '');
                        $catName = $this->cleanText($catRaw);
                        $catCode = $this->parseParenCode($catRaw);
                        $brandName = $this->cleanText((string) ($row['C'] ?? ''));
                        $modelsRaw = trim((string) ($row['D'] ?? ''));
                        $notas   = trim((string) ($row['E'] ?? '')) ?: null;
                        $cost    = (float) ($row['F'] ?? 0);
                        $price   = (float) ($row['G'] ?? 0);
                        $qty     = (float) ($row['H'] ?? 0);

                        // Categoría (crea si no existe; asigna code si faltaba)
                        $categoryId = null;
                        if ($catName !== '') {
                            $category = ProductCategory::firstOrCreate(
                                ['company_id' => $companyId, 'name' => $catName],
                                ['code' => $catCode, 'active' => true]
                            );
                            if ($catCode && !$category->code) {
                                $category->update(['code' => $catCode]);
                            }
                            $categoryId = $category->id;
                        }

                        // Marca (crea si no existe)
                        $brandId = null;
                        if ($brandName !== '') {
                            $brand = ProductBrand::firstOrCreate(
                                ['company_id' => $companyId, 'name' => $brandName],
                                ['active' => true]
                            );
                            $brandId = $brand->id;
                        }

                        // Código combinado categoría-producto
                        $code = ($catCode !== null && $prodNum !== null)
                            ? $catCode . '-' . str_pad((string) $prodNum, 4, '0', STR_PAD_LEFT)
                            : null;

                        // Buscar por nombre limpio (case-insensitive) dentro de la empresa
                        $product = Product::where('company_id', $companyId)
                            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                            ->first();

                        $payload = [
                            'category_id' => $categoryId,
                            'brand_id'    => $brandId,
                            'code'        => $code,
                            'cost'        => $cost,
                            'price'       => $price,
                            'description' => $notas,
                        ];

                        if ($product) {
                            $product->update($payload);
                            $updated++;
                        } else {
                            $product = Product::create(array_merge($payload, [
                                'company_id'    => $companyId,
                                'name'          => $name,
                                'sku'           => $this->generateSku($name, $companyId),
                                'unit'          => 'unidad',
                                'min_stock'     => 0,
                                'current_stock' => 0,
                                'active'        => true,
                            ]));
                            $created++;
                        }

                        // Modelos compatibles (col D): busca o crea cada uno y asocia
                        if ($modelsRaw !== '') {
                            $modelIds = [];
                            foreach (explode(',', $modelsRaw) as $mRaw) {
                                $mName = $this->cleanText($mRaw);
                                if ($mName === '') continue;
                                $model = MotoModel::firstOrCreate(
                                    ['company_id' => $companyId, 'name' => $mName],
                                    ['active' => true]
                                );
                                $modelIds[] = $model->id;
                            }
                            $product->motoModels()->syncWithoutDetaching($modelIds);
                        }

                        // Fijar stock del almacén
                        $this->setWarehouseStock($product, $warehouse->id, $qty, $warehouse->company_id);
                    } catch (\Throwable $e) {
                        $errors[] = "Fila {$rowNum} ({$name}): " . $e->getMessage();
                    }
                }
            });

            Storage::disk('local')->delete($path);

            return back()->with('import_result', [
                'created' => $created,
                'updated' => $updated,
                'errors'  => $errors,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al migrar inventario', ['msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No se pudo procesar el archivo: ' . $e->getMessage()]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────

    /** Fija el stock de un almacén a $target creando un movimiento por la diferencia. */
    private function setWarehouseStock(Product $product, int $warehouseId, float $target, ?int $companyId = null): void
    {
        $companyId = $companyId ?? $product->company_id;
        $current   = (float) $product->stockInWarehouse($warehouseId);
        $delta     = $target - $current;

        if (abs($delta) < 0.0001) {
            return;
        }

        InventoryMovement::create([
            'company_id'    => $companyId,
            'warehouse_id'  => $warehouseId,
            'product_id'    => $product->id,
            'user_id'       => auth()->id(),
            'type'          => $delta > 0 ? 'in' : 'out',
            'quantity'      => abs($delta),
            'unit_cost'     => $product->cost,
            'reference'     => 'AJUSTE-INV',
            'notes'         => 'Ajuste de inventario (lista)',
            'adjustment_reason' => 'Ajuste de stock a ' . $target,
            'movement_date' => now(),
        ]);

        Product::where('id', $product->id)->increment('current_stock', $delta);
        $product->refresh();
    }

    /** Stock neto por almacén para todos los productos: [product_id => qty]. */
    private function warehouseStockMap(?int $companyId, int $warehouseId): array
    {
        $in = InventoryMovement::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($warehouseId) {
                $q->where(fn ($w) => $w->where('warehouse_id', $warehouseId)->whereIn('type', ['in', 'adjustment']))
                  ->orWhere(fn ($w) => $w->where('destination_warehouse_id', $warehouseId)->where('type', 'transfer'));
            })
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')->pluck('q', 'product_id');

        $out = InventoryMovement::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', ['out', 'transfer'])
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')->pluck('q', 'product_id');

        $map = [];
        foreach ($in as $pid => $q)  $map[$pid] = ($map[$pid] ?? 0) + (float) $q;
        foreach ($out as $pid => $q) $map[$pid] = ($map[$pid] ?? 0) - (float) $q;
        return $map;
    }

    private function cleanText(string $raw): string
    {
        return trim(preg_replace('/\s*\(\w+\)\s*/', ' ', $raw));
    }

    private function parseParenNumber(string $raw): ?int
    {
        return preg_match('/\((\d+)\)/', $raw, $m) ? (int) $m[1] : null;
    }

    private function parseParenCode(string $raw): ?string
    {
        return preg_match('/\(([\w-]+)\)/', $raw, $m) ? $m[1] : null;
    }

    private function generateSku(string $name, int $companyId): string
    {
        $base = Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6)) ?: 'PROD';
        $i = 1;
        do {
            $sku = $base . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $i++;
        } while (Product::withTrashed()->where('company_id', $companyId)->where('sku', $sku)->exists());
        return $sku;
    }

    private function authorizeProduct(Product $product): void
    {
        if (!auth()->user()->is_super_admin && $product->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
