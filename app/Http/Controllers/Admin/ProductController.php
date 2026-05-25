<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CreditCategory;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Product::with('company', 'creditCategory')->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        return view('admin.products.index', ['products' => $query->paginate(15)]);
    }

    public function create()
    {
        $user = auth()->user();
        $companyId = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        return view('admin.products.create', [
            'companies' => $user->is_super_admin ? Company::orderBy('name')->get() : collect([$user->getCurrentCompany()])->filter(),
            'categories' => CreditCategory::when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store()
    {
        try {
            $user = auth()->user();
            $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

            $validated = request()->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'name' => 'required|string|max:255',
                'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')],
                'credit_category_id' => ['nullable', 'exists:credit_categories,id'],
                'description' => 'nullable|string',
                'unit' => 'required|string|max:50',
                'cost' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'active' => 'sometimes|boolean',
            ]);

            Product::create([
                ...$validated,
                'company_id' => $companyId,
                'active' => request()->boolean('active', true),
            ]);

            return redirect()->route('products.index')->with('success', 'Producto creado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al crear producto', ['message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible crear el producto.']);
        }
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);
        $user = auth()->user();

        return view('admin.products.edit', [
            'product' => $product,
            'companies' => $user->is_super_admin ? Company::orderBy('name')->get() : collect([$user->getCurrentCompany()])->filter(),
            'categories' => CreditCategory::where('company_id', $product->company_id)->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Product $product)
    {
        $this->authorizeProduct($product);

        try {
            $user = auth()->user();
            $companyId = $user->is_super_admin ? request('company_id', $product->company_id) : $product->company_id;

            $validated = request()->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'name' => 'required|string|max:255',
                'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
                'credit_category_id' => ['nullable', 'exists:credit_categories,id'],
                'description' => 'nullable|string',
                'unit' => 'required|string|max:50',
                'cost' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'active' => 'sometimes|boolean',
            ]);

            $product->update([
                ...$validated,
                'company_id' => $companyId,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar producto', ['product_id' => $product->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar el producto.']);
        }
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);

        try {
            $product->delete();
            return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al eliminar producto', ['product_id' => $product->id, 'message' => $exception->getMessage()]);
            return back()->withErrors(['error' => 'No fue posible eliminar el producto.']);
        }
    }

    protected function authorizeProduct(Product $product): void
    {
        if (!auth()->user()->is_super_admin && $product->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}