@extends('layouts.app')
@section('title', 'Productos')
@section('page')
@php
    $canEditProducts = auth()->user()->is_super_admin
        || auth()->user()->hasPermissionInCompany('products.edit', auth()->user()->getCurrentCompany());
    $hasFilters = $q !== '' || $categoryId || $brandId || $status || $low;
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-seam me-2 text-danger"></i>Productos</h1>
            <p class="text-muted mb-0 small">Catálogo completo de repuestos y accesorios.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.create', auth()->user()->getCurrentCompany()))
        <div class="d-flex gap-2">
            <a href="{{ route('products.import') }}" class="btn btn-light border">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Importar Excel
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nuevo producto
            </a>
        </div>
        @endif
    </div>

    {{-- ── Filtros ─────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-3 px-md-4">
            <form method="GET" action="{{ route('products.index') }}" id="prodFilterForm" class="row g-2 align-items-end">
                <div class="col-12 col-md">
                    <label class="form-label small fw-semibold text-muted mb-1">Buscar</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" value="{{ $q }}" autocomplete="off"
                               class="form-control border-start-0 ps-0"
                               placeholder="Buscar por nombre, SKU, código...">
                        @if($q !== '')
                        <a href="{{ route('products.index', array_merge(request()->except(['q','page']))) }}"
                           class="btn btn-light border" title="Limpiar búsqueda"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <label class="form-label small fw-semibold text-muted mb-1">Categoría</label>
                    <select name="category_id" class="form-select form-select-sm js-autosubmit" data-placeholder="Todas">
                        <option value="">Todas</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-auto">
                    <label class="form-label small fw-semibold text-muted mb-1">Marca</label>
                    <select name="brand_id" class="form-select form-select-sm js-autosubmit" data-placeholder="Todas">
                        <option value="">Todas</option>
                        @foreach($brands as $br)
                        <option value="{{ $br->id }}" {{ (string) $brandId === (string) $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-auto">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" data-no-search>
                        <option value="">Todos</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                <div class="col-6 col-md-auto">
                    <div class="form-check mt-md-4">
                        <input class="form-check-input" type="checkbox" id="lowFilter" name="low" value="1"
                               {{ $low ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label small" for="lowFilter"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Stock bajo</label>
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                </div>
            </form>

            @if($hasFilters)
            <div class="mt-2 d-flex align-items-center gap-2 small text-muted flex-wrap">
                <i class="bi bi-funnel-fill text-primary"></i>
                <span>Mostrando {{ $products->total() }} producto(s) con los filtros aplicados.</span>
                <a href="{{ route('products.index') }}" class="text-decoration-none">Limpiar filtros</a>
            </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;width:40px;font-size:.72rem;"></th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Producto</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Categoría</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Marca</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Stock</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Precio</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        @php $mainPhoto = $product->mainPhoto(); @endphp
                        <tr class="border-bottom border-light">
                            <td class="ps-3 py-2">
                                @if($mainPhoto)
                                <img src="{{ $mainPhoto->url }}" alt="{{ $product->name }}"
                                     class="rounded-2 border object-fit-cover"
                                     style="width:30px;height:30px;object-fit:cover;">
                                @else
                                <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center fw-bold text-muted"
                                     style="width:30px;height:30px;font-size:.75rem;">
                                    {{ strtoupper(substr($product->name, 0, 1)) }}
                                </div>
                                @endif
                            </td>
                            <td class="py-2">
                                <a href="{{ route('products.show', $product) }}" class="text-decoration-none text-dark fw-semibold d-block lh-sm">
                                    {{ $product->name }}
                                </a>
                                <span class="text-muted" style="font-size:.75rem;">{{ $product->sku }}</span>
                            </td>
                            <td class="py-2 cell-edit-td" style="min-width:150px;">
                                @if($canEditProducts)
                                <select class="cell-edit form-select form-select-sm" data-field="category_id"
                                        data-product="{{ $product->id }}" data-original="{{ $product->category_id }}"
                                        data-placeholder="Sin categoría">
                                    <option value="">—</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string) $product->category_id === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @elseif($product->category)
                                <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $product->category->name }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2 cell-edit-td" style="min-width:150px;">
                                @if($canEditProducts)
                                <select class="cell-edit form-select form-select-sm" data-field="brand_id"
                                        data-product="{{ $product->id }}" data-original="{{ $product->brand_id }}"
                                        data-placeholder="Sin marca">
                                    <option value="">—</option>
                                    @foreach($brands as $br)
                                    <option value="{{ $br->id }}" {{ (string) $product->brand_id === (string) $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                                    @endforeach
                                </select>
                                @elseif($product->brand)
                                <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $product->brand->name }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-center">
                                @php
                                    $stock    = (float) $product->current_stock;
                                    $minStock = (float) ($product->min_stock ?? 0);
                                    $low      = $minStock > 0 && $stock <= $minStock;
                                @endphp
                                <span class="badge {{ $low ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }}" style="font-size:.75rem;">
                                    {{ number_format($stock, 0) }}
                                </span>
                                @if($low)
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:.7rem;" title="Stock bajo (mín: {{ $product->min_stock }})"></i>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-semibold">
                                ${{ number_format($product->price, 2) }}
                            </td>
                            <td class="py-2">
                                @if($product->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-3">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-light border py-0 px-2" title="Ver" style="font-size:.8rem;">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar «{{ addslashes($product->name) }}»?')">
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
                            <td colspan="8" class="text-center py-5 text-muted">
                                @if($hasFilters)
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No se encontraron productos con esos filtros.</p>
                                <a href="{{ route('products.index') }}" class="btn btn-sm btn-light border mt-3">
                                    <i class="bi bi-x-lg me-1"></i>Limpiar filtros
                                </a>
                                @else
                                <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay productos registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primer producto
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
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $products->links() }}</div>
</div>

@push('styles')
<style>
    /* Celdas Categoría/Marca (select2): pulso de guardado */
    .cell-edit-td { transition: background-color .3s ease; }
    .cell-edit-td.saved { background-color: #f0fdf4; }
    .cell-edit-td.saving { opacity: .55; }
    /* Que el select2 de la celda no quede demasiado angosto */
    .cell-edit-td .select2-container { min-width: 130px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    if (!window.jQuery) return;
    const $ = window.jQuery;
    const CSRF = '{{ csrf_token() }}';
    const URL_TPL = '{{ route('products.quick-field', '__ID__') }}';

    function toast(msg, ok) {
        let c = document.getElementById('prodToast');
        if (!c) {
            c = document.createElement('div');
            c.id = 'prodToast';
            c.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;';
            document.body.appendChild(c);
        }
        const color = ok === false ? '#e11d48' : '#16a34a';
        const icon  = ok === false ? 'bi-x-octagon' : 'bi-check-circle';
        const el = document.createElement('div');
        el.style.cssText = 'background:#fff;border-left:4px solid ' + color + ';box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.65rem .85rem;font-size:.83rem;max-width:320px;display:flex;align-items:center;gap:.5rem;';
        el.innerHTML = '<i class="bi ' + icon + '" style="color:' + color + ';"></i><span>' + msg + '</span>';
        c.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 2800);
    }

    // Auto-submit de los filtros con select2 (jQuery capta el change de select2).
    $(document).on('change', '#prodFilterForm .js-autosubmit', function () {
        this.form.submit();
    });

    // Edición inline de categoría / marca (delegado + jQuery para select2).
    $(document).on('change', '.cell-edit', function () {
        const $sel     = $(this);
        const field    = this.dataset.field;
        const id        = this.dataset.product;
        const value     = this.value;
        const original  = this.dataset.original || '';
        const labelTxt  = field === 'category_id' ? 'Categoría' : 'Marca';
        const $td       = $sel.closest('td');

        $td.addClass('saving').removeClass('saved');

        fetch(URL_TPL.replace('__ID__', id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ field: field, value: value === '' ? null : value }),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            $td.removeClass('saving');
            if (!ok || !d.ok) {
                $sel.val(original).trigger('change.select2');
                toast(d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo actualizar.'), false);
                return;
            }
            $sel[0].dataset.original = d.value || '';
            $td.addClass('saved');
            setTimeout(() => $td.removeClass('saved'), 1600);
            toast(labelTxt + ' actualizada' + (d.label && d.label !== '—' ? ': ' + d.label : ' (sin ' + labelTxt.toLowerCase() + ')'));
        })
        .catch(() => {
            $td.removeClass('saving');
            $sel.val(original).trigger('change.select2');
            toast('Error de conexión.', false);
        });
    });
})();
</script>
@endpush
@endsection
