@extends('layouts.app')
@section('title', 'Punto de venta')
@section('page')

@if(!$session)
{{-- ─── EMPTY STATE: no open register ─────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-center" style="min-height:65vh;">
    <div class="text-center px-4" style="max-width:420px;">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
             style="width:90px;height:90px;background:rgba(225,6,0,.07);">
            <i class="bi bi-safe2 text-danger" style="font-size:2.4rem;"></i>
        </div>
        <h4 class="fw-bold mb-2">Caja cerrada</h4>
        <p class="text-muted mb-4">Debes abrir tu caja registradora antes de operar el punto de venta.</p>
        <a href="{{ route('cash-registers.index') }}" class="btn btn-primary px-5">
            <i class="bi bi-safe2 me-2"></i>Ir a Cajas
        </a>
    </div>
</div>

@else
{{-- ─── FULL POS LAYOUT ─────────────────────────────────────────────── --}}

@php
    $productsData = $products->map(function($p) {
        $photos = [];
        foreach ($p->photos as $ph) {
            $photos[] = $ph->url;
        }
        return [
            'id'               => $p->id,
            'name'             => $p->name,
            'sku'              => $p->sku,
            'price'            => (float) $p->price,
            'stock'            => (float) $p->current_stock,
            'category'         => $p->category?->name ?? '',
            'category_id'      => $p->category_id,
            'brand'            => $p->brand?->name ?? '',
            'description'      => $p->description ?? '',
            'compatible_models'=> $p->motoModels->pluck('display_name')->implode(', '),
            'model_ids'        => $p->motoModels->pluck('id')->values()->all(),
            'photo'            => count($photos) > 0 ? $photos[0] : null,
            'photos'           => $photos,
        ];
    })->values();

    $clientsJson = $clients->map(function($c) {
        return [
            'id'        => $c->id,
            'name'      => $c->full_name,
            'id_number' => $c->id_number ?? '',
        ];
    })->values();
@endphp

<div class="pos-wrapper">

    {{-- ── TOP BAR ────────────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-shrink-0 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div>
                <h1 class="mb-0 fw-bold fs-5"><i class="bi bi-cart3 me-2 text-danger"></i>Punto de Venta</h1>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle d-flex align-items-center gap-1 px-3 py-2">
                <span class="rounded-circle bg-success" style="width:7px;height:7px;display:inline-block;"></span>
                {{ $session->cashRegister->branch->name ?? 'Sucursal' }}
                &nbsp;&middot;&nbsp;
                {{ $session->cashRegister->name ?? 'Caja' }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-list-ul me-1"></i>Ventas
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show flex-shrink-0" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-3">

        {{-- ── LEFT: PRODUCT GRID ──────────────────────────────────────── --}}
        <div class="col-lg-8">
            {{-- Search --}}
            <div class="mb-2">
                <div class="input-group input-group-sm shadow-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="productSearch" class="form-control border-start-0 ps-0"
                           placeholder="Buscar producto por nombre, SKU o categoría..."
                           autocomplete="off">
                    <button type="button" class="btn btn-light border" id="clearSearch" style="display:none;" onclick="clearSearch()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            {{-- Category filter bar (multiselección) --}}
            <div class="d-flex align-items-center gap-2 mb-2 flex-nowrap">
                <span class="text-muted flex-shrink-0" style="font-size:.7rem;"><i class="bi bi-tags me-1"></i>Categoría</span>
                <div class="cat-filter-bar flex-grow-1" id="catBar" style="min-width:0;">
                    <button type="button" class="cat-pill active" data-cat="">Todas</button>
                    @foreach($categories as $cat)
                    <button type="button" class="cat-pill" data-cat="{{ $cat->id }}">{{ $cat->name }}</button>
                    @endforeach
                </div>
            </div>
            {{-- Model filter bar (multiselección) --}}
            @if($motoModels->count())
            <div class="d-flex align-items-center gap-2 mb-3 flex-nowrap">
                <span class="text-muted flex-shrink-0" style="font-size:.7rem;"><i class="bi bi-bicycle me-1"></i>Modelo</span>
                <div class="cat-filter-bar flex-grow-1" id="modelBar" style="min-width:0;">
                    <button type="button" class="cat-pill model-pill active" data-model="">Todos</button>
                    @foreach($motoModels as $m)
                    <button type="button" class="cat-pill model-pill" data-model="{{ $m->id }}">{{ $m->display_name }}</button>
                    @endforeach
                </div>
            </div>
            @endif
            {{-- Grid --}}
            <div id="productGrid" class="row g-2 align-content-start pb-2">
                {{-- Filled by JS --}}
            </div>
            <div id="noProductsMsg" class="text-center py-5 text-muted" style="display:none;">
                <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                Sin resultados para esa búsqueda.
            </div>
        </div>

        {{-- ── RIGHT: CART ────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <form action="{{ route('pos.store') }}" method="POST" id="posForm"
                  style="position:sticky;top:1rem;">
                @csrf
                <input type="hidden" name="sale_type" id="saleTypeInput" value="cash">
                <input type="hidden" name="cash_register_session_id" value="{{ $session->id }}">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-cart3 me-2 text-muted"></i>Carrito</h6>
                            <span class="badge bg-light border text-muted" id="cartCount">0 items</span>
                        </div>
                    </div>

                    {{-- Cart rows --}}
                    <div class="overflow-auto" style="max-height:42vh;">
                        <div id="cartEmpty" class="text-center py-5 text-muted">
                            <i class="bi bi-cart d-block fs-2 mb-2 opacity-25"></i>
                            <small>Haz clic en un producto para agregarlo</small>
                        </div>
                        <table class="table table-sm align-middle mb-0" id="cartTable" style="display:none;font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2" style="font-size:.7rem;" class="text-muted text-uppercase">Producto</th>
                                    <th class="py-2 text-center" style="width:80px;font-size:.7rem;">Cant.</th>
                                    <th class="py-2 text-end" style="width:70px;font-size:.7rem;">P.Unit</th>
                                    <th class="py-2 text-end" style="width:75px;font-size:.7rem;">Sub.</th>
                                    <th class="py-2 pe-2" style="width:32px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody"></tbody>
                        </table>
                    </div>

                    {{-- Footer: client + discount + total + actions --}}
                    <div class="border-top">
                        {{-- Client selector (botón → modal de búsqueda) --}}
                        <div class="p-3 border-bottom">
                            <label class="form-label small fw-semibold mb-1">Cliente</label>
                            <input type="hidden" name="client_id" id="client_id" value="">
                            <button type="button" class="btn btn-light border w-100 d-flex align-items-center justify-content-between"
                                    data-bs-toggle="modal" data-bs-target="#clientModal">
                                <span class="d-flex align-items-center gap-2 text-truncate">
                                    <i class="bi bi-person-circle text-muted"></i>
                                    <span id="selectedClientLabel" class="text-truncate">Cliente general</span>
                                </span>
                                <i class="bi bi-search text-muted small"></i>
                            </button>
                        </div>
                        {{-- Discount --}}
                        <div class="px-3 pt-2 pb-1">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted">Subtotal</span>
                                <span id="cartSubtotal">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <label class="text-muted small mb-0" for="discount">Descuento</label>
                                <div class="input-group input-group-sm" style="width:110px;">
                                    <span class="input-group-text bg-light px-2">$</span>
                                    <input type="number" id="discount" name="discount"
                                           class="form-control text-end" min="0" step="0.01"
                                           value="0" placeholder="0.00"
                                           oninput="recalcCart()">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-1">
                                <span>TOTAL</span>
                                <span id="cartTotal" class="fs-5 text-dark">$0.00</span>
                            </div>
                        </div>
                        {{-- Action buttons --}}
                        <div class="p-3 d-flex flex-column gap-2">
                            <button type="button" class="btn btn-primary w-100 py-2"
                                    id="btnCash" onclick="submitCash()">
                                <i class="bi bi-cash me-2"></i>Cobrar (Contado)
                            </button>
                            <button type="button" class="btn btn-light border w-100 py-2"
                                    id="btnCredit"
                                    data-bs-toggle="modal" data-bs-target="#creditModal">
                                <i class="bi bi-calendar2-check me-2"></i>A Crédito
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hidden cart inputs built by JS --}}
                <div id="cartInputs"></div>

            </form>
        </div>

    </div>
</div>

{{-- ─── CLIENT SEARCH MODAL ───────────────────────────────────────────── --}}
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="clientModalLabel">
                    <i class="bi bi-person-circle me-2 text-muted"></i>Seleccionar cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="clientSearch" class="form-control border-start-0 ps-0"
                           placeholder="Buscar por nombre o documento..." autocomplete="off">
                </div>
                <div id="clientList" class="list-group" style="max-height:42vh;overflow-y:auto;">
                    {{-- Filled by JS --}}
                </div>
                <div id="noClientsMsg" class="text-center py-4 text-muted" style="display:none;">
                    <i class="bi bi-person-x d-block fs-2 mb-2 opacity-25"></i>
                    Sin coincidencias.
                </div>

                {{-- Alta rápida --}}
                <button type="button" class="btn btn-light border w-100 mt-3" onclick="toggleNewClient()">
                    <i class="bi bi-person-plus me-1"></i>Registrar nuevo cliente
                </button>
                <div id="newClientForm" class="mt-3 p-3 rounded-3 border bg-light" style="display:none;">
                    <div id="ncAlert" class="alert alert-danger d-none py-2 small"></div>
                    <div class="row g-2">
                        <div class="col-12"><input type="text" id="nc_full_name" class="form-control form-control-sm" placeholder="Nombre completo *"></div>
                        <div class="col-6"><input type="text" id="nc_id_number" class="form-control form-control-sm" placeholder="Documento"></div>
                        <div class="col-6"><input type="text" id="nc_phone" class="form-control form-control-sm" placeholder="Teléfono"></div>
                        <div class="col-12"><input type="email" id="nc_email" class="form-control form-control-sm" placeholder="Email"></div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-100 mt-2" id="nc_save" onclick="saveNewClientPos()">
                        <i class="bi bi-check-lg me-1"></i>Guardar y seleccionar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── CREDIT MODAL ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="creditModal" tabindex="-1" aria-labelledby="creditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="creditModalLabel">
                    <i class="bi bi-calendar2-check me-2 text-muted"></i>Venta a crédito — Cronograma de cuotas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">N° cuotas</label>
                        <input type="number" id="cm_cuotas" class="form-control" min="1" value="3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Cada (días)</label>
                        <input type="number" id="cm_dias" class="form-control" min="1" value="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Primera fecha</label>
                        <input type="date" id="cm_fecha" class="form-control"
                               value="{{ now()->addDays(30)->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Pago inicial</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light px-2">$</span>
                            <input type="number" id="cm_downpayment" class="form-control" min="0" step="0.01" value="0" placeholder="0.00"
                                   oninput="updateBalanceIndicator()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">% adicional (recargo)</label>
                        <div class="input-group">
                            <input type="number" id="cm_pct" class="form-control" min="0" step="0.01" value="0" placeholder="0.00"
                                   oninput="updateBalanceIndicator()">
                            <span class="input-group-text bg-light px-2">%</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-light border btn-sm mb-3" onclick="generateInstallments()">
                    <i class="bi bi-lightning-charge me-1"></i>Generar cuotas
                </button>

                {{-- Installments table --}}
                <div id="installmentsWrap" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" style="font-size:.83rem;" id="installmentsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2" style="width:40px;">#</th>
                                    <th class="py-2">Vencimiento</th>
                                    <th class="py-2">Monto</th>
                                    <th class="py-2" style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="installmentsBody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2 flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-light border" onclick="addInstallmentRow()">
                            <i class="bi bi-plus-lg me-1"></i>+ Cuota
                        </button>
                        <div id="cm_balance_indicator" class="small fw-semibold"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" onclick="submitCredit()">
                    <i class="bi bi-check-lg me-1"></i>Confirmar venta a crédito
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── PRODUCT DETAIL MODAL ──────────────────────────────────────── --}}
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-box-seam me-2 text-muted"></i>Detalle del producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div id="pmGallery"></div>
                    </div>
                    <div class="col-md-7">
                        <h5 class="fw-bold mb-1" id="pmName"></h5>
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <span class="text-muted small" id="pmSku"></span>
                            <span id="pmStock" class="badge"></span>
                        </div>
                        <div class="fs-4 fw-bold mb-3" id="pmPrice"></div>
                        <dl class="row g-1 small mb-3">
                            <dt class="col-5 text-muted fw-normal">Categoría</dt>
                            <dd class="col-7 mb-0" id="pmCategory"></dd>
                            <dt class="col-5 text-muted fw-normal">Marca</dt>
                            <dd class="col-7 mb-0" id="pmBrand"></dd>
                        </dl>
                        <div class="mb-2" id="pmModelsRow">
                            <div class="text-muted small fw-semibold mb-1">Modelos compatibles</div>
                            <div class="small" id="pmModels"></div>
                        </div>
                        <div class="mb-0" id="pmDescRow">
                            <div class="text-muted small fw-semibold mb-1">Descripción</div>
                            <div class="small text-muted" id="pmDesc"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="pmFichaBtn" target="_blank" class="btn btn-primary"
                   data-base-url="{{ route('products.show', '__ID__') }}">
                    <i class="bi bi-eye me-1"></i>Ver ficha completa
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.product-card {
    cursor: pointer;
    border: 1.5px solid #e8e8e8;
    border-radius: 9px;
    padding: 7px;
    background: #fff;
    transition: all .15s ease;
    user-select: none;
    position: relative;
    height: 100%;
}
.product-card:hover { border-color: var(--brand-black,#0a0a0a); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.product-card.disabled-card { opacity: .45; cursor: not-allowed; }
.product-card.in-cart { border-color: #0a0a0a; background: #fafafa; }
.product-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 6px; background:#f5f5f5; }
.product-thumb-placeholder {
    width: 100%; aspect-ratio: 1; border-radius: 6px; background:#f0f0f0;
    display: flex; align-items: center; justify-content: center; color:#ccc; font-size:1.6rem;
}
.cart-qty-input { width:56px; text-align:center; }

/* Category filter bar */
.cat-filter-bar {
    display: flex;
    gap: .4rem;
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: .25rem;
    scrollbar-width: thin;
}
.cat-filter-bar::-webkit-scrollbar { height: 4px; }
.cat-filter-bar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
.cat-pill {
    display: inline-flex;
    align-items: center;
    padding: .2rem .7rem;
    border-radius: 50rem;
    font-size: .73rem;
    font-weight: 500;
    border: 1.5px solid #dee2e6;
    background: #fff;
    color: #495057;
    cursor: pointer;
    transition: all .15s ease;
    white-space: nowrap;
    flex-shrink: 0;
}
.cat-pill:hover { border-color: #0a0a0a; color: #0a0a0a; }
.cat-pill.active { background: #0a0a0a; border-color: #0a0a0a; color: #fff; }

/* Product info btn */
.product-info-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255,255,255,.9);
    border: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    color: #666;
    cursor: pointer;
    z-index: 2;
    transition: all .15s;
    padding: 0;
    line-height: 1;
}
.product-info-btn:hover { background: #fff; border-color: #0a0a0a; color: #0a0a0a; }

/* Product detail modal gallery */
.pm-gallery-main {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    border-radius: 10px;
    background: #f5f5f5;
}
.pm-gallery-placeholder {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 10px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #ccc;
}
.pm-thumb {
    width: 56px;
    height: 56px;
    object-fit: cover;
    border-radius: 6px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: border-color .15s;
}
.pm-thumb.active, .pm-thumb:hover { border-color: #0a0a0a; }

@media print {
    .app-sidebar, .app-topbar, #posForm .card-header, .pos-wrapper > .d-flex:first-child { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
const PRODUCTS = @json($productsData);
const CLIENTS  = @json($clientsJson);
let cart = {};
let installmentCount = 0;
const selectedCats   = new Set();
const selectedModels = new Set();

// ── CLIENT SEARCH MODAL ─────────────────────────────────────────────
function renderClientList(filter) {
    const listEl = document.getElementById('clientList');
    const noMsg  = document.getElementById('noClientsMsg');
    const q = (filter || '').toLowerCase().trim();
    const matches = q
        ? CLIENTS.filter(c =>
            c.name.toLowerCase().includes(q) ||
            (c.id_number && c.id_number.toLowerCase().includes(q)))
        : CLIENTS;

    let html = `
        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                onclick="selectClient('', 'Cliente general')">
            <i class="bi bi-people text-muted"></i>
            <span class="fw-semibold">Cliente general</span>
        </button>`;

    if (matches.length === 0 && q) {
        noMsg.style.display = '';
    } else {
        noMsg.style.display = 'none';
        html += matches.map(c => `
            <button type="button" class="list-group-item list-group-item-action"
                    onclick="selectClient(${c.id}, '${(c.name + (c.id_number ? ' (' + c.id_number + ')' : '')).replace(/'/g, "\\'")}')">
                <div class="fw-semibold">${c.name}</div>
                ${c.id_number ? `<small class="text-muted"><i class="bi bi-card-text me-1"></i>${c.id_number}</small>` : ''}
            </button>`).join('');
    }
    listEl.innerHTML = html;
}

function selectClient(id, label) {
    document.getElementById('client_id').value = id;
    document.getElementById('selectedClientLabel').textContent = label;
    bootstrap.Modal.getInstance(document.getElementById('clientModal'))?.hide();
}

function toggleNewClient() {
    const f = document.getElementById('newClientForm');
    f.style.display = f.style.display === 'none' ? '' : 'none';
    if (f.style.display !== 'none') document.getElementById('nc_full_name').focus();
}

function saveNewClientPos() {
    const btn = document.getElementById('nc_save');
    const alertBox = document.getElementById('ncAlert');
    const name = document.getElementById('nc_full_name').value.trim();
    alertBox.classList.add('d-none');
    if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    fetch('{{ route('clients.quick-store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
        },
        body: JSON.stringify({
            full_name: name,
            id_number: document.getElementById('nc_id_number').value.trim(),
            phone:     document.getElementById('nc_phone').value.trim(),
            email:     document.getElementById('nc_email').value.trim(),
        }),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok || !d.ok) { alertBox.textContent = d.message || 'No se pudo guardar.'; alertBox.classList.remove('d-none'); return; }
        CLIENTS.push({ id: d.client.id, name: d.client.full_name, id_number: d.client.id_number || '' });
        ['nc_full_name','nc_id_number','nc_phone','nc_email'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('newClientForm').style.display = 'none';
        selectClient(d.client.id, d.client.full_name + (d.client.id_number ? ' (' + d.client.id_number + ')' : ''));
    })
    .catch(() => { alertBox.textContent = 'Error de conexión.'; alertBox.classList.remove('d-none'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar y seleccionar'; });
}

// ── PRODUCT DETAIL MODAL ────────────────────────────────────────────
function openProductModal(pid) {
    const p = PRODUCTS.find(x => x.id === pid);
    if (!p) return;
    const modal = document.getElementById('productModal');

    // Gallery
    let galleryHtml = '';
    if (p.photos && p.photos.length > 0) {
        galleryHtml = `
            <img id="pmMainImg" src="${p.photos[0]}" class="pm-gallery-main mb-2" alt="${p.name}">
            ${p.photos.length > 1 ? `<div class="d-flex gap-2 flex-wrap">
                ${p.photos.map((ph, i) => `<img src="${ph}" class="pm-thumb ${i===0?'active':''}" onclick="pmSwap(this, '${ph}')" alt="">`).join('')}
            </div>` : ''}`;
    } else {
        galleryHtml = `<div class="pm-gallery-placeholder"><i class="bi bi-box-seam"></i></div>`;
    }

    modal.querySelector('#pmGallery').innerHTML = galleryHtml;
    modal.querySelector('#pmName').textContent  = p.name;
    modal.querySelector('#pmSku').textContent   = p.sku;
    modal.querySelector('#pmPrice').textContent = '$' + p.price.toFixed(2);
    modal.querySelector('#pmStock').textContent = p.stock > 0 ? p.stock + ' en stock' : 'Sin stock';
    modal.querySelector('#pmStock').className   = 'badge ' + (p.stock > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle');
    modal.querySelector('#pmCategory').textContent = p.category || '—';
    modal.querySelector('#pmBrand').textContent    = p.brand || '—';
    modal.querySelector('#pmDesc').textContent     = p.description || '—';

    const modelsRow = modal.querySelector('#pmModelsRow');
    if (p.compatible_models) {
        modelsRow.style.display = '';
        modal.querySelector('#pmModels').textContent = p.compatible_models;
    } else {
        modelsRow.style.display = 'none';
    }

    const fichaBtn = modal.querySelector('#pmFichaBtn');
    fichaBtn.href = fichaBtn.dataset.baseUrl.replace('__ID__', p.id);

    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function pmSwap(thumb, src) {
    document.getElementById('pmMainImg').src = src;
    document.querySelectorAll('.pm-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

// ── RENDER PRODUCT GRID ─────────────────────────────────────────────
function renderGrid(filter) {
    const grid = document.getElementById('productGrid');
    const noMsg = document.getElementById('noProductsMsg');
    const q = (filter || '').toLowerCase().trim();

    let list = q
        ? PRODUCTS.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q) ||
            p.category.toLowerCase().includes(q))
        : PRODUCTS.slice();

    if (selectedCats.size) {
        list = list.filter(p => selectedCats.has(String(p.category_id)));
    }
    if (selectedModels.size) {
        list = list.filter(p => (p.model_ids || []).some(id => selectedModels.has(String(id))));
    }

    if (list.length === 0) {
        grid.innerHTML = '';
        noMsg.style.display = '';
        return;
    }
    noMsg.style.display = 'none';

    grid.innerHTML = list.map(p => {
        const disabled = p.stock <= 0 ? 'disabled-card' : '';
        const inCart   = cart[p.id] ? 'in-cart' : '';
        const stockBadge = p.stock > 0
            ? `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;">${p.stock} en stock</span>`
            : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.65rem;">Sin stock</span>`;
        const img = p.photo
            ? `<img src="${p.photo}" class="product-thumb mb-2" alt="${p.name}">`
            : `<div class="product-thumb-placeholder mb-2"><i class="bi bi-box-seam"></i></div>`;
        const meta = [p.brand, p.compatible_models].filter(Boolean).join(' · ') || '—';
        const metaEsc = meta.replace(/"/g, '&quot;');
        return `
        <div class="col-6 col-md-4 col-xl-3">
            <div class="product-card ${disabled} ${inCart}" onclick="addToCart(${p.id})" data-pid="${p.id}">
                <button type="button" class="product-info-btn" onclick="event.stopPropagation();openProductModal(${p.id})" title="Ver detalle">
                    <i class="bi bi-eye"></i>
                </button>
                ${img}
                <div class="fw-semibold lh-sm mb-1" style="font-size:.78rem;">${p.name}</div>
                <div class="text-muted mb-1 text-truncate" style="font-size:.66rem;" title="${metaEsc}">${meta}</div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                    <span class="fw-bold" style="font-size:.85rem;">$${p.price.toFixed(2)}</span>
                    ${stockBadge}
                </div>
            </div>
        </div>`;
    }).join('');
}

// ── CART LOGIC ──────────────────────────────────────────────────────
function addToCart(pid) {
    const p = PRODUCTS.find(x => x.id === pid);
    if (!p || p.stock <= 0) return;
    if (cart[pid]) {
        if (cart[pid].qty < p.stock) cart[pid].qty++;
    } else {
        cart[pid] = { product: p, qty: 1 };
    }
    renderCart();
    highlightProductCard(pid);
}

function removeFromCart(pid) {
    delete cart[pid];
    renderCart();
    highlightProductCard(pid);
}

function highlightProductCard(pid) {
    document.querySelectorAll(`[data-pid="${pid}"]`).forEach(el => {
        el.classList.toggle('in-cart', !!cart[pid]);
    });
}

function renderCart() {
    const body    = document.getElementById('cartBody');
    const empty   = document.getElementById('cartEmpty');
    const table   = document.getElementById('cartTable');
    const count   = document.getElementById('cartCount');
    const inputs  = document.getElementById('cartInputs');

    const items = Object.values(cart);
    count.textContent = items.length + (items.length === 1 ? ' item' : ' items');

    if (items.length === 0) {
        empty.style.display = '';
        table.style.display = 'none';
        inputs.innerHTML = '';
        recalcCart();
        return;
    }
    empty.style.display = 'none';
    table.style.display = '';

    body.innerHTML = items.map((it, i) => {
        const sub = (it.qty * it.product.price).toFixed(2);
        return `
        <tr>
            <td class="ps-3 py-2">
                <div class="fw-semibold lh-sm" style="font-size:.78rem;">${it.product.name}</div>
                <div class="text-muted" style="font-size:.68rem;">${it.product.sku}</div>
            </td>
            <td class="text-center py-2">
                <input type="number" class="form-control form-control-sm cart-qty-input"
                       min="1" step="1" inputmode="numeric" max="${it.product.stock}"
                       value="${it.qty}"
                       oninput="updateQty(${it.product.id}, this.value)">
            </td>
            <td class="text-end py-2">$${it.product.price.toFixed(2)}</td>
            <td class="text-end py-2 fw-semibold">$${sub}</td>
            <td class="pe-2 py-2">
                <button type="button" class="btn btn-sm btn-light border text-danger p-0 px-1"
                        onclick="removeFromCart(${it.product.id})" title="Quitar">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        </tr>`;
    }).join('');

    inputs.innerHTML = items.map((it, i) => `
        <input type="hidden" name="items[${i}][product_id]" value="${it.product.id}">
        <input type="hidden" name="items[${i}][quantity]"   value="${it.qty}">
        <input type="hidden" name="items[${i}][unit_price]" value="${it.product.price}">
    `).join('');

    recalcCart();
}

function updateQty(pid, val) {
    if (!cart[pid]) return;
    const qty = Math.max(1, Math.min(Math.floor(parseInt(val, 10) || 1), cart[pid].product.stock));
    cart[pid].qty = qty;
    renderCart();
}

function recalcCart() {
    const items = Object.values(cart);
    const sub   = items.reduce((s, it) => s + it.qty * it.product.price, 0);
    const disc  = parseFloat(document.getElementById('discount').value) || 0;
    const total = Math.max(0, sub - disc);
    document.getElementById('cartSubtotal').textContent = '$' + sub.toFixed(2);
    document.getElementById('cartTotal').textContent    = '$' + total.toFixed(2);
    return { sub, disc, total };
}

// ── SUBMIT ──────────────────────────────────────────────────────────
function lockPosButtons(active) {
    ['btnCash', 'btnCredit'].forEach(function (id) {
        const b = document.getElementById(id);
        if (b) b.disabled = true;
    });
    if (active) {
        active.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
    }
}

function submitCash() {
    if (Object.keys(cart).length === 0) { alert('Agrega al menos un producto.'); return; }
    document.getElementById('saleTypeInput').value = 'cash';
    lockPosButtons(document.getElementById('btnCash'));
    document.getElementById('posForm').requestSubmit();
}

function submitCredit() {
    if (Object.keys(cart).length === 0) { alert('Agrega al menos un producto.'); return; }
    const rows = document.querySelectorAll('#installmentsBody tr');
    if (rows.length === 0) { alert('Genera al menos una cuota antes de confirmar.'); return; }
    document.getElementById('saleTypeInput').value = 'credit';
    const posForm = document.getElementById('posForm');
    document.querySelectorAll('.inst-hidden').forEach(el => el.remove());

    const dp  = parseFloat(document.getElementById('cm_downpayment').value) || 0;
    const pct = parseFloat(document.getElementById('cm_pct').value) || 0;
    const { total } = recalcCart();
    const recargo = total * pct / 100;

    if (dp > 0) {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'down_payment'; inp.value = dp.toFixed(2); inp.className = 'inst-hidden';
        posForm.appendChild(inp);
    }

    // Append interest (recargo)
    const intInp = document.createElement('input');
    intInp.type = 'hidden'; intInp.name = 'interest'; intInp.value = recargo.toFixed(2); intInp.className = 'inst-hidden';
    posForm.appendChild(intInp);

    rows.forEach((row, i) => {
        const dd = row.querySelector('.inst-date').value;
        const am = row.querySelector('.inst-amount').value;
        [['installments['+i+'][due_date]', dd], ['installments['+i+'][amount]', am]].forEach(([n,v]) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = n; inp.value = v; inp.className = 'inst-hidden';
            posForm.appendChild(inp);
        });
    });
    bootstrap.Modal.getInstance(document.getElementById('creditModal'))?.hide();
    lockPosButtons(document.getElementById('btnCash'));
    posForm.requestSubmit();
}

// ── INSTALLMENTS GENERATOR ──────────────────────────────────────────
function generateInstallments() {
    const n    = parseInt(document.getElementById('cm_cuotas').value) || 3;
    const days = parseInt(document.getElementById('cm_dias').value) || 30;
    const fd   = document.getElementById('cm_fecha').value;
    if (!fd) { alert('Ingresa la primera fecha de vencimiento.'); return; }

    const { total } = recalcCart();
    const dp  = parseFloat(document.getElementById('cm_downpayment').value) || 0;
    const pct = parseFloat(document.getElementById('cm_pct').value) || 0;
    const recargo = total * pct / 100;
    const rem = Math.max(0, (total + recargo) - dp);

    const base = Math.floor((rem / n) * 100) / 100;
    const last = (rem - base * (n - 1)).toFixed(2);

    const body = document.getElementById('installmentsBody');
    body.innerHTML = '';
    installmentCount = 0;

    let d = new Date(fd + 'T00:00:00');
    for (let i = 0; i < n; i++) {
        const dateStr = d.toISOString().slice(0,10);
        const amt = (i === n - 1) ? last : base.toFixed(2);
        addInstallmentRow(dateStr, amt);
        d.setDate(d.getDate() + days);
    }
    document.getElementById('installmentsWrap').style.display = '';
    updateBalanceIndicator();
}

function addInstallmentRow(dateVal, amtVal) {
    const i = installmentCount++;
    const tr = document.createElement('tr');
    tr.dataset.row = i;
    tr.innerHTML = `
        <td class="py-1">${i+1}</td>
        <td class="py-1">
            <input type="date" class="form-control form-control-sm inst-date"
                   value="${dateVal || ''}" oninput="updateBalanceIndicator()">
        </td>
        <td class="py-1">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light px-2">$</span>
                <input type="number" class="form-control inst-amount text-end"
                       step="0.01" min="0" value="${amtVal || ''}"
                       oninput="updateBalanceIndicator()">
            </div>
        </td>
        <td class="py-1">
            <button type="button" class="btn btn-sm btn-light border text-danger p-0 px-1"
                    onclick="this.closest('tr').remove(); renumberInstallments(); updateBalanceIndicator();">
                <i class="bi bi-x"></i>
            </button>
        </td>`;
    document.getElementById('installmentsBody').appendChild(tr);
    updateBalanceIndicator();
}

function renumberInstallments() {
    document.querySelectorAll('#installmentsBody tr').forEach((tr, i) => {
        tr.cells[0].textContent = i + 1;
    });
}

function updateBalanceIndicator() {
    const { total } = recalcCart();
    const dp  = parseFloat(document.getElementById('cm_downpayment').value) || 0;
    const pct = parseFloat(document.getElementById('cm_pct').value) || 0;
    const recargo = total * pct / 100;
    const totalConRecargo = total + recargo;
    const rem = Math.max(0, totalConRecargo - dp);

    const instSum = Array.from(document.querySelectorAll('.inst-amount'))
        .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
    const diff = (instSum - rem).toFixed(2);
    const ind  = document.getElementById('cm_balance_indicator');
    const ok   = Math.abs(parseFloat(diff)) < 0.02;

    let recargoLine = pct > 0
        ? `<div class="text-muted small mb-1">
               Recargo ${pct}%: <strong>$${recargo.toFixed(2)}</strong>
               &nbsp;&middot;&nbsp;
               Total con recargo: <strong>$${totalConRecargo.toFixed(2)}</strong>
           </div>`
        : '';

    ind.innerHTML = recargoLine + (ok
        ? `<span class="text-success"><i class="bi bi-check-circle me-1"></i>Cuotas OK ($${instSum.toFixed(2)})</span>`
        : `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Suma cuotas: $${instSum.toFixed(2)} / Requerido: $${rem.toFixed(2)} (diff: $${diff})</span>`);
}

// ── SEARCH ──────────────────────────────────────────────────────────
function clearSearch() {
    document.getElementById('productSearch').value = '';
    document.getElementById('clearSearch').style.display = 'none';
    renderGrid('');
}

document.addEventListener('DOMContentLoaded', function () {
    renderGrid('');

    // Barras de filtro multiselección (categorías y modelos)
    function setupMultiBar(barId, dataKey, set) {
        const bar = document.getElementById(barId);
        if (!bar) return;
        const allBtn = bar.querySelector('.cat-pill[data-' + dataKey + '=""]') || bar.querySelector('.cat-pill');
        bar.querySelectorAll('.cat-pill').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const val = this.dataset[dataKey] || '';
                if (val === '') {
                    set.clear();
                    bar.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                } else {
                    this.classList.toggle('active');
                    if (this.classList.contains('active')) {
                        set.add(val);
                        if (allBtn) allBtn.classList.remove('active');
                    } else {
                        set.delete(val);
                        if (set.size === 0 && allBtn) allBtn.classList.add('active');
                    }
                }
                renderGrid(document.getElementById('productSearch').value);
            });
        });
    }
    setupMultiBar('catBar', 'cat', selectedCats);
    setupMultiBar('modelBar', 'model', selectedModels);

    const searchInput = document.getElementById('productSearch');
    searchInput.addEventListener('input', function () {
        document.getElementById('clearSearch').style.display = this.value ? '' : 'none';
        renderGrid(this.value);
    });

    document.getElementById('discount').addEventListener('input', updateBalanceIndicator);

    document.getElementById('creditModal').addEventListener('show.bs.modal', function () {
        updateBalanceIndicator();
    });

    renderClientList('');
    const clientSearch = document.getElementById('clientSearch');
    clientSearch.addEventListener('input', function () {
        renderClientList(this.value);
    });
    document.getElementById('clientModal').addEventListener('shown.bs.modal', function () {
        clientSearch.value = '';
        renderClientList('');
        clientSearch.focus();
    });
});
</script>
@endpush
@endif

@endsection
