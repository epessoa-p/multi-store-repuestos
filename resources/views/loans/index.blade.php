@extends('layouts.app')

@push('styles')
<style>
    /* ── Branch Selector ─────────────────────────────────── */
    .branch-selector {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .branch-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem 1.1rem;
        border-radius: 2rem;
        font-size: .85rem;
        font-weight: 500;
        border: 1.5px solid #dee2e6;
        background: #fff;
        color: #495057;
        text-decoration: none;
        transition: all .18s ease;
        white-space: nowrap;
    }
    .branch-btn:hover {
        border-color: #0d6efd;
        color: #0d6efd;
        background: #f0f5ff;
        text-decoration: none;
    }
    .branch-btn.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
        box-shadow: 0 3px 10px rgba(13,110,253,.28);
    }
    .branch-btn .branch-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: currentColor;
        opacity: .6;
        flex-shrink: 0;
    }
    .branch-btn.active .branch-dot { opacity: 1; background: rgba(255,255,255,.85); }

    /* ── Filter Card ─────────────────────────────────────── */
    .filter-card {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: .75rem;
    }
</style>
@endpush

@section('page')
{{-- ── Header ─────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-0 fs-4 fw-bold"><i class="bi bi-cash-coin text-primary me-2"></i>Préstamos</h1>
    </div>
    @if(auth()->user()->hasPermissionInCompany('loans.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('loans.create', ['sucursal_id' => $selectedBranchId]) }}" class="btn btn-primary btn-sm px-3">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Préstamo
        </a>
    @endif
</div>

{{-- ── Branch Selector ────────────────────────────────────── --}}
@if($branches->isNotEmpty())
<div class="mb-3">
    <span class="text-muted small me-2"><i class="bi bi-geo-alt me-1"></i>Sucursal:</span>
    <div class="branch-selector d-inline-flex">
        @foreach($branches as $branch)
            <a class="branch-btn {{ (int) $selectedBranchId === (int) $branch->id ? 'active' : '' }}"
               href="{{ route('loans.index', array_merge(request()->except(['page', 'sucursal_id']), ['sucursal_id' => $branch->id])) }}">
                <span class="branch-dot"></span>
                {{ $branch->name }}
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- ── Filters ────────────────────────────────────────────── --}}
<div class="card filter-card mb-3 border-0">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('loans.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="sucursal_id" value="{{ $selectedBranchId }}">

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Búsqueda rápida</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" value="{{ $q }}" placeholder="ID, cliente o producto">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach(['pending' => 'Pendiente', 'approved' => 'Aprobado', 'active' => 'Activo', 'finished' => 'Finalizado', 'cancelled' => 'Cancelado', 'overdue' => 'Vencido'] as $value => $label)
                        <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Producto</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (int) $productId === (int) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Desde</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Hasta</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>

            <div class="col-12 d-flex gap-2 mt-1">
                <button class="btn btn-primary btn-sm px-4" type="submit">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                <a href="{{ route('loans.index', ['sucursal_id' => $selectedBranchId]) }}" class="btn btn-light btn-sm border">
                    <i class="bi bi-x-circle me-1"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">ID</th>
                    <th>Cliente</th>
                    <th>Producto</th>
                    <th>Monto</th>
                    <th>Tasa</th>
                    <th>Plazo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr style="cursor:pointer" onclick="window.location='{{ route('loans.show', $loan) }}'">
                        <td class="ps-3"><strong>#{{ $loan->id }}</strong></td>
                        <td>{{ $loan->client?->name ?? 'Sin cliente' }}</td>
                        <td>{{ $loan->product?->name ?? '-' }}</td>
                        <td>${{ number_format($loan->amount, 2) }}</td>
                        <td>{{ $loan->interest_rate }}%</td>
                        <td>{{ $loan->term_months }} meses</td>
                        <td>
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'pending' => 'warning',
                                    'approved' => 'info',
                                    'finished' => 'secondary',
                                    'cancelled' => 'dark',
                                    'overdue' => 'danger',
                                ];
                                $statusLabels = [
                                    'active' => 'Activo',
                                    'pending' => 'Pendiente',
                                    'approved' => 'Aprobado',
                                    'finished' => 'Finalizado',
                                    'cancelled' => 'Cancelado',
                                    'overdue' => 'Vencido',
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$loan->status] ?? 'secondary' }}">
                                {{ $statusLabels[$loan->status] ?? ucfirst($loan->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No hay préstamos registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

        @if($loans->hasPages())
            <div class="d-flex justify-content-center">
                {{ $loans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
