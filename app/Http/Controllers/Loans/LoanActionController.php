<?php

namespace App\Http\Controllers\Loans;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Loan;
use App\Models\LoanCollateral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoanActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-permission:loans.view');
    }

    /**
     * Registrar pago de interés de un periodo específico.
     */
    public function storeInterestPayment(Request $request, Loan $loan)
    {
        $this->authorizeCompany($loan);

        try {
            $validated = $request->validate([
                'interest_payment_id' => 'required|integer|exists:loan_interest_payments,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string|max:50',
                'reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $interestPayment = $loan->interestPayments()->findOrFail($validated['interest_payment_id']);

            if ($interestPayment->status === 'paid') {
                return back()->withErrors(['error' => 'Este periodo de interés ya fue pagado.']);
            }

            $remaining = $interestPayment->getRemainingAmount();
            $amount = min((float) $validated['amount'], $remaining);

            if ($amount <= 0) {
                return back()->withErrors(['error' => 'El monto debe ser mayor a 0.']);
            }

            $interestPayment->update([
                'paid_amount' => (float) $interestPayment->paid_amount + $amount,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'registered_by' => auth()->id(),
                'paid_at' => now(),
                'status' => ((float) $interestPayment->paid_amount + $amount) >= (float) $interestPayment->interest_amount ? 'paid' : 'pending',
            ]);

            // Registrar también como pago general del préstamo
            $loan->payments()->create([
                'registered_by' => auth()->id(),
                'amount' => $amount,
                'payment_type' => 'interest',
                'capital' => 0,
                'interest' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => 'Pago interés periodo #' . $interestPayment->period_number . '. ' . ($validated['notes'] ?? ''),
            ]);

            $loan->increment('total_paid', $amount);

            return redirect()->route('loans.show', $loan)->with('success', 'Pago de interés registrado correctamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al pagar interés', [
                'loan_id' => $loan->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible registrar el pago de interés.']);
        }
    }

    /**
     * Registrar amortización (abono al capital).
     */
    public function storeAmortization(Request $request, Loan $loan)
    {
        $this->authorizeCompany($loan);

        try {
            $currentCapital = $loan->getCurrentCapital();

            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01|max:' . $currentCapital,
                'payment_date' => 'required|date',
                'payment_method' => 'required|string|max:50',
                'reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $amount = (float) $validated['amount'];
            $capitalAfter = round($currentCapital - $amount, 2);

            // Registrar amortización
            $loan->amortizations()->create([
                'amount' => $amount,
                'capital_before' => $currentCapital,
                'capital_after' => $capitalAfter,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'registered_by' => auth()->id(),
            ]);

            // Actualizar capital vigente
            $loan->update(['current_capital' => $capitalAfter]);

            // Registrar como pago general
            $loan->payments()->create([
                'registered_by' => auth()->id(),
                'amount' => $amount,
                'payment_type' => 'capital',
                'capital' => $amount,
                'interest' => 0,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => 'Amortización al capital. ' . ($validated['notes'] ?? ''),
            ]);

            $loan->increment('total_paid', $amount);

            // Si el capital llega a 0 y no hay intereses pendientes, liquidar
            if ($capitalAfter <= 0 && $loan->getUnpaidInterestTotal() <= 0) {
                $loan->update(['status' => 'finished', 'monthly_payment' => 0]);
                $this->releaseCollateralFromLoan($loan);
            }

            return redirect()->route('loans.show', $loan)->with('success', 'Amortización registrada. Nuevo capital: Bs ' . number_format($capitalAfter, 2));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al amortizar préstamo', [
                'loan_id' => $loan->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible registrar la amortización.']);
        }
    }

    /**
     * Liquidar préstamo: pagar capital + intereses + multas pendientes.
     */
    public function storeLiquidation(Request $request, Loan $loan)
    {
        $this->authorizeCompany($loan);

        try {
            $liquidationAmount = $loan->getLiquidationAmount();

            $validated = $request->validate([
                'amount' => 'required|numeric|min:' . ($liquidationAmount > 0 ? $liquidationAmount : 0.01),
                'payment_date' => 'required|date',
                'payment_method' => 'required|string|max:50',
                'reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $amount = (float) $validated['amount'];
            $capital = $loan->getCurrentCapital();
            $unpaidInterest = $loan->getUnpaidInterestTotal();
            $penalty = (float) $loan->penalty_amount;

            // Pagar todos los intereses pendientes
            $loan->interestPayments()->where('status', '!=', 'paid')->each(function ($ip) {
                $ip->update([
                    'paid_amount' => $ip->interest_amount,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'registered_by' => auth()->id(),
                ]);
            });

            // Registrar pago de liquidación
            $loan->payments()->create([
                'registered_by' => auth()->id(),
                'amount' => $amount,
                'payment_type' => 'mixed',
                'capital' => $capital,
                'interest' => $unpaidInterest + $penalty,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => 'Liquidación total del préstamo. ' . ($validated['notes'] ?? ''),
            ]);

            $loan->increment('total_paid', $amount);

            $loan->update([
                'status' => 'finished',
                'current_capital' => 0,
                'monthly_payment' => 0,
                'penalty_status' => 'normal',
                'penalty_amount' => 0,
                'total_to_pay' => max((float) $loan->total_to_pay, (float) $loan->total_paid + $amount),
            ]);

            $this->releaseCollateralFromLoan($loan);

            return redirect()->route('loans.show', $loan)->with('success', 'Préstamo liquidado correctamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al liquidar préstamo', [
                'loan_id' => $loan->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible liquidar el préstamo.']);
        }
    }

    public function amortize(Loan $loan)
    {
        $this->authorizeCompany($loan);
        $loan->load('client', 'product');
        return view('loans.actions.amortize', compact('loan'));
    }

    public function liquidate(Loan $loan)
    {
        $this->authorizeCompany($loan);
        $loan->load('client', 'product');
        $pending = $loan->getLiquidationAmount();
        return view('loans.actions.liquidate', compact('loan', 'pending'));
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
            'notes' => 'Salida por liberación de prenda (préstamo liquidado)',
            'movement_date' => now(),
        ]);

        $collateral->update([
            'status' => 'released',
            'released_at' => now(),
        ]);
    }
}
