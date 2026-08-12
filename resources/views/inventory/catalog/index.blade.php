@extends('layouts.app')
@section('title', 'Catálogo público')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-5">
                <i class="bi bi-qr-code me-2 text-danger"></i>Catálogo público
            </h1>
            <p class="text-muted mb-0 small">
                Comparte el catálogo de cada sucursal por link o QR. Los clientes ven productos, precios y disponibilidad — solo consulta, sin iniciar sesión.
            </p>
        </div>
    </div>

    @if($branches->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-buildings fs-1 d-block mb-2 opacity-25"></i>
                <p class="mb-0">No hay sucursales registradas.</p>
            </div>
        </div>
    @else
    <div class="row g-3">
        @foreach($branches as $branch)
        @php $url = $branch->catalogUrl(); @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 {{ $branch->catalog_enabled ? '' : 'opacity-75' }}">
                <div class="card-body">
                    {{-- Cabecera sucursal --}}
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <span class="d-inline-flex align-items-center gap-2 fw-semibold text-truncate">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $branch->color ?: '#6c757d' }};display:inline-block;flex-shrink:0;"></span>
                            <span class="text-truncate">{{ $branch->name }}</span>
                        </span>
                        @if($branch->catalog_enabled)
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;">
                                <i class="bi bi-broadcast me-1"></i>Activo
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.65rem;">
                                <i class="bi bi-slash-circle me-1"></i>Inactivo
                            </span>
                        @endif
                    </div>

                    {{-- QR --}}
                    <div class="text-center mb-3">
                        <div class="d-inline-block p-2 bg-white border rounded-3 qr-box" data-qr-url="{{ $url }}"
                             style="line-height:0;{{ $branch->catalog_enabled ? '' : 'filter:grayscale(1);opacity:.6;' }}"></div>
                    </div>

                    {{-- Link + copiar --}}
                    <div class="input-group input-group-sm mb-3">
                        <input type="text" class="form-control bg-light" value="{{ $url }}" readonly
                               onclick="this.select()" style="font-size:.72rem;">
                        <button class="btn btn-outline-secondary btn-copy" type="button" data-url="{{ $url }}" title="Copiar enlace">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>

                    {{-- Acciones --}}
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-primary flex-fill {{ $branch->catalog_enabled ? '' : 'disabled' }}">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Ver
                        </a>
                        <a href="{{ route('catalog.public.pdf', $branch->catalog_token) }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-light border flex-fill {{ $branch->catalog_enabled ? '' : 'disabled' }}">
                            <i class="bi bi-download me-1"></i>PDF
                        </a>
                        <button type="button" class="btn btn-sm btn-light border flex-fill btn-print-qr"
                                data-url="{{ $url }}" data-branch="{{ $branch->name }}">
                            <i class="bi bi-printer me-1"></i>Imprimir QR
                        </button>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex align-items-center justify-content-between gap-2">
                        {{-- Activar / desactivar --}}
                        <form method="POST" action="{{ route('inventory.catalog.toggle', $branch) }}" class="m-0">
                            @csrf
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="sw-{{ $branch->id }}" {{ $branch->catalog_enabled ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <label class="form-check-label small text-muted" for="sw-{{ $branch->id }}">
                                    {{ $branch->catalog_enabled ? 'Publicado' : 'Oculto' }}
                                </label>
                            </div>
                        </form>

                        {{-- Regenerar enlace --}}
                        <form method="POST" action="{{ route('inventory.catalog.regenerate', $branch) }}" class="m-0"
                              onsubmit="return confirm('¿Generar un enlace nuevo para {{ $branch->name }}? El enlace y QR actuales dejarán de funcionar.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0" style="font-size:.75rem;">
                                <i class="bi bi-arrow-repeat me-1"></i>Regenerar enlace
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
<script>
(function () {
    // Render de QR por sucursal
    function renderQrs() {
        if (typeof QRCode === 'undefined') return;
        document.querySelectorAll('.qr-box').forEach(function (box) {
            if (box.dataset.done === '1') return;
            box.dataset.done = '1';
            new QRCode(box, {
                text: box.dataset.qrUrl,
                width: 150,
                height: 150,
                correctLevel: QRCode.CorrectLevel.M
            });
        });
    }
    renderQrs();

    // Copiar enlace
    document.querySelectorAll('.btn-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = this.dataset.url;
            var icon = this.querySelector('i');
            var done = function () {
                if (!icon) return;
                icon.className = 'bi bi-check2 text-success';
                setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(done).catch(done);
            } else {
                var t = document.createElement('textarea');
                t.value = url; document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(t); done();
            }
        });
    });

    // Imprimir QR (ventana con el QR + nombre + enlace)
    document.querySelectorAll('.btn-print-qr').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var box = this.closest('.card').querySelector('.qr-box');
            var img = box ? (box.querySelector('img') || box.querySelector('canvas')) : null;
            if (!img) return;
            var src = img.tagName === 'CANVAS' ? img.toDataURL('image/png') : img.src;
            var branch = this.dataset.branch;
            var url = this.dataset.url;
            var w = window.open('', '_blank', 'width=420,height=560');
            if (!w) return;
            w.document.write(
                '<html><head><title>QR ' + branch + '</title></head>' +
                '<body style="font-family:Inter,Segoe UI,sans-serif;text-align:center;padding:24px;">' +
                '<h2 style="margin:0 0 4px;">' + branch + '</h2>' +
                '<p style="color:#666;margin:0 0 16px;font-size:13px;">Escanea para ver el catálogo</p>' +
                '<img src="' + src + '" style="width:260px;height:260px;">' +
                '<p style="color:#999;font-size:10px;word-break:break-all;margin-top:14px;">' + url + '</p>' +
                '<scr' + 'ipt>window.onload=function(){setTimeout(function(){window.print();},200);}</scr' + 'ipt>' +
                '</body></html>'
            );
            w.document.close();
        });
    });
})();
</script>
@endpush
