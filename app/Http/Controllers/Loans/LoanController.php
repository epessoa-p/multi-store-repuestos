<?php

namespace App\Http\Controllers\Loans;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\CreditCategory;
use App\Models\CreditCategoryRule;
use App\Models\InventoryMovement;
use App\Models\Loan;
use App\Models\LoanCollateral;
use App\Models\LoanImage;
use App\Models\LoanPayment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class LoanController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-permission:loans.view');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        if (!$user->is_super_admin && $company) {
            $this->updateOverdueCollateralStatuses((int) $company->id);
        }

        $branchesQuery = Branch::where('active', true)->orderBy('name');
        if (!$user->is_super_admin) {
            $branchesQuery->where('company_id', $company?->id);
        }
        $branches = $branchesQuery->get();

        $selectedBranchId = $request->integer('sucursal_id');
        if (!$selectedBranchId && $branches->isNotEmpty()) {
            $selectedBranchId = (int) $branches->first()->id;
        }
        if ($selectedBranchId && $branches->isNotEmpty() && !$branches->pluck('id')->contains($selectedBranchId)) {
            $selectedBranchId = (int) $branches->first()->id;
        }

        $query = Loan::with('client', 'product')->latest();
        if (!$user->is_super_admin) {
            $query->where('company_id', $company?->id);
        }

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        $status = trim((string) $request->get('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('id', $q)
                    ->orWhereHas('client', fn ($clientQ) => $clientQ->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('product', fn ($productQ) => $productQ->where('name', 'like', "%{$q}%"));
            });
        }

        $productId = $request->integer('product_id');
        if ($productId) {
            $query->where('product_id', $productId);
        }

        $dateFrom = $request->get('date_from');
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        $dateTo = $request->get('date_to');
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $productsQuery = Product::where('active', true)->orderBy('name');
        if (!$user->is_super_admin) {
            $productsQuery->where('company_id', $company?->id);
        }
        $products = $productsQuery->get();

        $loans = $query->paginate(15)->withQueryString();

        return view('loans.index', compact(
            'loans',
            'branches',
            'selectedBranchId',
            'status',
            'q',
            'productId',
            'products',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        if (!$company) {
            return redirect()->route('select-company')->withErrors(['error' => 'Selecciona una empresa para crear préstamos.']);
        }

        $branches = $user->is_super_admin
            ? Branch::where('active', true)->orderBy('name')->get()
            : Branch::where('company_id', $company->id)->where('active', true)->orderBy('name')->get();

        $defaultBranchId = old('branch_id', $request->integer('sucursal_id'));
        if (empty($defaultBranchId) && $branches->isNotEmpty()) {
            $defaultBranchId = $branches->first()->id;
        }
        if ($defaultBranchId && $branches->isNotEmpty() && !$branches->pluck('id')->contains((int) $defaultBranchId)) {
            $defaultBranchId = $branches->first()->id;
        }

        $products = $user->is_super_admin
            ? Product::where('active', true)->with('creditCategory')->orderBy('name')->get()
            : Product::where('company_id', $company->id)->where('active', true)->with('creditCategory')->orderBy('name')->get();

        $creditCategories = $user->is_super_admin
            ? CreditCategory::where('active', true)->with(['rules' => fn ($q) => $q->where('active', true)->orderBy('interest_rate')])->orderBy('name')->get()
            : CreditCategory::where('company_id', $company->id)->where('active', true)->with(['rules' => fn ($q) => $q->where('active', true)->orderBy('interest_rate')])->orderBy('name')->get();

        $clients = $user->is_super_admin
            ? Client::where('active', true)->orderBy('name')->get()
            : $company->clients()->where('active', true)->orderBy('name')->get();

        return view('loans.create', compact('company', 'clients', 'products', 'creditCategories', 'branches', 'defaultBranchId'));
    }

    public function store()
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        if (!$company) {
            return redirect()->route('select-company')->withErrors(['error' => 'Selecciona una empresa para crear préstamos.']);
        }

        try {
            $validated = request()->validate([
                'branch_id' => 'required|exists:branches,id',
                'product_id' => 'nullable|exists:products,id',
                'product_name' => 'nullable|string|max:255',
                'product_sku' => 'nullable|string|max:100',
                'product_cost' => 'nullable|numeric|min:0',
                'product_price' => 'nullable|numeric|min:0',
                'product_credit_category_id' => 'nullable|exists:credit_categories,id',
                'credit_category_id' => 'nullable|exists:credit_categories,id',
                'credit_category_rule_id' => 'nullable|exists:credit_category_rules,id',
                'client_id' => 'nullable|exists:clients,id',
                'client_name' => 'nullable|required_without:client_id|string|max:255',
                'client_id_number' => 'nullable|string|max:30',
                'client_phone' => 'nullable|string|max:20',
                'client_email' => 'nullable|email|max:255',
                'client_address' => 'nullable|string|max:255',
                'amount' => 'required|numeric|min:0.01',
                'interest_rate' => 'required|numeric|min:0|max:100',
                'term_months' => 'required|integer|min:1',
                'interest_period' => 'nullable|in:monthly,quarterly,semiannual,annual',
                'notes' => 'nullable|string',
                'images' => 'nullable|array|max:20',
                'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);

            if (!empty($validated['branch_id'])) {
                $branch = Branch::findOrFail($validated['branch_id']);
                if ($branch->company_id !== $company->id && !$user->is_super_admin) {
                    abort(403);
                }
            }

            $product = null;
            if (!empty($validated['product_id'])) {
                $product = Product::findOrFail($validated['product_id']);
                if ($product->company_id !== $company->id && !$user->is_super_admin) {
                    abort(403);
                }
            } elseif (!empty($validated['product_name'])) {
                $product = Product::where('company_id', $company->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['product_name']))])
                    ->first();

                if (!$product) {
                    $sku = trim((string) ($validated['product_sku'] ?? ''));
                    if ($sku === '') {
                        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['product_name']), 0, 6));
                        $sku = ($base !== '' ? $base : 'PRD') . '-' . now()->format('YmdHis');
                    }

                    $product = Product::create([
                        'company_id' => $company->id,
                        'credit_category_id' => $validated['product_credit_category_id'] ?? null,
                        'name' => trim($validated['product_name']),
                        'sku' => $sku,
                        'unit' => 'unidad',
                        'cost' => $validated['product_cost'] ?? 0,
                        'price' => $validated['product_price'] ?? 0,
                        'active' => true,
                    ]);
                }
            }

            $category = null;
            if ($product?->credit_category_id) {
                $category = CreditCategory::find($product->credit_category_id);
            } elseif (!empty($validated['credit_category_id'])) {
                $category = CreditCategory::findOrFail($validated['credit_category_id']);
            }

            if ($category && $category->company_id !== $company->id && !$user->is_super_admin) {
                abort(403);
            }

            $rule = null;
            if (!empty($validated['credit_category_rule_id'])) {
                $rule = CreditCategoryRule::findOrFail($validated['credit_category_rule_id']);
                if ($category && $rule->credit_category_id !== $category->id) {
                    return back()->withInput()->withErrors(['credit_category_rule_id' => 'La regla no corresponde a la categoría seleccionada.']);
                }
            }

            if (!empty($validated['client_id'])) {
                $client = Client::findOrFail($validated['client_id']);
                if ($client->company_id !== $company->id && !$user->is_super_admin) {
                    abort(403);
                }
            } else {
                $client = Client::create([
                    'company_id' => $company->id,
                    'name' => $validated['client_name'],
                    'id_number' => $validated['client_id_number'] ?? null,
                    'phone' => $validated['client_phone'] ?? null,
                    'email' => $validated['client_email'] ?? null,
                    'address' => $validated['client_address'] ?? null,
                    'active' => true,
                ]);
            }

            $endDate = now()->copy()->addMonths((int) ($rule?->term_months_limit ?? $validated['term_months']))->toDateString();

            $loan = $company->loans()->create([
                'branch_id' => $validated['branch_id'] ?? null,
                'product_id' => $product?->id,
                'credit_category_id' => $category?->id,
                'credit_category_rule_id' => $rule?->id,
                'client_id' => $client->id,
                'amount' => $validated['amount'],
                'current_capital' => $validated['amount'],
                'interest_rate' => $rule?->interest_rate ?? $validated['interest_rate'],
                'interest_period' => $rule?->interest_period ?? ($validated['interest_period'] ?? 'monthly'),
                'term_months' => $rule?->term_months_limit ?? $validated['term_months'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'start_date' => now()->toDateString(),
                'end_date' => $endDate,
                'status' => 'active',
            ]);

            $loan->update([
                'monthly_payment' => $loan->calculateMonthlyPayment(),
                'total_to_pay' => $loan->calculateTotalToPay(),
            ]);

            // Auto-create collateral if product exists
            if ($product) {
                $branch = Branch::find($validated['branch_id']);
                $warehouseId = $branch?->warehouse_id;

                if ($warehouseId) {
                    $movement = InventoryMovement::create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouseId,
                        'branch_id' => $loan->branch_id,
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => 'in',
                        'quantity' => 1,
                        'unit_cost' => $product->cost ?? 0,
                        'reference' => 'LOAN-RET-' . $loan->id,
                        'notes' => 'Ingreso a retención por préstamo #' . $loan->id,
                        'movement_date' => now(),
                    ]);

                    LoanCollateral::updateOrCreate(
                        ['loan_id' => $loan->id],
                        [
                            'company_id' => $company->id,
                            'branch_id' => $loan->branch_id,
                            'warehouse_id' => $warehouseId,
                            'product_id' => $product->id,
                            'inventory_movement_id' => $movement->id,
                            'status' => 'retained',
                            'retained_at' => now(),
                            'expected_release_date' => $endDate,
                            'notes' => 'Prenda retenida al crear préstamo',
                        ]
                    );
                }
            }

            // Store attached images
            if (request()->hasFile('images')) {
                foreach (request()->file('images') as $image) {
                    if (!$image) continue;
                    $path = $image->store('loan-images/loan-' . $loan->id, 'public');
                    LoanImage::create([
                        'loan_id' => $loan->id,
                        'path' => $path,
                        'original_name' => $image->getClientOriginalName(),
                        'mime_type' => $image->getClientMimeType(),
                        'size_bytes' => $image->getSize(),
                    ]);
                }
            }

            return redirect()->route('loans.show', $loan)->with('success', 'Préstamo registrado exitosamente');
        } catch (ValidationException $exception) {
            // Let Laravel show field-level errors in the form.
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al registrar préstamo', [
                'company_id' => $company?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible registrar el préstamo.']);
        }
    }

    public function show(Loan $loan)
    {
        $this->authorizeCompany($loan);

        $this->updateOverdueCollateralStatuses((int) $loan->company_id);

        // Inicializar current_capital si es null
        if ($loan->current_capital === null) {
            $loan->update(['current_capital' => $loan->amount]);
        }

        // Generar periodos de interés pendientes
        if (in_array($loan->status, ['active', 'overdue'])) {
            $loan->generatePendingInterestPeriods();
            $loan->checkOverdueStatus();
        }

        $loan->load('client', 'product', 'creditCategory', 'creditCategoryRule', 'createdBy', 'approvedBy', 'payments', 'collateral.warehouse', 'contract.attachments', 'images', 'interestPayments', 'amortizations');

        $contractTemplates = \App\Models\DocumentTemplate::where('company_id', $loan->company_id)
            ->where('type', 'contrato')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('loans.show', compact('loan', 'contractTemplates'));
    }

    public function approve(Loan $loan)
    {
        $this->authorizeCompany($loan);

        if (!auth()->user()->hasPermissionInCompany('loans.approve', auth()->user()->getCurrentCompany())) {
            return back()->withErrors(['error' => 'No tienes permisos para aprobar préstamos']);
        }

        if (!$loan->product_id) {
            return back()->withErrors(['error' => 'El préstamo debe tener un producto para aprobar y retener en almacén.']);
        }

        $loan->loadMissing('branch', 'product');
        $warehouseId = $loan->branch?->warehouse_id;

        if (!$warehouseId) {
            return back()->withErrors(['error' => 'La sucursal no tiene almacén principal configurado.']);
        }

        $endDate = now()->copy()->addMonths((int) $loan->term_months)->toDateString();

        $loan->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->id,
            'approved_at' => now(),
            'start_date' => now()->toDateString(),
            'end_date' => $endDate,
        ]);

        $movement = InventoryMovement::create([
            'company_id' => $loan->company_id,
            'warehouse_id' => $warehouseId,
            'branch_id' => $loan->branch_id,
            'product_id' => $loan->product_id,
            'user_id' => auth()->id(),
            'type' => 'in',
            'quantity' => 1,
            'unit_cost' => $loan->product?->cost ?? 0,
            'reference' => 'LOAN-RET-' . $loan->id,
            'notes' => 'Ingreso a retención por préstamo #' . $loan->id,
            'movement_date' => now(),
        ]);

        LoanCollateral::updateOrCreate(
            ['loan_id' => $loan->id],
            [
                'company_id' => $loan->company_id,
                'branch_id' => $loan->branch_id,
                'warehouse_id' => $warehouseId,
                'product_id' => $loan->product_id,
                'inventory_movement_id' => $movement->id,
                'status' => 'retained',
                'retained_at' => now(),
                'expected_release_date' => $endDate,
                'notes' => 'Prenda retenida por aprobación de préstamo',
            ]
        );

        return back()->with('success', 'Préstamo aprobado exitosamente');
    }

    public function activate(Loan $loan)
    {
        $this->authorizeCompany($loan);

        if ($loan->status !== 'approved') {
            return back()->withErrors(['error' => 'El préstamo debe estar aprobado']);
        }

        $loan->update(['status' => 'active']);

        return back()->with('success', 'Préstamo activado');
    }

    public function recordPayment(Loan $loan)
    {
        $this->authorizeCompany($loan);

        $validated = request()->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $payment = $loan->payments()->create([
            ...$validated,
            'registered_by' => auth()->user()->id,
        ]);

        $loan->increment('total_paid', $validated['amount']);

        if ($loan->total_paid >= $loan->total_to_pay) {
            $loan->update(['status' => 'finished']);
            $this->releaseCollateralFromLoan($loan);
        }

        return back()->with('success', 'Pago registrado exitosamente');
    }

    public function destroy(Loan $loan)
    {
        $this->authorizeCompany($loan);

        if (!auth()->user()->hasPermissionInCompany('loans.delete', auth()->user()->getCurrentCompany())) {
            return back()->withErrors(['error' => 'No tienes permisos para eliminar préstamos']);
        }

        if ($loan->status !== 'pending' && $loan->status !== 'cancelled') {
            return back()->withErrors(['error' => 'Solo se pueden eliminar préstamos en estado pendiente o cancelado']);
        }

        $loan->delete();

        return redirect()->route('loans.index')->with('success', 'Préstamo eliminado exitosamente');
    }

    protected function authorizeCompany(Loan $loan)
    {
        if (!auth()->user()->is_super_admin && $loan->company_id !== auth()->user()->getCurrentCompany()->id) {
            abort(403);
        }
    }

    protected function updateOverdueCollateralStatuses(int $companyId): void
    {
        $collaterals = LoanCollateral::where('company_id', $companyId)
            ->where('status', 'retained')
            ->whereDate('expected_release_date', '<', now()->toDateString())
            ->with('loan')
            ->get();

        foreach ($collaterals as $collateral) {
            $loan = $collateral->loan;
            if (!$loan) {
                continue;
            }

            if ((float) $loan->total_paid < (float) $loan->total_to_pay) {
                $collateral->update([
                    'status' => 'sellable',
                    'sellable_at' => now(),
                ]);

                if ($loan->status !== 'finished') {
                    $loan->update(['status' => 'overdue']);
                }
            }
        }
    }

    protected function releaseCollateralFromLoan(Loan $loan): void
    {
        $collateral = LoanCollateral::where('loan_id', $loan->id)->whereIn('status', ['retained', 'sellable'])->first();

        if (!$collateral) {
            return;
        }

        InventoryMovement::create([
            'company_id' => $loan->company_id,
            'warehouse_id' => $collateral->warehouse_id,
            'branch_id' => $collateral->branch_id,
            'product_id' => $collateral->product_id,
            'user_id' => auth()->id(),
            'type' => 'out',
            'quantity' => 1,
            'unit_cost' => $loan->product?->cost ?? 0,
            'reference' => 'LOAN-REL-' . $loan->id,
            'notes' => 'Salida por liberación de prenda (préstamo pagado)',
            'movement_date' => now(),
        ]);

        $collateral->update([
            'status' => 'released',
            'released_at' => now(),
        ]);
    }
}
