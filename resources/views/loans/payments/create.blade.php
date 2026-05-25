@extends('layouts.app')

@section('title', 'Registrar Pago')

@section('page')
<div class="container-fluid" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-cash-stack"></i> Registrar Pago</h1>
            <p class="text-muted mb-0">Préstamo #{{ $loan->id }} - {{ $loan->client?->name ?? 'Sin cliente' }}</p>
        </div>
        <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><small class="text-muted">Monto préstamo</small><div class="fw-bold">${{ number_format((float) $loan->amount, 2) }}</div></div>
                <div class="col-md-3"><small class="text-muted">Total a pagar</small><div class="fw-bold">${{ number_format((float) $loan->total_to_pay, 2) }}</div></div>
                <div class="col-md-3"><small class="text-muted">Total pagado</small><div class="fw-bold text-success">${{ number_format((float) $loan->total_paid, 2) }}</div></div>
                <div class="col-md-3"><small class="text-muted">Pendiente</small><div class="fw-bold text-danger">${{ number_format((float) ($loan->total_to_pay - $loan->total_paid), 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('loans.payments.store', $loan) }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-4">
                    <label class="form-label">Tipo de pago</label>
                    <select name="payment_type" id="payment_type" class="form-select" required>
                        <option value="interest" {{ old('payment_type') === 'interest' ? 'selected' : '' }}>Interés</option>
                        <option value="capital" {{ old('payment_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                        <option value="mixed" {{ old('payment_type') === 'mixed' ? 'selected' : '' }}>Mixto</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Monto</label>
                    <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                </div>

                <div class="col-md-6" id="capital-field">
                    <label class="form-label">Capital</label>
                    <input type="number" step="0.01" name="capital" class="form-control" value="{{ old('capital') }}">
                </div>

                <div class="col-md-6" id="interest-field">
                    <label class="form-label">Interés</label>
                    <input type="number" step="0.01" name="interest" class="form-control" value="{{ old('interest') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Método de pago</label>
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
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Registrar pago</button>
                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const type = document.getElementById('payment_type');
        const capitalField = document.getElementById('capital-field');
        const interestField = document.getElementById('interest-field');

        function syncFields() {
            const val = type.value;
            capitalField.style.display = (val === 'interest') ? 'none' : '';
            interestField.style.display = (val === 'capital') ? 'none' : '';
        }

        type.addEventListener('change', syncFields);
        syncFields();
    })();
</script>
@endpush
@endsection
