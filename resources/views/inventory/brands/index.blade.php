@extends('layouts.app')
@section('title', 'Marcas de producto')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-award me-2 text-danger"></i>Marcas</h1>
            <p class="text-muted mb-0 small">Gestiona las marcas de los repuestos y productos.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-brands.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('product-brands.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva marca
        </a>
        @endif
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        @foreach($errors->all() as $e){{ $e }}@endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ── Filtros ─────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-3 px-md-4">
            <form method="GET" action="{{ route('product-brands.index') }}"
                  class="d-flex flex-wrap align-items-center gap-2">
                {{-- Preservar el estado activo al buscar --}}
                @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif

                {{-- Búsqueda --}}
                <div class="input-group input-group-sm flex-grow-1" style="min-width:220px;max-width:360px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" value="{{ $q }}" autocomplete="off"
                           class="form-control border-start-0 ps-0"
                           placeholder="Buscar por nombre o descripción...">
                    @if($q)
                    <a href="{{ route('product-brands.index', array_merge(request()->except(['q','page']))) }}"
                       class="btn btn-light border" title="Limpiar búsqueda"><i class="bi bi-x-lg"></i></a>
                    @endif
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                </div>

                {{-- Estado (pills con conteo) --}}
                <div class="d-flex align-items-center gap-1 flex-wrap ms-md-auto">
                    <span class="text-muted small me-1 d-none d-sm-inline">Estado:</span>
                    <a href="{{ route('product-brands.index', array_merge(request()->except(['status','page']))) }}"
                       class="btn btn-sm {{ !$status ? 'btn-dark' : 'btn-light border' }}">
                        Todas <span class="badge bg-white text-dark border ms-1">{{ $counts['all'] }}</span>
                    </a>
                    <a href="{{ route('product-brands.index', array_merge(request()->except('page'), ['status' => 'active'])) }}"
                       class="btn btn-sm {{ $status === 'active' ? 'btn-success' : 'btn-light border' }}">
                        Activas <span class="badge {{ $status === 'active' ? 'bg-white text-success' : 'bg-success-subtle text-success border border-success-subtle' }} ms-1">{{ $counts['active'] }}</span>
                    </a>
                    <a href="{{ route('product-brands.index', array_merge(request()->except('page'), ['status' => 'inactive'])) }}"
                       class="btn btn-sm {{ $status === 'inactive' ? 'btn-secondary' : 'btn-light border' }}">
                        Inactivas <span class="badge {{ $status === 'inactive' ? 'bg-white text-secondary' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} ms-1">{{ $counts['inactive'] }}</span>
                    </a>
                </div>
            </form>

            @if($q || $status)
            <div class="mt-2 d-flex align-items-center gap-2 small text-muted flex-wrap">
                <i class="bi bi-funnel-fill text-primary"></i>
                <span>Mostrando {{ $brands->total() }} resultado(s)</span>
                @if($q)
                <span>para «<strong>{{ $q }}</strong>»</span>
                @endif
                @if($status === 'active')
                <span>· solo activas</span>
                @endif
                @if($status === 'inactive')
                <span>· solo inactivas</span>
                @endif
                <a href="{{ route('product-brands.index') }}" class="text-decoration-none">Limpiar filtros</a>
            </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead>
                    <tr class="table-light border-bottom">
                        <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Nombre</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Descripción</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Productos</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                    <tr class="border-bottom border-light">
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;">
                                    <i class="bi bi-award" style="font-size:.72rem;"></i>
                                </div>
                                <span class="fw-semibold">{{ $brand->name }}</span>
                            </div>
                        </td>
                        <td class="py-2 text-muted" style="max-width:280px;">{{ Str::limit($brand->description, 70) ?: '—' }}</td>
                        <td class="py-2 text-center">
                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $brand->products_count }}</span>
                        </td>
                        <td class="py-2">
                            @if($brand->active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activa</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactiva</span>
                            @endif
                        </td>
                        <td class="py-2 text-end pe-3">
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-brands.edit', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-brands.edit', $brand) }}" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-brands.delete', auth()->user()->getCurrentCompany()))
                            <form action="{{ route('product-brands.destroy', $brand) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar la marca «{{ addslashes($brand->name) }}»?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light border text-danger py-0 px-2" title="Eliminar" style="font-size:.8rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            @if($q || $status)
                            <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">No se encontraron marcas con esos filtros.</p>
                            <a href="{{ route('product-brands.index') }}" class="btn btn-sm btn-light border mt-3">
                                <i class="bi bi-x-lg me-1"></i>Limpiar filtros
                            </a>
                            @else
                            <i class="bi bi-award fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">No hay marcas registradas.</p>
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-brands.create', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-brands.create') }}" class="btn btn-sm btn-primary mt-3">
                                <i class="bi bi-plus-lg me-1"></i>Crear primera marca
                            </a>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $brands->links() }}</div>
</div>
@endsection
