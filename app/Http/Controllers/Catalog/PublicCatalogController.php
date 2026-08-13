<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory\ProductCategory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    /** Resuelve la sucursal por token (y habilitada), o 404. */
    private function resolveBranch(string $token): Branch
    {
        return Branch::with('company')
            ->where('catalog_token', $token)
            ->where('catalog_enabled', true)
            ->firstOrFail();
    }

    /**
     * Reúne los datos del catálogo de una sucursal: productos activos de la
     * empresa, categorías (para el filtro) y disponibilidad por sucursal.
     */
    private function gather(Branch $branch, Request $request, bool $light = false): array
    {
        $cid = $branch->company_id;

        $q          = trim((string) $request->get('q', ''));
        $categoryId = $request->get('category');

        // En modo liviano (PDF) no se cargan fotos ni columnas innecesarias:
        // menos memoria por modelo × cientos de productos.
        $relations = $light ? ['category:id,name', 'brand:id,name'] : ['category:id,name', 'brand:id,name', 'photos'];

        $productsQuery = Product::with($relations)
            ->where('company_id', $cid)
            ->where('active', true)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%")
                      ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->when($categoryId, fn ($qq) => $qq->where('category_id', $categoryId))
            ->orderBy('name');

        $products = $light
            ? $productsQuery->get(['id', 'name', 'price', 'category_id', 'brand_id'])
            : $productsQuery->get();

        // Sucursales de la empresa (para disponibilidad cruzada).
        $branches = Branch::where('company_id', $cid)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'warehouse_id']);

        // Matriz de stock del Kardex: [warehouse_id => [product_id => qty]].
        $stock = InventoryMovement::stockMatrix($cid);

        // Categorías con productos activos (para el filtro).
        $categories = ProductCategory::where('company_id', $cid)
            ->whereIn('id', function ($sub) use ($cid) {
                $sub->from('products')->select('category_id')
                    ->where('company_id', $cid)->where('active', true)
                    ->whereNotNull('category_id');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return compact('products', 'branches', 'stock', 'categories', 'q', 'categoryId');
    }

    /** Página pública del catálogo (solo lectura). */
    public function show(Request $request, string $token)
    {
        $branch = $this->resolveBranch($token);
        $data   = $this->gather($branch, $request);

        return view('catalog.public', array_merge($data, [
            'branch'  => $branch,
            'company' => $branch->company,
        ]));
    }

    /** Descarga del catálogo en PDF (sin fotos, agrupado por categoría). */
    public function pdf(Request $request, string $token)
    {
        // Catálogos grandes pueden tardar y consumir memoria al componer el PDF.
        @set_time_limit(120);
        @ini_set('memory_limit', '256M');

        $branch = $this->resolveBranch($token);
        $data   = $this->gather($branch, $request, light: true);

        // Agrupar por categoría (ordenado por nombre) para el PDF.
        $grouped = $data['products']
            ->groupBy(fn ($p) => $p->category?->name ?: 'Sin categoría')
            ->sortKeys();

        $pdf = Pdf::loadView('catalog.pdf', array_merge($data, [
            'grouped' => $grouped,
            'branch'  => $branch,
            'company' => $branch->company,
        ]))->setPaper('a4');

        $slug = \Illuminate\Support\Str::slug($branch->name ?: 'sucursal');

        return $pdf->download("catalogo-{$slug}.pdf");
    }
}
