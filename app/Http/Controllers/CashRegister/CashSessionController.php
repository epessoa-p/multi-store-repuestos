<?php

namespace App\Http\Controllers\CashRegister;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CashSessionController extends Controller
{
    public function open()
    {
        $validated = request()->validate([
            'cash_register_id' => 'required|exists:cash_registers,id',
            'opening_amount'   => 'required|numeric|min:0',
            'opening_notes'    => 'nullable|string|max:1000',
        ]);

        $register = CashRegister::with('assignedPersonal')->findOrFail($validated['cash_register_id']);
        $this->authorizeSessionAction($register->company_id);

        if (!$register->assigned_personal_id) {
            return back()->withErrors(['error' => 'La caja no tiene un cajero asignado.']);
        }

        if ($register->activeSession()) {
            return back()->withErrors(['error' => 'Esta caja ya tiene una sesión activa.']);
        }

        try {
            CashRegisterSession::create([
                'cash_register_id' => $register->id,
                'personal_id'      => $register->assigned_personal_id,
                'opened_by'        => auth()->id(),
                'opening_amount'   => $validated['opening_amount'],
                'status'           => 'open',
                'notes'            => $validated['opening_notes'] ?? null,
                'opened_at'        => now(),
            ]);

            return redirect()->back()->with('success', 'Caja abierta exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al abrir caja', ['message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al abrir la caja: ' . $e->getMessage()]);
        }
    }

    public function close(CashRegisterSession $session)
    {
        if (!$session->isOpen()) {
            return back()->withErrors(['error' => 'Esta sesión ya está cerrada.']);
        }

        $validated = request()->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
        ]);

        try {
            $income         = $session->totalIncome();
            $expense        = $session->totalExpense();
            $expectedAmount = (float) $session->opening_amount + $income - $expense;
            $closingAmount  = (float) $validated['closing_amount'];

            $session->update([
                'closed_by'       => auth()->id(),
                'closing_amount'  => $closingAmount,
                'expected_amount' => $expectedAmount,
                'difference'      => $closingAmount - $expectedAmount,
                'status'          => 'closed',
                'notes'           => $validated['notes'] ?? $session->notes,
                'closed_at'       => now(),
            ]);

            return redirect()->back()->with('success', 'Caja cerrada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al cerrar caja', ['session_id' => $session->id, 'message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al cerrar la caja: ' . $e->getMessage()]);
        }
    }

    public function show(CashRegisterSession $session)
    {
        $session->load(['cashRegister.branch', 'openedBy', 'closedBy']);

        $movements = $session->movements()
            ->with('user')
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->get();

        $availableRegisters = $session->cashRegister
            ? CashRegister::where('company_id', $session->cashRegister->company_id)
                ->where('active', true)
                ->with('branch')
                ->get()
            : collect();

        return view('cash.session.show', compact('session', 'movements', 'availableRegisters'));
    }

    public function addMovement(CashRegisterSession $session)
    {
        if (!$session->isOpen()) {
            return back()->withErrors(['error' => 'No se pueden agregar movimientos a una sesión cerrada.']);
        }

        $categoryKeys = array_keys(CashMovement::CATEGORIES);

        $validated = request()->validate([
            'type'          => ['required', Rule::in(['income', 'expense'])],
            'category'      => ['required', Rule::in($categoryKeys)],
            'amount'        => 'required|numeric|min:0.01',
            'method'        => 'nullable|string|max:50',
            'description'   => 'nullable|string|max:500',
            'movement_date' => 'required|date',
        ]);

        try {
            $session->load('cashRegister');

            CashMovement::create([
                ...$validated,
                'company_id'               => $session->cashRegister->company_id,
                'cash_register_id'         => $session->cash_register_id,
                'cash_register_session_id' => $session->id,
                'user_id'                  => auth()->id(),
            ]);

            return redirect()->route('cash.session.show', $session)->with('success', 'Movimiento registrado.');
        } catch (\Throwable $e) {
            Log::error('Error al agregar movimiento', ['session_id' => $session->id, 'message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al registrar el movimiento: ' . $e->getMessage()]);
        }
    }

    private function authorizeSessionAction(int $companyId): void
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $user->getCurrentCompany()?->id !== $companyId) {
            abort(403);
        }
    }
}
