@extends('layouts.app')
@section('title', 'Fidelización · Recompensas')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gift me-2 text-danger"></i>Recompensas</h1>
            <p class="text-muted mb-0 small">Catálogo de premios canjeables por puntos.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('loyalty.rewards.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva recompensa</a>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    @if($catalogUrl)
    {{-- Catálogo público (link para enviar + PDF) --}}
    <div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #7c3aed !important;">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="min-width:240px;">
                    <div class="fw-semibold small mb-1"><i class="bi bi-link-45deg me-1" style="color:#7c3aed;"></i>Catálogo público</div>
                    <div class="input-group input-group-sm">
                        <input type="text" id="catalogLink" class="form-control" value="{{ $catalogUrl }}" readonly onclick="this.select()">
                        <button class="btn btn-light border" type="button" onclick="copyCatalogLink(this)"><i class="bi bi-clipboard"></i> Copiar</button>
                    </div>
                </div>
                <a href="{{ $catalogUrl }}" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-box-arrow-up-right me-1"></i>Ver catálogo</a>
                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#qrModal"><i class="bi bi-qr-code me-1"></i>QR</button>
                <a href="{{ $catalogUrl }}?print=1" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-filetype-pdf me-1"></i>Descargar PDF</a>
            </div>
            <div class="text-muted mt-2" style="font-size:.72rem;"><i class="bi bi-info-circle me-1"></i>Página pública (sin inicio de sesión) con las recompensas activas. Compártela con tus clientes.</div>
        </div>
    </div>
    @endif

    <div class="row g-3">
        @forelse($rewards as $reward)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-0 shadow-sm h-100 {{ $reward->active ? '' : 'opacity-50' }}">
                @if($reward->image_url)
                <img src="{{ $reward->image_url }}" class="card-img-top" style="height:140px;object-fit:cover;" alt="{{ $reward->name }}">
                @else
                <div class="d-flex align-items-center justify-content-center bg-light" style="height:140px;"><i class="bi bi-gift fs-1 text-muted opacity-50"></i></div>
                @endif
                <div class="card-body p-3">
                    <div class="fw-semibold small mb-1">{{ $reward->name }}</div>
                    <div class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">{{ number_format($reward->points_cost, 0) }} pts</div>
                    <div class="text-muted" style="font-size:.72rem;">
                        @if($reward->product) <i class="bi bi-box-seam me-1"></i>{{ $reward->product->name }}<br>@endif
                        Stock: {{ $reward->stock === null ? 'Ilimitado' : $reward->stock }}
                    </div>
                </div>
                <div class="card-footer bg-white border-top d-flex gap-2 py-2">
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.edit', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('loyalty.rewards.edit', $reward) }}" class="btn btn-sm btn-light border flex-grow-1"><i class="bi bi-pencil"></i></a>
                    @endif
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.delete', auth()->user()->getCurrentCompany()))
                    <form action="{{ route('loyalty.rewards.destroy', $reward) }}" method="POST" onsubmit="return confirm('¿Eliminar esta recompensa?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-gift fs-1 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">Aún no hay recompensas. Crea la primera.</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $rewards->links() }}</div>
</div>

@if($catalogUrl)
{{-- Modal QR del catálogo --}}
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code me-2 text-muted"></i>QR del catálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="qrBox" data-url="{{ $catalogUrl }}" class="d-inline-block p-2 border rounded-3 bg-white"></div>
                <div class="text-muted mt-2" style="font-size:.74rem;">Escanéalo para abrir el catálogo público.</div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="downloadQr()"><i class="bi bi-download me-1"></i>Descargar PNG</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function copyCatalogLink(btn) {
    const inp = document.getElementById('catalogLink');
    inp.select();
    navigator.clipboard.writeText(inp.value).then(() => {
        const html = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado';
        setTimeout(() => btn.innerHTML = html, 1800);
    }).catch(() => { document.execCommand('copy'); });
}

(function () {
    const modal = document.getElementById('qrModal');
    if (!modal) return;
    let built = false;
    modal.addEventListener('shown.bs.modal', function () {
        if (built || typeof QRCode === 'undefined') return;
        const box = document.getElementById('qrBox');
        new QRCode(box, { text: box.dataset.url, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });
        built = true;
    });
})();

function downloadQr() {
    const box = document.getElementById('qrBox');
    const canvas = box.querySelector('canvas');
    const img = box.querySelector('img');
    const data = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
    if (!data) return;
    const a = document.createElement('a');
    a.href = data; a.download = 'catalogo-qr.png';
    document.body.appendChild(a); a.click(); a.remove();
}
</script>
@endpush
@endsection
