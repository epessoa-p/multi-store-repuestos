<?php

namespace App\Http\Controllers\Loans;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Loan;
use App\Models\LoanCollateral;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoanPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-permission:loans.view');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        $query = LoanPayment::with(['loan.client', 'loan.product', 'registeredBy'])->latest();

        if (!$user->is_super_admin) {
            $query->whereHas('loan', fn ($loanQuery) => $loanQuery->where('company_id', $company?->id));
        }

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('loan.client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$q}%"));
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        return view('loans.payments.index', compact('payments', 'q'));
    }

    public function create(Loan $loan)
    {
        $this->authorizeCompany($loan);

        $loan->load('client', 'product');

        return view('loans.payments.create', compact('loan'));
    }

    public function store(Request $request, Loan $loan)
    {
        $this->authorizeCompany($loan);

        try {
            $validated = $request->validate([
                'payment_type' => 'required|in:interest,capital,mixed',
                'amount' => 'required|numeric|min:0.01',
                'capital' => 'nullable|numeric|min:0',
                'interest' => 'nullable|numeric|min:0',
                'payment_date' => 'required|date',
                'payment_method' => 'required|string|max:50',
                'reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $amount = (float) $validated['amount'];
            $capital = (float) ($validated['capital'] ?? 0);
            $interest = (float) ($validated['interest'] ?? 0);

            if ($validated['payment_type'] === 'interest') {
                $capital = 0;
                $interest = $amount;
            } elseif ($validated['payment_type'] === 'capital') {
                $capital = $amount;
                $interest = 0;
            } elseif ($capital <= 0 && $interest <= 0) {
                $capital = $amount;
            }

            $loan->payments()->create([
                'registered_by' => auth()->id(),
                'amount' => $amount,
                'payment_type' => $validated['payment_type'],
                'capital' => $capital,
                'interest' => $interest,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $loan->increment('total_paid', $amount);

            if ($loan->fresh()->total_paid >= $loan->total_to_pay) {
                $loan->update(['status' => 'finished']);
                $this->releaseCollateralFromLoan($loan);
            }

            return redirect()->route('loans.payments.index')->with('success', 'Pago registrado correctamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al registrar pago de préstamo', [
                'loan_id' => $loan->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible registrar el pago.']);
        }
    }

    protected function authorizeCompany(Loan $loan): void
    {
        if (!auth()->user()->is_super_admin && $loan->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
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
