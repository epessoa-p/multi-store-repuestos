@extends('layouts.app')

@section('title', $client->name . ' - Detalle del Cliente')

@section('page')
@php
    $totalBorrowed = (float) ($client->total_borrowed ?? 0);
    $totalPaid     = (float) ($client->total_paid_sum ?? 0);
    $totalPending  = max(0, $totalBorrowed - $totalPaid);

    $statusColors = [
        'active' => 'success', 'pending' => 'warning', 'approved' => 'info',
        'finished' => 'secondary', 'cancelled' => 'dark', 'overdue' => 'danger',
    ];
    $statusLabels = [
        'active' => 'Activo', 'pending' => 'Pendiente', 'approved' => 'Aprobado',
        'finished' => 'Finalizado', 'cancelled' => 'Cancelado', 'overdue' => 'Vencido',
    ];
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('clients.index') }}" class="text-decoration-none">Clientes</a></li>
                <li class="breadcrumb-item active">{{ $client->name }}</li>
            </ol>
        </nav>
        <h1 class="mb-0 fs-4 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-person-fill text-primary"></i>
            {{ $client->name }}
            @if($client->active)
                <span class="badge bg-success fs-6">Activo</span>
            @else
                <span class="badge bg-secondary fs-6">Inactivo</span>
            @endif
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i> Editar</a>
        <a href="{{ route('clients.index') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver</a>
    </div>
</div>

{{-- Client info + stats --}}
<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-lines-fill text-primary me-1"></i> Información del cliente</h6>
                <div class="row g-2 small">
                    <div class="col-5 text-muted">Documento:</div>
                    <div class="col-7 fw-semibold">{{ $client->id_number ?: '-' }}</div>
                    <div class="col-5 text-muted">Teléfono:</div>
                    <div class="col-7 fw-semibold">{{ $client->phone ?: '-' }}</div>
                    <div class="col-5 text-muted">Email:</div>
                    <div class="col-7 fw-semibold">{{ $client->email ?: '-' }}</div>
                    <div class="col-5 text-muted">Dirección:</div>
                    <div class="col-7 fw-semibold">{{ $client->address ?: '-' }}</div>
                    @if($client->notes)
                        <div class="col-5 text-muted">Notas:</div>
                        <div class="col-7 fw-semibold">{{ $client->notes }}</div>
                    @endif
                    <div class="col-5 text-muted">Registrado:</div>
                    <div class="col-7 fw-semibold">{{ $client->created_at?->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="row g-3 h-100">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="fs-3 fw-bold text-primary">{{ $client->loans_count }}</div>
                        <div class="small text-muted">Préstamos</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="fs-3 fw-bold text-success">${{ number_format($totalPaid, 2) }}</div>
                        <div class="small text-muted">Total pagado</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="fs-3 fw-bold text-danger">${{ number_format($totalPending, 2) }}</div>
                        <div class="small text-muted">Pendiente</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Loans list --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-cash-coin text-warning me-1"></i> Préstamos del cliente ({{ $loans->count() }})</h6>
        <a href="{{ route('loans.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Nuevo préstamo</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Tasa</th>
                        <th class="text-center">Plazo</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Pendiente</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        @php $loanPending = max(0, round((float) $loan->total_to_pay - (float) $loan->total_paid, 2)); @endphp
                        <tr style="cursor:pointer" onclick="window.location='{{ route('loans.show', $loan) }}'">
                            <td class="ps-4 fw-semibold">{{ $loan->id }}</td>
                            <td>{{ $loan->product?->name ?? '-' }}</td>
                            <td>{{ $loan->creditCategory?->name ?? '-' }}</td>
                            <td class="text-end fw-semibold">${{ number_format($loan->amount, 2) }}</td>
                            <td class="text-center">{{ $loan->interest_rate }}%</td>
                            <td class="text-center">{{ $loan->term_months }}m</td>
                            <td class="text-end text-success">${{ number_format($loan->total_paid, 2) }}</td>
                            <td class="text-end text-danger">${{ number_format($loanPending, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $statusColors[$loan->status] ?? 'secondary' }}">{{ $statusLabels[$loan->status] ?? ucfirst($loan->status) }}</span>
                            </td>
                            <td class="text-end pe-4">{{ $loan->created_at?->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                Este cliente no tiene préstamos todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
