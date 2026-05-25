@extends('layouts.app')

@section('title', 'Liquidar Préstamo')

@section('page')
<div class="container-fluid" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-currency-dollar"></i> Liquidar Préstamo</h1>
            <p class="text-muted mb-0">Pago total para cerrar el préstamo #{{ $loan->id }}</p>
        </div>
        <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    <div class="alert alert-warning border-0 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Monto recomendado para liquidar:</strong>
                <span class="ms-2 fs-5">${{ number_format((float) $pending, 2) }}</span>
            </div>
            <span class="badge bg-dark">Incluye interés pendiente</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('loans.liquidate.store', $loan) }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Monto de liquidación</label>
                    <input type="number" name="amount" step="0.01" min="{{ $pending }}" class="form-control" value="{{ old('amount', $pending) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-4">
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
                <div class="col-md-6">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-danger" type="submit"><i class="bi bi-lock-fill"></i> Liquidar préstamo</button>
                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
