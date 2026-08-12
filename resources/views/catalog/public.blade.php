@extends('layouts.public')
@section('title', ($company?->name ?? 'Catálogo') . ' · ' . $branch->name)

@section('topbar-actions')
<a id="btnPdf" href="{{ route('catalog.public.pdf', $branch->catalog_token) }}"
   class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1 no-print">
    <i class="bi bi-download"></i><span class="d-none d-sm-inline">Descargar PDF</span>
</a>
@endsection

@section('content')
<div class="container py-3">

    {{-- Aviso de precios oficiales --}}
    <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mb-3 no-print" style="font-size:.82rem;">
        <i class="bi bi-shield-check text-success fs-6"></i>
        <span>Estos son los <strong>precios oficiales</strong> de {{ $company?->name ?? 'la tienda' }}. Consulta libre.</span>
    </div>

    {{-- Filtros --}}
    <div class="row g-2 mb-3 no-print">
        <div class="col-12 col-md">
            <div class="input-group input-group-sm shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="catSearch" class="form-control border-start-0"
                       placeholder="Buscar producto…" autocomplete="off" style="box-shadow:none;">
            </div>
        </div>
        @if($categories->count())
        <div class="col-12 col-md-auto">
            <form method="GET" id="catFilterForm">
                <select name="category" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()"
                        style="min-width:180px;">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ (string) $categoryId === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        @endif
    </div>

    {{-- Grid de productos --}}
    <div class="row g-2 g-md-3" id="catGrid">
        @forelse($products as $product)
        @php
            $availHere = ($stock[$branch->warehouse_id][$product->id] ?? 0) > 0;
            $photo     = $product->photos->firstWhere('is_main', true) ?? $product->photos->first();
        @endphp
        <div class="col-6 col-md-4 col-lg-3 cat-item" data-name="{{ \Illuminate\Support\Str::lower($product->name . ' ' . $product->sku . ' ' . $product->code) }}">
            <div class="card h-100 border-0 shadow-sm">
                <div class="ratio ratio-1x1 bg-white rounded-top overflow-hidden d-flex align-items-center justify-content-center">
                    @if($photo)
                        <img src="{{ $photo->url }}" alt="{{ $product->name }}" loading="lazy" style="object-fit:contain;width:100%;height:100%;padding:6px;">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-muted h-100 w-100" style="background:#f0f1f3;">
                            <i class="bi bi-box-seam" style="font-size:1.8rem;opacity:.4;"></i>
                        </div>
                    @endif
                </div>
                <div class="card-body p-2 d-flex flex-column">
                    <div class="fw-semibold lh-sm mb-1" style="font-size:.82rem;">{{ $product->name }}</div>
                    <div class="text-muted mb-2" style="font-size:.68rem;">
                        {{ $product->category?->name }}@if($product->brand) · {{ $product->brand->name }}@endif
                    </div>
                    <div class="mt-auto">
                        <div class="fw-bold text-dark mb-1" style="font-size:1.02rem;">Bs {{ number_format($product->price, 2) }}</div>
                        @if($availHere)
                        <span class="badge bg-success-subtle text-success border border-success-subtle w-100 py-1" style="font-size:.66rem;">
                            <i class="bi bi-check-circle-fill me-1"></i>Disponible
                        </span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle w-100 py-1" style="font-size:.66rem;">
                            <i class="bi bi-x-circle me-1"></i>Agotado aquí
                        </span>
                        @endif

                        @if($branches->count() > 1)
                        <button class="btn btn-link btn-sm p-0 mt-1 text-decoration-none w-100 text-start" type="button"
                                data-bs-toggle="collapse" data-bs-target="#branches-{{ $product->id }}"
                                style="font-size:.68rem;">
                            <i class="bi bi-buildings me-1"></i>Otras sucursales
                        </button>
                        <div class="collapse" id="branches-{{ $product->id }}">
                            <div class="border rounded p-2 mt-1" style="font-size:.68rem;background:#fafafa;">
                                @foreach($branches as $b)
                                @php $ok = ($stock[$b->warehouse_id][$product->id] ?? 0) > 0; @endphp
                                <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-1' : '' }}">
                                    <span class="text-truncate d-inline-flex align-items-center gap-1" style="max-width:120px;">
                                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $b->color ?: '#6c757d' }};display:inline-block;"></span>
                                        {{ $b->name }}@if($b->id === $branch->id) <span class="text-muted">(aquí)</span>@endif
                                    </span>
                                    @if($ok)
                                        <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill"></i></span>
                                    @else
                                        <span class="text-muted"><i class="bi bi-x-circle"></i></span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-5">
            <i class="bi bi-box-seam" style="font-size:2.4rem;opacity:.3;"></i>
            <p class="mb-0 mt-2">No hay productos para mostrar.</p>
        </div>
        @endforelse
    </div>

    {{-- Sin resultados (búsqueda cliente) --}}
    <div id="catNoResults" class="text-center text-muted py-5 d-none">
        <i class="bi bi-search" style="font-size:2rem;opacity:.3;"></i>
        <p class="mb-0 mt-2">Sin resultados para tu búsqueda.</p>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var search    = document.getElementById('catSearch');
    var items     = Array.prototype.slice.call(document.querySelectorAll('.cat-item'));
    var noResults = document.getElementById('catNoResults');
    var btnPdf    = document.getElementById('btnPdf');
    var basePdf   = btnPdf ? btnPdf.getAttribute('href') : '';
    var category  = @json((string) ($categoryId ?? ''));

    function updatePdfLink(q) {
        if (!btnPdf) return;
        var params = [];
        if (category) params.push('category=' + encodeURIComponent(category));
        if (q)        params.push('q=' + encodeURIComponent(q));
        btnPdf.setAttribute('href', basePdf + (params.length ? ('?' + params.join('&')) : ''));
    }

    function applyFilter() {
        var q = (search.value || '').trim().toLowerCase();
        var visible = 0;
        items.forEach(function (el) {
            var match = !q || (el.getAttribute('data-name') || '').indexOf(q) !== -1;
            el.classList.toggle('d-none', !match);
            if (match) visible++;
        });
        if (noResults) noResults.classList.toggle('d-none', visible !== 0 || items.length === 0);
        updatePdfLink(q);
    }

    if (search) search.addEventListener('input', applyFilter);
    updatePdfLink('');
})();
</script>
@endpush
