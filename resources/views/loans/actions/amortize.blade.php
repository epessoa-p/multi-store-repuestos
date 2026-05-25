@extends('layouts.app')

@section('title', 'Amortizar Préstamo')

@section('page')
<div class="container-fluid" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-graph-up-arrow"></i> Amortizar Préstamo</h1>
            <p class="text-muted mb-0">Préstamo #{{ $loan->id }} - {{ $loan->client?->name ?? 'Sin cliente' }}</p>
        </div>
        <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><small class="text-muted">Saldo capital estimado</small><div class="fw-bold text-primary">${{ number_format((float) $loan->getOutstandingPrincipal(), 2) }}</div></div>
                <div class="col-md-4"><small class="text-muted">Cuota actual</small><div class="fw-bold">${{ number_format((float) $loan->monthly_payment, 2) }}</div></div>
                <div class="col-md-4"><small class="text-muted">Total pendiente</small><div class="fw-bold text-danger">${{ number_format((float) ($loan->total_to_pay - $loan->total_paid), 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('loans.amortize.store', $loan) }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Modalidad</label>
                    <select name="mode" class="form-select" required>
                        <option value="installment" {{ old('mode') === 'installment' ? 'selected' : '' }}>Pago de cuota</option>
                        <option value="capital" {{ old('mode') === 'capital' ? 'selected' : '' }}>Abono a capital</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Monto</label>
                    <input type="number" name="amount" step="0.01" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Método</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Referencia</label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Aplicar amortización</button>
                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
