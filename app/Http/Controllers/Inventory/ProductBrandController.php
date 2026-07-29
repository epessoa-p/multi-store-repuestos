<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ProductBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductBrandController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $q      = trim((string) $request->get('q', ''));
        $status = $request->get('status'); // '', 'active', 'inactive'

        // Conteos para los filtros (sobre el alcance de la empresa).
        $scope  = fn () => ProductBrand::query()->when($cid, fn ($x) => $x->where('company_id', $cid));
        $counts = [
            'all'      => $scope()->count(),
            'active'   => $scope()->where('active', true)->count(),
            'inactive' => $scope()->where('active', false)->count(),
        ];

        $brands = ProductBrand::with('company')->withCount('products')
            ->when($cid, fn ($x) => $x->where('company_id', $cid))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")))
            ->when($status === 'active', fn ($x) => $x->where('active', true))
            ->when($status === 'inactive', fn ($x) => $x->where('active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.brands.index', compact('brands', 'q', 'status', 'counts'));
    }

    public function create()
    {
        return view('inventory.brands.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin
            ? request('company_id')
            : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay empresa activa.']);
        }

        $validated = request()->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'active'      => 'sometimes|boolean',
        ]);

        try {
            ProductBrand::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);

            return redirect()->route('product-brands.index')
                ->with('success', 'Marca creada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear marca', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(ProductBrand $brand)
    {
        $this->authorizeBrand($brand);
        return view('inventory.brands.edit', array_merge($this->formData(), compact('brand')));
    }

    public function update(ProductBrand $brand)
    {
        $this->authorizeBrand($brand);

        $validated = request()->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'active'      => 'sometimes|boolean',
        ]);

        try {
            $brand->update([
                ...$validated,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('product-brands.index')
                ->with('success', 'Marca actualizada exitosamente.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(ProductBrand $brand)
    {
        $this->authorizeBrand($brand);

        try {
            $brand->delete();
            return redirect()->route('product-brands.index')
                ->with('success', 'Marca eliminada exitosamente.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function authorizeBrand(ProductBrand $brand): void
    {
        if (!auth()->user()->is_super_admin
            && $brand->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user      = auth()->user();
        $companies = $user->is_super_admin
            ? Company::orderBy('name')->get()
            : collect([$user->getCurrentCompany()])->filter();

        return compact('companies');
    }
}
