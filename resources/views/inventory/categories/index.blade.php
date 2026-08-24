@extends('layouts.app')
@section('title', 'Categorías de producto')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-tags me-2 text-danger"></i>Categorías</h1>
            <p class="text-muted mb-0 small">Clasifica los productos de tu catálogo por categoría.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('product-categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva categoría
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
            <form method="GET" action="{{ route('product-categories.index') }}"
                  class="d-flex flex-wrap align-items-center gap-2">
                {{-- Preservar el estado activo al buscar --}}
                @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif

                {{-- Búsqueda --}}
                <div class="input-group input-group-sm flex-grow-1" style="min-width:220px;max-width:360px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" value="{{ $q }}" autocomplete="off"
                           class="form-control border-start-0 ps-0"
                           placeholder="Buscar por nombre, código o descripción...">
                    @if($q)
                    <a href="{{ route('product-categories.index', array_merge(request()->except(['q','page']))) }}"
                       class="btn btn-light border" title="Limpiar búsqueda"><i class="bi bi-x-lg"></i></a>
                    @endif
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                </div>

                {{-- Estado (pills con conteo) --}}
                <div class="d-flex align-items-center gap-1 flex-wrap ms-md-auto">
                    <span class="text-muted small me-1 d-none d-sm-inline">Estado:</span>
                    <a href="{{ route('product-categories.index', array_merge(request()->except(['status','page']))) }}"
                       class="btn btn-sm {{ !$status ? 'btn-dark' : 'btn-light border' }}">
                        Todas <span class="badge bg-white text-dark border ms-1">{{ $counts['all'] }}</span>
                    </a>
                    <a href="{{ route('product-categories.index', array_merge(request()->except('page'), ['status' => 'active'])) }}"
                       class="btn btn-sm {{ $status === 'active' ? 'btn-success' : 'btn-light border' }}">
                        Activas <span class="badge {{ $status === 'active' ? 'bg-white text-success' : 'bg-success-subtle text-success border border-success-subtle' }} ms-1">{{ $counts['active'] }}</span>
                    </a>
                    <a href="{{ route('product-categories.index', array_merge(request()->except('page'), ['status' => 'inactive'])) }}"
                       class="btn btn-sm {{ $status === 'inactive' ? 'btn-secondary' : 'btn-light border' }}">
                        Inactivas <span class="badge {{ $status === 'inactive' ? 'bg-white text-secondary' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} ms-1">{{ $counts['inactive'] }}</span>
                    </a>
                </div>
            </form>

            @if($q || $status)
            <div class="mt-2 d-flex align-items-center gap-2 small text-muted flex-wrap">
                <i class="bi bi-funnel-fill text-primary"></i>
                <span>Mostrando {{ $categories->total() }} resultado(s)</span>
                @if($q)
                <span>para «<strong>{{ $q }}</strong>»</span>
                @endif
                @if($status === 'active')
                <span>· solo activas</span>
                @endif
                @if($status === 'inactive')
                <span>· solo inactivas</span>
                @endif
                <a href="{{ route('product-categories.index') }}" class="text-decoration-none">Limpiar filtros</a>
            </div>
            @endif
        </div>
    </div>

    @php $canEditCat = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.edit', auth()->user()->getCurrentCompany()); @endphp
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead>
                    <tr class="table-light border-bottom">
                        <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Nombre</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Código</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Descripción</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Productos</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr class="border-bottom border-light">
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;">
                                    <i class="bi bi-tag" style="font-size:.72rem;"></i>
                                </div>
                                <span class="fw-semibold">{{ $category->name }}</span>
                            </div>
                        </td>
                        <td class="py-2 code-cell">
                            @if($canEditCat)
                            <input type="text" class="form-control form-control-sm code-edit font-monospace"
                                   value="{{ $category->code }}" data-id="{{ $category->id }}"
                                   data-original="{{ $category->code }}" maxlength="30"
                                   placeholder="—" title="Editar código (Enter o clic fuera para guardar)"
                                   style="max-width:120px;font-size:.75rem;">
                            @elseif($category->code)
                            <span class="badge bg-light text-dark border font-monospace fw-normal" style="font-size:.72rem;">{{ $category->code }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-2 text-muted" style="max-width:280px;">{{ Str::limit($category->description, 70) ?: '—' }}</td>
                        <td class="py-2 text-center">
                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $category->products_count }}</span>
                        </td>
                        <td class="py-2">
                            @if($category->active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activa</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactiva</span>
                            @endif
                        </td>
                        <td class="py-2 text-end pe-3">
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.edit', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-categories.edit', $category) }}" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.delete', auth()->user()->getCurrentCompany()))
                            <form action="{{ route('product-categories.destroy', $category) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar la categoría «{{ addslashes($category->name) }}»?')">
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
                        <td colspan="6" class="text-center py-5 text-muted">
                            @if($q || $status)
                            <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">No se encontraron categorías con esos filtros.</p>
                            <a href="{{ route('product-categories.index') }}" class="btn btn-sm btn-light border mt-3">
                                <i class="bi bi-x-lg me-1"></i>Limpiar filtros
                            </a>
                            @else
                            <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">No hay categorías registradas.</p>
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.create', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-categories.create') }}" class="btn btn-sm btn-primary mt-3">
                                <i class="bi bi-plus-lg me-1"></i>Crear primera categoría
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

    <div class="mt-4 d-flex justify-content-center">{{ $categories->links() }}</div>
</div>

@push('styles')
<style>
    /* Edición inline del código */
    .code-edit {
        border-color: transparent;
        background: transparent;
        transition: background-color .15s, border-color .15s;
    }
    .code-edit:hover  { border-color: #dee2e6; background: #fff; }
    .code-edit:focus  { border-color: #86b7fe; background: #fff; box-shadow: 0 0 0 .18rem rgba(13,110,253,.15); }
    .code-cell.saving { opacity: .55; }
    .code-cell.saved  { background-color: #f0fdf4; transition: background-color .3s ease; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    const CSRF    = '{{ csrf_token() }}';
    const URL_TPL = '{{ route('product-categories.quick-code', '__ID__') }}';

    function toast(msg, ok) {
        let c = document.getElementById('catToast');
        if (!c) {
            c = document.createElement('div');
            c.id = 'catToast';
            c.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;';
            document.body.appendChild(c);
        }
        const color = ok === false ? '#e11d48' : '#16a34a';
        const icon  = ok === false ? 'bi-x-octagon' : 'bi-check-circle';
        const el = document.createElement('div');
        el.style.cssText = 'background:#fff;border-left:4px solid ' + color + ';box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.65rem .85rem;font-size:.83rem;max-width:320px;display:flex;align-items:center;gap:.5rem;';
        el.innerHTML = '<i class="bi ' + icon + '" style="color:' + color + ';"></i><span>' + msg + '</span>';
        c.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 2600);
    }

    function save(input) {
        const id       = input.dataset.id;
        const original = input.dataset.original || '';
        const value    = input.value.trim();
        if (value === original) return;              // sin cambios

        const td = input.closest('td');
        td.classList.add('saving');
        td.classList.remove('saved');

        fetch(URL_TPL.replace('__ID__', id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ code: value === '' ? null : value }),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            td.classList.remove('saving');
            if (!ok || !d.ok) {
                input.value = original;
                toast(d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo guardar el código.'), false);
                return;
            }
            input.value = d.code || '';
            input.dataset.original = d.code || '';
            td.classList.add('saved');
            setTimeout(() => td.classList.remove('saved'), 1600);
            toast(d.code ? 'Código actualizado: ' + d.code : 'Código eliminado.');
        })
        .catch(() => {
            td.classList.remove('saving');
            input.value = original;
            toast('Error de conexión.', false);
        });
    }

    document.querySelectorAll('.code-edit').forEach(function (input) {
        input.addEventListener('change', function () { save(input); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); input.blur(); }      // dispara change
            if (e.key === 'Escape') { input.value = input.dataset.original || ''; input.blur(); }
        });
    });
})();
</script>
@endpush
@endsection
