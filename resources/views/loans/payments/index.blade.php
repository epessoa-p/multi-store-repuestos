@extends('layouts.app')

@section('title', 'Pagos de Préstamos')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-receipt-cutoff"></i> Pagos de Préstamos</h1>
            <p class="text-muted mb-0">Historial de abonos e intereses registrados.</p>
        </div>
        <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver a préstamos</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Referencia o cliente">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('loans.payments.index') }}" class="btn btn-light border">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Préstamo</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Capital</th>
                            <th class="text-end">Interés</th>
                            <th>Método</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('d/m/Y') }}</td>
                                <td><a href="{{ route('loans.show', $payment->loan) }}" class="text-decoration-none">#{{ $payment->loan_id }}</a></td>
                                <td>{{ $payment->loan?->client?->name ?? '-' }}</td>
                                <td>{{ $payment->loan?->product?->name ?? '-' }}</td>
                                <td class="text-end">${{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="text-end">${{ number_format((float) $payment->capital, 2) }}</td>
                                <td class="text-end">${{ number_format((float) $payment->interest, 2) }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>{{ $payment->reference ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4 text-muted">No hay pagos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $payments->links() }}
    </div>
</div>
@endsection
