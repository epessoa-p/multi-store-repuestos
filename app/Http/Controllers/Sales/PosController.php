<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\HandlesSaleCreation;
use App\Models\Client;
use App\Models\Inventory\ProductCategory;
use App\Models\Motos\MotoModel;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    use HandlesSaleCreation;

    public function index()
    {
        $user    = auth()->user();
        $cid     = $user->getCurrentCompany()?->id;
        $session = $this->currentOpenSession();

        $products = Product::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->with(['category', 'brand', 'photos', 'motoModels.brand'])
            ->orderBy('name')
            ->get();

        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'id_number']);

        $categories = ProductCategory::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Modelos con al menos un producto asociado (para el filtro)
        $motoModels = MotoModel::with('brand')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereHas('products')
            ->orderBy('name')
            ->get();

        return view('sales.pos.index', compact('session', 'products', 'clients', 'categories', 'motoModels'));
    }

    public function store(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->getCurrentCompany()?->id;
        $session   = $this->currentOpenSession();

        if (!$session) {
            return back()->withErrors(['error' => 'Debes abrir tu caja antes de vender en el POS.']);
        }

        $validated = $request->validate([
            'client_id'          => 'nullable|exists:clients,id',
            'sale_type'          => 'required|in:cash,credit',
            'discount'           => 'nullable|numeric|min:0',
            'interest'           => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            // Crédito rápido
            'installments'             => 'nullable|array',
            'installments.*.due_date'  => 'required_with:installments|date',
            'installments.*.amount'    => 'required_with:installments|numeric|min:0.01',
            'down_payment'             => 'nullable|numeric|min:0',
        ]);

        try {
            $sale = $this->confirmSale([
                'company_id'   => $companyId,
                'branch_id'    => $session->cashRegister?->branch_id,
                'client_id'    => $validated['client_id'] ?? null,
                'sale_type'    => $validated['sale_type'],
                'sale_date'    => now()->toDateString(),
                'discount'     => $validated['discount'] ?? 0,
                'tax'          => 0,
                'interest'     => $validated['interest'] ?? 0,
                'notes'        => 'Venta POS',
                'items'        => $validated['items'],
                'installments' => $validated['installments'] ?? [],
                'down_payment' => $validated['down_payment'] ?? 0,
            ], $session);

            return redirect()->route('sales.show', $sale)
                ->with('success', 'Venta registrada: ' . $sale->code)
                ->with('print_receipt', true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Error en venta POS', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al registrar la venta: ' . $e->getMessage()]);
        }
    }
}
