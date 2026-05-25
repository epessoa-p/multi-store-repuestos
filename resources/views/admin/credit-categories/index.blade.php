@extends('layouts.app')

@section('title', 'Categorías de Crédito')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-tags"></i> Categorías de Crédito</h1>
            <p class="text-muted mb-0">Configura categorías como Vehículos, Joyas, Artículos y Garrafas con reglas flexibles.</p>
        </div>
        <a href="{{ route('credit-categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva categoría</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Empresa</th>
                            <th class="text-end">Reglas</th>
                            <th>Monto Min/Max</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td><strong>{{ $category->name }}</strong><br><small class="text-muted">{{ $category->slug }}</small></td>
                                <td>{{ $category->company?->name ?: '-' }}</td>
                                <td class="text-end">{{ $category->rules->count() }}</td>
                                <td>${{ number_format((float) ($category->min_amount ?? 0), 2) }} / ${{ number_format((float) ($category->max_amount ?? 0), 2) }}</td>
                                <td><span class="badge {{ $category->active ? 'bg-success' : 'bg-secondary' }}">{{ $category->active ? 'Activa' : 'Inactiva' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('credit-categories.show', $category) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('credit-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('credit-categories.destroy', $category) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar categoría?')"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted">No hay categorías registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $categories->links() }}</div>
</div>
@endsection
