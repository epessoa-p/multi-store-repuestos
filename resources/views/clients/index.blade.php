@extends('layouts.app')

@section('title', 'Clientes - Sistema de Préstamos')

@section('page')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-person-vcard"></i> Clientes</h1>
            <p class="text-muted mb-0">Gestiona tu cartera de clientes y su historial de préstamos.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2">
                {{ $clients->total() }} clientes encontrados
            </span>
            <a href="{{ route('loans.create') }}" class="btn btn-success">
                <i class="bi bi-cash-coin"></i> Nuevo préstamo
            </a>
            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nuevo cliente
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('clients.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="q" value="{{ $search }}" placeholder="Buscar por nombre, documento, teléfono o correo">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary w-100">
                        Limpiar
                    </a>
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
                            <th class="ps-4">Cliente</th>
                            <th>Documento</th>
                            <th>Contacto</th>
                            @if(auth()->user()->is_super_admin)
                                <th>Empresa</th>
                            @endif
                            <th class="text-end">Préstamos</th>
                            <th class="text-end">Total Prestado</th>
                            <th class="text-end pe-4">Total Pagado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('clients.show', $client) }}'">
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $client->name }}</div>
                                    <small class="text-muted">{{ $client->active ? 'Activo' : 'Inactivo' }}</small>
                                </td>
                                <td>{{ $client->id_number ?: '-' }}</td>
                                <td>
                                    <div>{{ $client->phone ?: '-' }}</div>
                                    <small class="text-muted">{{ $client->email ?: '-' }}</small>
                                </td>
                                @if(auth()->user()->is_super_admin)
                                    <td>
                                        <span class="badge text-bg-light border">{{ $client->company?->name }}</span>
                                    </td>
                                @endif
                                <td class="text-end fw-semibold">{{ $client->loans_count }}</td>
                                <td class="text-end">${{ number_format((float) $client->total_borrowed, 2) }}</td>
                                <td class="text-end pe-4">${{ number_format((float) $client->total_paid, 2) }}</td>
                                <td class="text-end pe-3" onclick="event.stopPropagation()">
                                    <a href="{{ route('loans.create', ['client_id' => $client->id]) }}" class="btn btn-sm btn-outline-success" title="Crear préstamo para este cliente">
                                        <i class="bi bi-cash-coin"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->is_super_admin ? 8 : 7 }}" class="text-center py-5 text-muted">
                                    No hay clientes registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($clients->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $clients->links() }}
        </div>
    @endif
</div>
@endsection
