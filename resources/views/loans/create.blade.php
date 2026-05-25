@extends('layouts.app')

@section('title', 'Nuevo Prestamo')

@section('page')
<div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('loans.index') }}" class="text-decoration-none">Préstamos</a></li>
                    <li class="breadcrumb-item active">Nuevo</li>
                </ol>
            </nav>
            <h1 class="mb-0 fs-4 fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-cash-coin text-primary"></i> Nuevo Préstamo
            </h1>
        </div>
        <a href="{{ route('loans.index') }}" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex gap-2 align-items-start" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
            <div>
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('loans.store') }}" method="POST" id="loan-form" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">

            {{-- ── CLIENT CARD ─────────────────────────────── --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="lp-icon-badge bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <h6 class="mb-0 fw-bold">Cliente</h6>
                        </div>

                        {{-- Segmented control --}}
                        <div class="lp-mode-toggle mb-3">
                            <button type="button" class="lp-mode-btn active" id="btn-existing">
                                <i class="bi bi-person-check me-1"></i>Existente
                            </button>
                            <button type="button" class="lp-mode-btn" id="btn-new">
                                <i class="bi bi-person-plus me-1"></i>Nuevo
                            </button>
                        </div>

                        {{-- Existing client --}}
                        <div id="existing-section">
                            <label class="form-label small fw-semibold">Seleccionar cliente</label>
                            <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror">
                                <option value="">Buscar cliente...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ (string) old('client_id') === (string) $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}{{ $client->id_number ? ' · ' . $client->id_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="mt-2">
                                <a href="{{ route('clients.create') }}" class="text-decoration-none small text-muted" target="_blank">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Abrir módulo de clientes
                                </a>
                            </div>
                        </div>

                        {{-- New client --}}
                        <div id="new-section" class="d-none">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name"
                                        class="form-control @error('client_name') is-invalid @enderror"
                                        value="{{ old('client_name') }}" placeholder="Ej: Juan Pérez">
                                    @error('client_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Documento</label>
                                    <input type="text" name="client_id_number"
                                        class="form-control @error('client_id_number') is-invalid @enderror"
                                        value="{{ old('client_id_number') }}" placeholder="Cédula / RIF">
                                    @error('client_id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Teléfono</label>
                                    <input type="text" name="client_phone"
                                        class="form-control @error('client_phone') is-invalid @enderror"
                                        value="{{ old('client_phone') }}" placeholder="04xx-xxx-xxxx">
                                    @error('client_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Correo electrónico</label>
                                    <input type="email" name="client_email"
                                        class="form-control @error('client_email') is-invalid @enderror"
                                        value="{{ old('client_email') }}" placeholder="correo@ejemplo.com">
                                    @error('client_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Dirección</label>
                                    <input type="text" name="client_address"
                                        class="form-control @error('client_address') is-invalid @enderror"
                                        value="{{ old('client_address') }}" placeholder="Calle, ciudad...">
                                    @error('client_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── LOAN CARD ────────────────────────────────── --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="lp-icon-badge bg-success bg-opacity-10 text-success">
                                <i class="bi bi-wallet2"></i>
                            </span>
                            <h6 class="mb-0 fw-bold">Datos del Préstamo</h6>
                        </div>

                        {{-- § 1 — Ubicación y Producto --}}
                        <p class="lp-section-label"><i class="bi bi-geo-alt-fill"></i> Ubicación y artículo</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Sucursal <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ (string) old('branch_id', $defaultBranchId) === (string) $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">
                                    Producto <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-box-seam text-muted"></i>
                                        </span>
                                        <input type="text" id="product_name" name="product_name"
                                            class="form-control border-start-0 border-end-0 @error('product_name') is-invalid @enderror"
                                            value="{{ old('product_name') }}"
                                            placeholder="Escribe para buscar..." autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary" id="clear-product"
                                            title="Limpiar" style="display:none; border-left:0;">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="product_id" name="product_id" value="{{ old('product_id') }}">
                                    <div id="product-suggestions" class="list-group shadow d-none"
                                        style="position:absolute;top:100%;left:0;right:0;z-index:1050;max-height:220px;overflow:auto;"></div>
                                </div>
                                @error('product_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <small class="text-muted">
                                    <i class="bi bi-lightbulb text-warning me-1"></i>Si no existe, puedes crearlo al salir del campo.
                                </small>
                            </div>
                        </div>

                        {{-- New product panel --}}
                        <div class="d-none mb-4" id="new-product-panel">
                            <div class="border border-warning rounded-3 p-3" style="background:rgba(255,193,7,.06);">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-plus-circle-fill text-warning"></i>
                                    <span class="fw-semibold small">Nuevo producto</span>
                                    <span class="badge bg-warning text-dark ms-auto">No encontrado</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-3">
                                        <label class="form-label small">Costo</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" id="product_cost" name="product_cost"
                                                class="form-control" value="{{ old('product_cost') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small">Precio</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" id="product_price" name="product_price"
                                                class="form-control" value="{{ old('product_price') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small">SKU <span class="text-muted">(auto)</span></label>
                                        <input type="text" id="product_sku" name="product_sku"
                                            class="form-control form-control-sm font-monospace"
                                            value="{{ old('product_sku') }}" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small">Categoría</label>
                                        <select id="product_credit_category_id" name="product_credit_category_id" class="form-select form-select-sm">
                                            <option value="">Sin categoría</option>
                                            @foreach($creditCategories as $category)
                                                <option value="{{ $category->id }}" {{ (string) old('product_credit_category_id') === (string) $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- § 2 — Categoría y Regla --}}
                        <p class="lp-section-label"><i class="bi bi-tags-fill"></i> Categoría de crédito</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-5" id="category-wrapper">
                                <label class="form-label small fw-semibold">Categoría</label>
                                <select name="credit_category_id" id="credit_category_id"
                                    class="form-select @error('credit_category_id') is-invalid @enderror">
                                    <option value="">Sin categoría</option>
                                    @foreach($creditCategories as $category)
                                        <option value="{{ $category->id }}" {{ (string) old('credit_category_id') === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('credit_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-7" id="rule-wrapper">
                                <label class="form-label small fw-semibold">
                                    Regla de crédito <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <select name="credit_category_rule_id" id="credit_category_rule_id"
                                    class="form-select @error('credit_category_rule_id') is-invalid @enderror">
                                    <option value="">— Elige categoría primero —</option>
                                </select>
                                <small class="text-muted" id="rule-help">
                                    <i class="bi bi-magic text-primary me-1"></i>Al elegir una regla se auto-rellenan interés, período y plazo.
                                </small>
                                @error('credit_category_rule_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- § 3 — Condiciones Financieras --}}
                        <p class="lp-section-label"><i class="bi bi-calculator-fill"></i> Condiciones financieras</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold text-primary">$</span>
                                    <input type="number" name="amount" id="amount" step="0.01"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" placeholder="0.00" required>
                                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tasa de interés <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="interest_rate" id="interest_rate" step="0.01"
                                        class="form-control @error('interest_rate') is-invalid @enderror"
                                        value="{{ old('interest_rate') }}" placeholder="0.00" required>
                                    <span class="input-group-text">%</span>
                                    @error('interest_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Periodicidad del interés <span class="text-danger">*</span></label>
                                <select name="interest_period" id="interest_period"
                                    class="form-select @error('interest_period') is-invalid @enderror" required>
                                    <option value="monthly"    {{ old('interest_period', 'monthly') === 'monthly'    ? 'selected' : '' }}>Mensual</option>
                                    <option value="quarterly"  {{ old('interest_period') === 'quarterly'  ? 'selected' : '' }}>Trimestral</option>
                                    <option value="semiannual" {{ old('interest_period') === 'semiannual' ? 'selected' : '' }}>Semestral</option>
                                    <option value="annual"     {{ old('interest_period') === 'annual'     ? 'selected' : '' }}>Anual</option>
                                </select>
                                @error('interest_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Plazo <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="term_months" id="term_months"
                                        class="form-control @error('term_months') is-invalid @enderror"
                                        value="{{ old('term_months') }}" placeholder="12" required>
                                    <span class="input-group-text text-muted">meses</span>
                                    @error('term_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Live preview box --}}
                        <div id="loan-preview" class="lp-preview mb-4" style="display:none;">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="lp-preview-label">Cuota mensual estimada</div>
                                    <div class="lp-preview-amount" id="preview-monthly">$0.00</div>
                                </div>
                                <div class="vr mx-3 opacity-25"></div>
                                <div class="col text-end">
                                    <div class="lp-preview-label">Total a pagar</div>
                                    <div class="fw-bold" id="preview-total">$0.00</div>
                                    <small class="text-muted" id="preview-term"></small>
                                </div>
                            </div>
                        </div>

                        {{-- § 4 — Observaciones --}}
                        <p class="lp-section-label mb-3"><i class="bi bi-chat-left-text-fill"></i> Observaciones</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Notas internas</label>
                                <textarea name="notes" rows="2"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="Observaciones adicionales...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- § 5 — Fotos / Imágenes --}}
                        <p class="lp-section-label mb-3 mt-4"><i class="bi bi-camera-fill"></i> Fotos e Imágenes</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Adjuntar fotos del producto o documentos</label>
                                <input type="file" name="images[]" class="form-control @error('images.*') is-invalid @enderror" multiple accept="image/*">
                                <small class="text-muted">Puedes seleccionar múltiples imágenes (JPG, PNG, WebP). Máx. 5MB cada una.</small>
                                @error('images.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Submit bar --}}
        <div class="mt-4 p-3 bg-white border rounded-3 shadow-sm d-flex align-items-center gap-3 flex-wrap">
            <button type="submit" class="btn btn-primary px-5">
                <i class="bi bi-check2-circle me-2"></i>Registrar préstamo
            </button>
            <a href="{{ route('loans.index') }}" class="btn btn-light border">
                <i class="bi bi-x me-1"></i>Cancelar
            </a>
            <small class="text-muted ms-auto"><span class="text-danger">*</span> Campos obligatorios</small>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .lp-icon-badge {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .lp-mode-toggle {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .4rem;
        background: #f1f3f5;
        padding: .25rem;
        border-radius: .6rem;
    }
    .lp-mode-btn {
        border: 0;
        background: transparent;
        border-radius: .45rem;
        padding: .45rem .5rem;
        font-size: .84rem;
        color: #495057;
        font-weight: 600;
    }
    .lp-mode-btn.active {
        background: #fff;
        color: #0d6efd;
        box-shadow: 0 1px 3px rgba(0,0,0,.09);
    }
    .lp-section-label {
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: #495057;
        margin-bottom: .6rem;
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .lp-preview {
        border: 1px solid #e6eefc;
        background: linear-gradient(135deg, #f7faff, #f3f9ff);
        border-radius: .8rem;
        padding: .9rem 1rem;
    }
    .lp-preview-label {
        color: #6c757d;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .lp-preview-amount {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.15;
        color: #0d6efd;
    }

    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endpush

@php
    $productsMap = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'cost' => (float) $product->cost,
            'price' => (float) $product->price,
            'credit_category_id' => $product->credit_category_id,
        ];
    })->values()->all();

    $rulesMap = $creditCategories->mapWithKeys(function ($category) {
        return [
            $category->id => $category->rules->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name ?: ('Regla #' . $rule->id),
                    'interest_rate' => (float) $rule->interest_rate,
                    'interest_period' => $rule->interest_period,
                    'term_months_limit' => (int) $rule->term_months_limit,
                    'min_amount' => $rule->min_amount,
                    'max_amount' => $rule->max_amount,
                ];
            })->values()->all(),
        ];
    })->toArray();
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        const $clientSelect = $('#client_id');
        const $existingSection = $('#existing-section');
        const $newSection = $('#new-section');
        const $btnExisting = $('#btn-existing');
        const $btnNew = $('#btn-new');
        const $productInput = $('#product_name');
        const $productId = $('#product_id');
        const $productSuggestions = $('#product-suggestions');
        const $newProductPanel = $('#new-product-panel');
        const $clearProduct = $('#clear-product');
        const $productSku = $('#product_sku');
        const $productCost = $('#product_cost');
        const $productPrice = $('#product_price');
        const $productCategory = $('#product_credit_category_id');
        const $categorySelect = $('#credit_category_id');
        const $ruleSelect = $('#credit_category_rule_id');
        const $interestInput = $('input[name="interest_rate"]');
        const $periodSelect = $('#interest_period');
        const $termInput = $('input[name="term_months"]');
        const $amountInput = $('#amount');
        const $preview = $('#loan-preview');
        const $previewMonthly = $('#preview-monthly');
        const $previewTotal = $('#preview-total');
        const $previewTerm = $('#preview-term');
        const productsMap = @json($productsMap);
        const rulesMap = @json($rulesMap);

        $clientSelect.select2({
            placeholder: 'Buscar cliente...',
            allowClear: true,
            width: '100%'
        });

        function normalizeText(text) {
            return (text || '').toString().trim().toLowerCase();
        }

        function makeSku(name) {
            const cleaned = (name || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
            const stamp = new Date().toISOString().replace(/[-:TZ.]/g, '').slice(0, 14);
            return `${cleaned || 'PRD'}-${stamp}`;
        }

        function hideNewProductPanel() {
            $newProductPanel.addClass('d-none');
            $productSku.val('');
            $productCost.val('');
            $productPrice.val('');
            $productCategory.val('');
        }

        function showNewProductPanel(name) {
            $newProductPanel.removeClass('d-none');
            if (!$productSku.val()) {
                $productSku.val(makeSku(name));
            }
        }

        function renderSuggestions(term) {
            const q = normalizeText(term);
            if (q.length < 1) {
                $productSuggestions.addClass('d-none').empty();
                return;
            }

            const found = productsMap.filter(p => normalizeText(p.name).includes(q) || normalizeText(p.sku).includes(q)).slice(0, 8);
            if (!found.length) {
                $productSuggestions.addClass('d-none').empty();
                return;
            }

            const html = found.map(p => `<button type="button" class="list-group-item list-group-item-action product-suggest-item" data-id="${p.id}">${p.name} <small class="text-muted">(${p.sku})</small></button>`).join('');
            $productSuggestions.html(html).removeClass('d-none');
        }

        function selectProductById(productId) {
            const p = productsMap.find(item => String(item.id) === String(productId));
            if (!p) return;

            $productInput.val(p.name);
            $productId.val(p.id);
            $clearProduct.show();
            $productSuggestions.addClass('d-none').empty();
            hideNewProductPanel();

            // Auto-sync category from product
            if (p.credit_category_id) {
                $categorySelect.val(String(p.credit_category_id)).trigger('change');
                $categorySelect.prop('disabled', true);
            } else {
                $categorySelect.prop('disabled', false);
            }
        }

        $productInput.on('input', function () {
            $productId.val('');
            $clearProduct.toggle(!!$(this).val());
            renderSuggestions($(this).val());
        });

        $clearProduct.on('click', function () {
            $productInput.val('');
            $productId.val('');
            $clearProduct.hide();
            hideNewProductPanel();
            $categorySelect.val('').trigger('change');
            $categorySelect.prop('disabled', false);
            $productSuggestions.addClass('d-none').empty();
            $productInput.trigger('focus');
        });

        $productSuggestions.on('mousedown', '.product-suggest-item', function (e) {
            e.preventDefault();
            selectProductById($(this).data('id'));
        });

        $productInput.on('blur', function () {
            setTimeout(() => {
                const typed = $(this).val().trim();
                const exact = productsMap.find(p => normalizeText(p.name) === normalizeText(typed));
                if (exact) {
                    selectProductById(exact.id);
                    return;
                }

                if (typed && !$productId.val()) {
                    showNewProductPanel(typed);
                    $categorySelect.prop('disabled', false);
                } else if (!typed) {
                    hideNewProductPanel();
                }

                $productSuggestions.addClass('d-none').empty();
            }, 150);
        });

        function setClientMode(mode) {
            const useNew = mode === 'new';

            $btnExisting.toggleClass('active', !useNew);
            $btnNew.toggleClass('active', useNew);
            $existingSection.toggleClass('d-none', useNew);
            $newSection.toggleClass('d-none', !useNew);

            if (useNew) {
                $clientSelect.val('').trigger('change');
            }
        }

        $btnExisting.on('click', function () { setClientMode('existing'); });
        $btnNew.on('click', function () { setClientMode('new'); });

        $clientSelect.on('change', function () {
            const hasClient = !!$(this).val();
            if (hasClient) {
                setClientMode('existing');
            }
        });

        function populateRules(categoryId) {
            $ruleSelect.html('<option value="">Seleccionar regla</option>');
            if (!categoryId || !rulesMap[categoryId]) {
                return;
            }

            rulesMap[categoryId].forEach(rule => {
                $ruleSelect.append(`<option value="${rule.id}" data-interest-rate="${rule.interest_rate}" data-interest-period="${rule.interest_period}" data-term="${rule.term_months_limit}">${rule.name} - ${rule.interest_rate}% (${rule.interest_period}) / ${rule.term_months_limit} meses</option>`);
            });
        }

        function syncCategoryByProduct() {
            const product = productsMap.find(p => String(p.id) === String($productId.val()));
            const categoryId = product?.credit_category_id;
            if (categoryId) {
                $categorySelect.val(String(categoryId)).trigger('change');
                $categorySelect.prop('disabled', true);
            } else {
                $categorySelect.prop('disabled', false);
            }
        }

        $categorySelect.on('change', function () {
            populateRules($(this).val());
        });

        // Cuando asignas categoría a un nuevo producto, automáticamente la categoría del préstamo se sincroniza
        $productCategory.on('change', function () {
            const categoryId = $(this).val();
            if (categoryId) {
                $categorySelect.val(String(categoryId)).trigger('change');
            }
        });

        $ruleSelect.on('change', function () {
            const selected = $(this).find(':selected');
            if (!selected.val()) {
                return;
            }
            $interestInput.val(selected.data('interest-rate'));
            $periodSelect.val(selected.data('interest-period'));
            $termInput.val(selected.data('term'));
            updatePreview();
        });

        function getMonthlyRate(rate, period) {
            const r = parseFloat(rate || 0);
            if (period === 'quarterly') return r / 3;
            if (period === 'semiannual') return r / 6;
            if (period === 'annual') return r / 12;
            return r;
        }

        function amortizedMonthlyPayment(amount, monthlyRatePercent, months) {
            const principal = parseFloat(amount || 0);
            const n = parseInt(months || 0, 10);
            const r = (parseFloat(monthlyRatePercent || 0) / 100);
            if (!principal || !n) return 0;
            if (r === 0) return principal / n;
            return principal * (r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
        }

        function fmtMoney(v) {
            return '$' + (Number(v || 0)).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updatePreview() {
            const amount = parseFloat($amountInput.val() || 0);
            const rate = parseFloat($interestInput.val() || 0);
            const period = $periodSelect.val();
            const months = parseInt($termInput.val() || 0, 10);

            if (!amount || !months) {
                $preview.hide();
                return;
            }

            const monthlyRate = getMonthlyRate(rate, period);
            const monthly = amortizedMonthlyPayment(amount, monthlyRate, months);
            const total = monthly * months;

            $previewMonthly.text(fmtMoney(monthly));
            $previewTotal.text(fmtMoney(total));
            $previewTerm.text(`${months} meses · ${period === 'monthly' ? 'Interés mensual' : period === 'quarterly' ? 'Interés trimestral' : period === 'semiannual' ? 'Interés semestral' : 'Interés anual'}`);
            $preview.show();
        }

        $amountInput.on('input', updatePreview);
        $interestInput.on('input', updatePreview);
        $periodSelect.on('change', updatePreview);
        $termInput.on('input', updatePreview);

        const oldClientId = '{{ old('client_id', request('client_id')) }}';
        const oldClientName = '{{ old('client_name') }}';
        setClientMode(oldClientId ? 'existing' : (oldClientName ? 'new' : 'existing'));
        if (oldClientId && !$clientSelect.val()) {
            $clientSelect.val(oldClientId).trigger('change');
        }

        const oldProductId = '{{ old('product_id') }}';
        if (oldProductId) {
            selectProductById(oldProductId);
        } else {
            $clearProduct.toggle(!!$productInput.val());
        }

        syncCategoryByProduct();
        populateRules($categorySelect.val());
        const oldRule = '{{ old('credit_category_rule_id') }}';
        if (oldRule) {
            $ruleSelect.val(oldRule).trigger('change');
        }

        updatePreview();
    });
</script>
@endpush
