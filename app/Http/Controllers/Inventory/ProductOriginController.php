<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ProductOrigin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductOriginController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $q      = trim((string) $request->get('q', ''));
        $status = $request->get('status'); // '', 'active', 'inactive'

        // Conteos para los filtros (sobre el alcance de la empresa).
        $scope  = fn () => ProductOrigin::query()->when($cid, fn ($x) => $x->where('company_id', $cid));
        $counts = [
            'all'      => $scope()->count(),
            'active'   => $scope()->where('active', true)->count(),
            'inactive' => $scope()->where('active', false)->count(),
        ];

        $origins = ProductOrigin::with('company')->withCount('products')
            ->when($cid, fn ($x) => $x->where('company_id', $cid))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")))
            ->when($status === 'active', fn ($x) => $x->where('active', true))
            ->when($status === 'inactive', fn ($x) => $x->where('active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.origins.index', compact('origins', 'q', 'status', 'counts'));
    }

    public function create()
    {
        return view('inventory.origins.create', $this->formData());
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
            ProductOrigin::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);

            return redirect()->route('product-origins.index')
                ->with('success', 'Origen creado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear origen', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(ProductOrigin $origin)
    {
        $this->authorizeOrigin($origin);
        return view('inventory.origins.edit', array_merge($this->formData(), compact('origin')));
    }

    public function update(ProductOrigin $origin)
    {
        $this->authorizeOrigin($origin);

        $validated = request()->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'active'      => 'sometimes|boolean',
        ]);

        try {
            $origin->update([
                ...$validated,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('product-origins.index')
                ->with('success', 'Origen actualizado exitosamente.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(ProductOrigin $origin)
    {
        $this->authorizeOrigin($origin);

        try {
            $origin->delete();
            return redirect()->route('product-origins.index')
                ->with('success', 'Origen eliminado exitosamente.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function authorizeOrigin(ProductOrigin $origin): void
    {
        if (!auth()->user()->is_super_admin
            && $origin->company_id !== auth()->user()->getCurrentCompany()?->id) {
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
