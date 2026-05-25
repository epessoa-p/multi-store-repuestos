@extends('layouts.app')

@section('title', 'Detalle Categoría de Crédito')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">{{ $creditCategory->name }}</h1>
            <p class="text-muted mb-0">Detalle de reglas y límites de la categoría.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('credit-categories.edit', $creditCategory) }}" class="btn btn-primary">Editar</a>
            <a href="{{ route('credit-categories.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p><strong>Empresa:</strong> {{ $creditCategory->company?->name }}</p>
                    <p><strong>Slug:</strong> {{ $creditCategory->slug }}</p>
                    <p><strong>Monto mínimo:</strong> ${{ number_format((float) ($creditCategory->min_amount ?? 0), 2) }}</p>
                    <p><strong>Monto máximo:</strong> ${{ number_format((float) ($creditCategory->max_amount ?? 0), 2) }}</p>
                    <p><strong>Estado:</strong> {{ $creditCategory->active ? 'Activa' : 'Inactiva' }}</p>
                    <p class="mb-0"><strong>Descripción:</strong> {{ $creditCategory->description ?: '-' }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Reglas de crédito</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tasa</th>
                                    <th>Periodicidad</th>
                                    <th>Límite de plazo</th>
                                    <th>Monto Min/Max</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($creditCategory->rules as $rule)
                                    <tr>
                                        <td>{{ $rule->name ?: 'Regla #' . $rule->id }}</td>
                                        <td>{{ number_format((float) $rule->interest_rate, 2) }}%</td>
                                        <td>{{ ucfirst($rule->interest_period) }}</td>
                                        <td>{{ $rule->term_months_limit }} meses</td>
                                        <td>${{ number_format((float) ($rule->min_amount ?? 0), 2) }} / ${{ number_format((float) ($rule->max_amount ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No hay reglas definidas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
