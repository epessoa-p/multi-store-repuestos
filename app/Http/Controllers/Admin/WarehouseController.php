<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Warehouse::with(['company', 'primaryBranch'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        return view('admin.warehouses.index', ['warehouses' => $query->paginate(15)]);
    }

    public function create()
    {
        return view('admin.warehouses.create', $this->formData());
    }

    public function store()
    {
        try {
            $user = auth()->user();
            $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

            $validated = request()->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'name' => 'required|string|max:255',
                'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')],
                'location' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'active' => 'sometimes|boolean',
            ]);

            Warehouse::create([
                ...$validated,
                'company_id' => $companyId,
                'active' => request()->boolean('active', true),
            ]);

            return redirect()->route('warehouses.index')->with('success', 'Almacén creado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al crear almacén', ['message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible crear el almacén.']);
        }
    }

    public function show(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        $warehouse->load(['company', 'primaryBranch', 'inventoryMovements.product', 'inventoryMovements.user']);

        return view('admin.warehouses.show', [
            'warehouse' => $warehouse,
            'products' => Product::where('company_id', $warehouse->company_id)->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);
        return view('admin.warehouses.edit', array_merge($this->formData($warehouse->company_id), ['warehouse' => $warehouse]));
    }

    public function update(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        try {
            $user = auth()->user();
            $companyId = $user->is_super_admin ? request('company_id', $warehouse->company_id) : $warehouse->company_id;

            $validated = request()->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'name' => 'required|string|max:255',
                'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
                'location' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'active' => 'sometimes|boolean',
            ]);

            $warehouse->update([
                ...$validated,
                'company_id' => $companyId,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('warehouses.index')->with('success', 'Almacén actualizado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar almacén', ['warehouse_id' => $warehouse->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar el almacén.']);
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        try {
            $warehouse->delete();
            return redirect()->route('warehouses.index')->with('success', 'Almacén eliminado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al eliminar almacén', ['warehouse_id' => $warehouse->id, 'message' => $exception->getMessage()]);
            return back()->withErrors(['error' => 'No fue posible eliminar el almacén.']);
        }
    }

    public function storeMovement(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        try {
            $validated = request()->validate([
                'product_id' => ['required', 'exists:products,id'],
                'type' => ['required', Rule::in(['in', 'out'])],
                'quantity' => 'required|numeric|min:0.01',
                'unit_cost' => 'nullable|numeric|min:0',
                'reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'movement_date' => 'required|date',
            ]);

            InventoryMovement::create([
                ...$validated,
                'company_id' => $warehouse->company_id,
                'warehouse_id' => $warehouse->id,
                'branch_id' => $warehouse->primaryBranch?->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('warehouses.show', $warehouse)->with('success', 'Movimiento registrado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al registrar movimiento de almacén', ['warehouse_id' => $warehouse->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible registrar el movimiento.']);
        }
    }

    protected function formData(?int $companyId = null): array
    {
        $user = auth()->user();
        $companies = $user->is_super_admin ? Company::orderBy('name')->get() : collect([$user->getCurrentCompany()])->filter();

        return [
            'companies' => $companies,
        ];
    }

    protected function authorizeWarehouse(Warehouse $warehouse): void
    {
        if (!auth()->user()->is_super_admin && $warehouse->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}