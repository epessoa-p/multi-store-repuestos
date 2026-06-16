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

        // ── KPIs de valor de stock (según almacén seleccionado) ──
        $stockOf = fn ($p) => $isAll ? (float) $p->current_stock : (float) ($stockMap[$p->id] ?? 0);
        $totalUnits = 0.0; $valueCost = 0.0; $valuePrice = 0.0;
        foreach ($products as $p) {
            $s = $stockOf($p);
            $totalUnits += $s;
            $valueCost  += $s * (float) $p->cost;
            $valuePrice += $s * (float) $p->price;
        }
        $productCount    = $products->count();
        $potentialProfit = $valuePrice - $valueCost;

        return view('inventory.stock.index', compact(
            'products', 'warehouses', 'categories', 'brands', 'activeWarehouse', 'isAll', 'whId', 'stockMap',
            'productCount', 'totalUnits', 'valueCost', 'valuePrice', 'potentialProfit'
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
            'quantity'     => 'required|integer|min:0',
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

    /** Resuelve company + warehouse validando pertenencia (helper común). */
    private function resolveCompanyWarehouse(Request $request): array
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? $request->company_id : $user->getCurrentCompany()?->id;
        if (!$companyId) {
            abort(response()->json(['ok' => false, 'message' => 'No hay una empresa activa.'], 422));
        }
        $warehouse = Warehouse::findOrFail($request->warehouse_id);
        if (!$user->is_super_admin && $warehouse->company_id !== $companyId) {
            abort(403);
        }
        return [$companyId, $warehouse];
    }

    /** Paso 1→2: parsea el Excel y devuelve las filas (sin persistir) para revisar/editar. */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file'         => 'required|file|mimes:xlsx,xls|max:5120',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        [$companyId, $warehouse] = $this->resolveCompanyWarehouse($request);

        try {
            $path     = $request->file('file')->store('imports', 'local');
            $fullPath = Storage::disk('local')->path($path);
            $rows     = $this->parseRows($fullPath);
            Storage::disk('local')->delete($path);

            // Enriquecer con estado (nuevo/actualiza) y valores actuales
            $existing = Product::where('company_id', $companyId)->get(['name', 'cost', 'price'])
                ->keyBy(fn ($p) => mb_strtolower($p->name));

            foreach ($rows as &$r) {
                $ex = $existing->get(mb_strtolower($r['name']));
                $r['status']        = $ex ? 'update' : 'new';
                $r['current_cost']  = $ex ? (float) $ex->cost : null;
                $r['current_price'] = $ex ? (float) $ex->price : null;
            }
            unset($r);

            return response()->json([
                'ok'        => true,
                'rows'      => array_values($rows),
                'warehouse' => $warehouse->name,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al previsualizar importación', ['msg' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'No se pudo leer el archivo: ' . $e->getMessage()], 422);
        }
    }

    /** Paso 3: confirma la importación a partir de las filas revisadas/editadas. */
    public function confirmImport(Request $request)
    {
        $request->validate([
            'warehouse_id'   => 'required|exists:warehouses,id',
            'rows'           => 'required|array|min:1',
            'rows.*.name'    => 'nullable|string',
        ]);

        [$companyId, $warehouse] = $this->resolveCompanyWarehouse($request);

        $counters = ['created' => 0, 'updated' => 0];
        $errors   = [];

        try {
            DB::transaction(function () use ($request, $companyId, $warehouse, &$counters, &$errors) {
                foreach ($request->input('rows', []) as $i => $d) {
                    $name = trim((string) ($d['name'] ?? ''));
                    if ($name === '') continue;
                    try {
                        $this->persistRow($d, $companyId, $warehouse, $counters);
                    } catch (\Throwable $e) {
                        $errors[] = "Fila " . ($i + 1) . " ({$name}): " . $e->getMessage();
                    }
                }
            });

            return response()->json([
                'ok'      => true,
                'created' => $counters['created'],
                'updated' => $counters['updated'],
                'errors'  => $errors,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al confirmar importación', ['msg' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'No se pudo importar: ' . $e->getMessage()], 422);
        }
    }

    /** Procesa la migración directa desde Excel (fallback sin JS). */
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
            $rows     = $this->parseRows($fullPath);
            Storage::disk('local')->delete($path);

            $counters = ['created' => 0, 'updated' => 0];
            $errors   = [];

            DB::transaction(function () use ($rows, $companyId, $warehouse, &$counters, &$errors) {
                foreach ($rows as $i => $d) {
                    try {
                        $this->persistRow($d, $companyId, $warehouse, $counters);
                    } catch (\Throwable $e) {
                        $errors[] = "Fila " . ($i + 1) . " ({$d['name']}): " . $e->getMessage();
                    }
                }
            });

            return back()->with('import_result', [
                'created' => $counters['created'],
                'updated' => $counters['updated'],
                'errors'  => $errors,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al migrar inventario', ['msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No se pudo procesar el archivo: ' . $e->getMessage()]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────

    /** Parsea el Excel a un arreglo de filas normalizadas (sin persistir). */
    private function parseRows(string $fullPath): array
    {
        $raw = IOFactory::load($fullPath)->getActiveSheet()->toArray(null, true, true, true);
        $out = [];
        foreach ($raw as $rowNum => $row) {
            if ($rowNum === 1) continue; // cabecera
            $name = $this->cleanText((string) ($row['A'] ?? ''));
            if ($name === '') continue;

            $prodNum = $this->parseParenNumber((string) ($row['A'] ?? ''));
            $catRaw  = (string) ($row['B'] ?? '');
            $catCode = $this->parseParenCode($catRaw);
            $code    = ($catCode !== null && $prodNum !== null)
                ? $catCode . '-' . str_pad((string) $prodNum, 4, '0', STR_PAD_LEFT)
                : null;

            $out[] = [
                'name'          => $name,
                'category'      => $this->cleanText($catRaw),
                'category_code' => $catCode,
                'code'          => $code,
                'brand'         => $this->cleanText((string) ($row['C'] ?? '')),
                'models'        => trim((string) ($row['D'] ?? '')),
                'notes'         => trim((string) ($row['E'] ?? '')),
                'cost'          => (float) ($row['F'] ?? 0),
                'price'         => (float) ($row['G'] ?? 0),
                'qty'           => (float) ($row['H'] ?? 0),
            ];
        }
        return $out;
    }

    /** Crea/actualiza un producto + categoría/marca/modelos y fija stock. */
    private function persistRow(array $d, int $companyId, Warehouse $warehouse, array &$counters): void
    {
        $name = trim((string) ($d['name'] ?? ''));
        if ($name === '') return;

        // Categoría
        $categoryId = null;
        $catName = trim((string) ($d['category'] ?? ''));
        $catCode = $d['category_code'] ?? null;
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

        // Marca
        $brandId = null;
        $brandName = trim((string) ($d['brand'] ?? ''));
        if ($brandName !== '') {
            $brand = ProductBrand::firstOrCreate(
                ['company_id' => $companyId, 'name' => $brandName],
                ['active' => true]
            );
            $brandId = $brand->id;
        }

        $payload = [
            'category_id' => $categoryId,
            'brand_id'    => $brandId,
            'code'        => $d['code'] ?? null,
            'cost'        => (float) ($d['cost'] ?? 0),
            'price'       => (float) ($d['price'] ?? 0),
            'description' => trim((string) ($d['notes'] ?? '')) ?: null,
        ];

        $product = Product::where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($product) {
            $product->update($payload);
            $counters['updated']++;
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
            $counters['created']++;
        }

        // Modelos compatibles (CSV): busca o crea cada uno y asocia
        $models    = $d['models'] ?? '';
        $modelsRaw = is_array($models) ? implode(',', $models) : (string) $models;
        if (trim($modelsRaw) !== '') {
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
        $this->setWarehouseStock($product, $warehouse->id, (float) ($d['qty'] ?? 0), $warehouse->company_id);
    }

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
