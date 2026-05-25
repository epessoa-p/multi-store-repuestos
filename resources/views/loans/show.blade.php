@extends('layouts.app')

@section('title', 'Préstamo #' . $loan->id)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .loan-stat { border-left: 3px solid; padding-left: 12px; }
    .loan-stat.primary { border-color: #0d6efd; }
    .loan-stat.success { border-color: #198754; }
    .loan-stat.danger { border-color: #dc3545; }
    .loan-stat.warning { border-color: #ffc107; }
    .loan-stat .stat-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; }
    .loan-stat .stat-value { font-size: 1.15rem; font-weight: 700; }

    /* ── Improved tab styling ─────────────────── */
    #loanActionTabs {
        background: var(--bs-primary);
        border-bottom: none;
        gap: .25rem;
        padding: .3rem .35rem 0;
        border-radius: .6rem .6rem 0 0;
    }
    #loanActionTabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: rgba(255, 255, 255, .86);
        font-weight: 600;
        font-size: .88rem;
        padding: .65rem 1.1rem;
        border-radius: .45rem .45rem 0 0;
        transition: all .2s ease;
    }
    #loanActionTabs .nav-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, .16);
        border-bottom-color: transparent;
    }
    #loanActionTabs .nav-link.active {
        color: #0d6efd;
        background: #fff;
        border-bottom-color: transparent;
        box-shadow: 0 -1px 0 rgba(255, 255, 255, .7);
    }
    #loanActionTabs .nav-link i { opacity: .9; }
    #loanActionTabs .nav-link.active i { opacity: 1; }

    /* Gallery thumbnails */
    .loan-gallery-thumb {
        width: 80px; height: 80px; object-fit: cover;
        border-radius: 8px; border: 2px solid #e9ecef;
        cursor: pointer; transition: border-color .2s;
    }
    .loan-gallery-thumb:hover { border-color: #0d6efd; }

    /* Interest period cards */
    .interest-period-card {
        border-radius: 12px;
        border: 1px solid #e9ecef;
        transition: box-shadow .2s;
    }
    .interest-period-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .interest-period-card .period-badge {
        width: 36px; height: 36px;
        border-radius: 50%; display: flex;
        align-items: center; justify-content: center;
        font-weight: 700; font-size: .85rem;
    }

    /* Signature pads */
    .signature-canvas {
        width: 100%; height: 120px;
        border: 1px dashed #adb5bd;
        border-radius: 8px;
        background: #fff;
        cursor: crosshair;
    }

    /* Penalty alert */
    .penalty-alert { animation: pulse-border 2s infinite; }
    @keyframes pulse-border {
        0%, 100% { border-color: #dc3545; }
        50% { border-color: #ffc107; }
    }
</style>
@endpush

@section('page')
@php
    $currentCapital = $loan->getCurrentCapital();
    $unpaidInterest = $loan->getUnpaidInterestTotal();
    $liquidationAmount = $loan->getLiquidationAmount();
    $statusColors = [
        'active' => 'success', 'pending' => 'warning', 'approved' => 'info',
        'finished' => 'secondary', 'cancelled' => 'dark', 'overdue' => 'danger',
    ];
    $statusLabels = [
        'active' => 'Activo', 'pending' => 'Pendiente', 'approved' => 'Aprobado',
        'finished' => 'Finalizado', 'cancelled' => 'Cancelado', 'overdue' => 'Vencido',
    ];
    $penaltyLabels = [
        'normal' => 'Normal', 'overdue' => 'En mora', 'grace' => 'Periodo de gracia', 'defaulted' => 'Incumplido',
    ];
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0 fs-4 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-cash-coin text-primary"></i>
            Préstamo #{{ $loan->id }}
            <span class="badge bg-{{ $statusColors[$loan->status] ?? 'secondary' }} fs-6">{{ $statusLabels[$loan->status] ?? ucfirst($loan->status) }}</span>
            @if($loan->penalty_status !== 'normal')
                <span class="badge bg-danger fs-6 ms-1">{{ $penaltyLabels[$loan->penalty_status] ?? $loan->penalty_status }}</span>
            @endif
        </h1>
    </div>
    <a href="{{ route('loans.index') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

{{-- Penalty alert --}}
@if($loan->penalty_status !== 'normal')
<div class="alert alert-danger penalty-alert d-flex align-items-center gap-2 mb-4" style="border-width:2px;">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div>
        <strong>Préstamo en {{ $penaltyLabels[$loan->penalty_status] ?? 'alerta' }}</strong>
        @if((float) $loan->penalty_amount > 0)
            — Multa aplicada: <strong>Bs {{ number_format((float) $loan->penalty_amount, 2) }}</strong>
        @endif
        @if($loan->penalty_status === 'defaulted')
            <br><small class="text-muted">El producto pasa a estado de venta si no se liquida.</small>
        @endif
    </div>
</div>
@endif

{{-- Stats bar --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="loan-stat primary">
            <div class="stat-label">Capital inicial</div>
            <div class="stat-value text-primary">Bs {{ number_format((float) $loan->amount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="loan-stat warning">
            <div class="stat-label">Capital vigente</div>
            <div class="stat-value text-warning">Bs {{ number_format($currentCapital, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="loan-stat success">
            <div class="stat-label">Total pagado</div>
            <div class="stat-value text-success">Bs {{ number_format((float) $loan->total_paid, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="loan-stat danger">
            <div class="stat-label">Para liquidar</div>
            <div class="stat-value text-danger">Bs {{ number_format($liquidationAmount, 2) }}</div>
        </div>
    </div>
</div>

{{-- Detail cards --}}
<div class="row g-4 mb-4">
    {{-- Client info --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-fill text-primary me-1"></i> Cliente</h6>
                <div class="mb-2"><strong>{{ $loan->client?->name ?? 'Sin cliente' }}</strong></div>
                <div class="small text-muted mb-1"><i class="bi bi-card-text me-1"></i> {{ $loan->client?->id_number ?? '-' }}</div>
                <div class="small text-muted mb-1"><i class="bi bi-telephone me-1"></i> {{ $loan->client?->phone ?? '-' }}</div>
                <div class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i> {{ $loan->client?->email ?? '-' }}</div>
                <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i> {{ $loan->client?->address ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Loan details --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 text-success me-1"></i> Condiciones</h6>
                <div class="row g-2 small">
                    <div class="col-6"><span class="text-muted">Producto:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->product?->name ?? '-' }}</div>
                    <div class="col-6"><span class="text-muted">Categoría:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->creditCategory?->name ?? '-' }}</div>
                    <div class="col-6"><span class="text-muted">Tasa de interés:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->interest_rate }}%</div>
                    <div class="col-6"><span class="text-muted">Periodicidad:</span></div>
                    <div class="col-6 fw-semibold">{{ ucfirst($loan->interest_period ?? 'Mensual') }}</div>
                    <div class="col-6"><span class="text-muted">Plazo límite:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->term_months }} meses</div>
                    <div class="col-6"><span class="text-muted">Interés por periodo:</span></div>
                    <div class="col-6 fw-semibold text-info">Bs {{ number_format($loan->calculatePeriodInterest(), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Status / dates --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 text-warning me-1"></i> Información</h6>
                <div class="row g-2 small">
                    <div class="col-6"><span class="text-muted">Inicio:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->start_date?->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-6"><span class="text-muted">Vence:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->end_date?->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-6"><span class="text-muted">Creado por:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->createdBy?->name ?? '-' }}</div>
                    <div class="col-6"><span class="text-muted">Fecha creación:</span></div>
                    <div class="col-6 fw-semibold">{{ $loan->created_at?->format('d/m/Y H:i') }}</div>
                    @php $collateralStatus = $loan->collateral->first()?->status; @endphp
                    <div class="col-6"><span class="text-muted">Prenda:</span></div>
                    <div class="col-6">
                        @if($collateralStatus === 'retained')
                            <span class="badge bg-warning text-dark">En retención</span>
                        @elseif($collateralStatus === 'sellable')
                            <span class="badge bg-danger">Disponible venta</span>
                        @elseif($collateralStatus === 'released')
                            <span class="badge bg-success">Liberada</span>
                        @elseif($collateralStatus === 'sold')
                            <span class="badge bg-dark">Vendida</span>
                        @else
                            <span class="badge bg-secondary">Sin prenda</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Photos gallery --}}
@if($loan->images->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-images text-primary me-1"></i> Fotos del préstamo ({{ $loan->images->count() }})</h6>
        <div class="d-flex flex-wrap gap-2">
            @foreach($loan->images as $img)
                <a href="{{ asset('storage/' . $img->path) }}" target="_blank">
                    <img src="{{ asset('storage/' . $img->path) }}" alt="{{ $img->original_name }}" class="loan-gallery-thumb" title="{{ $img->original_name }}">
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Payments history --}}
@if($loan->payments->count() > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-receipt text-info me-1"></i> Historial de pagos ({{ $loan->payments->count() }})</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Capital</th>
                        <th>Interés</th>
                        <th>Método</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loan->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td>
                                @if($payment->payment_type === 'interest')
                                    <span class="badge bg-info">Interés</span>
                                @elseif($payment->payment_type === 'capital')
                                    <span class="badge bg-primary">Capital</span>
                                @else
                                    <span class="badge bg-secondary">Mixto</span>
                                @endif
                            </td>
                            <td class="fw-semibold">Bs {{ number_format((float) $payment->amount, 2) }}</td>
                            <td>Bs {{ number_format((float) ($payment->capital ?? 0), 2) }}</td>
                            <td>Bs {{ number_format((float) ($payment->interest ?? 0), 2) }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td class="text-muted">{{ $payment->reference ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Action Tabs ─────────────────────────────────────────── --}}
@if(in_array($loan->status, ['active', 'overdue']))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pb-0 pt-3 px-3">
        <ul class="nav nav-tabs" id="loanActionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-contract" data-bs-toggle="tab" data-bs-target="#pane-contract" type="button" role="tab">
                    <i class="bi bi-file-earmark-text me-1"></i> Contrato
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-interest" data-bs-toggle="tab" data-bs-target="#pane-interest" type="button" role="tab">
                    <i class="bi bi-percent me-1"></i> Interés
                    @php $pendingInterests = $loan->interestPayments->where('status', 'pending')->count(); @endphp
                    @if($pendingInterests > 0)
                        <span class="badge bg-danger rounded-pill ms-1">{{ $pendingInterests }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-amortize" data-bs-toggle="tab" data-bs-target="#pane-amortize" type="button" role="tab">
                    <i class="bi bi-graph-down-arrow me-1"></i> Amortizar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-liquidate" data-bs-toggle="tab" data-bs-target="#pane-liquidate" type="button" role="tab">
                    <i class="bi bi-check2-all me-1"></i> Liquidar
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body pt-4">
        <div class="tab-content" id="loanActionTabsContent">

            {{-- ═══ Tab: Contrato ═══ --}}
            <div class="tab-pane fade show active" id="pane-contract" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-text text-primary me-1"></i> Contrato del préstamo</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('loans.contract.download.pdf', $loan) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF contrato</a>
                        <button type="button" id="btn-preview-pdf" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF vista previa</button>
                        <button type="button" id="btn-download-word" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-word me-1"></i> Word</button>
                    </div>
                </div>

                {{-- Template selector --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><i class="bi bi-file-earmark-ruled me-1"></i> Cargar desde plantilla</label>
                    <div class="d-flex gap-2">
                        <select id="template-selector" class="form-select form-select-sm" style="max-width:350px;">
                            <option value="">— Seleccionar plantilla —</option>
                            @foreach($contractTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="load-template-btn" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i> Cargar
                        </button>
                    </div>
                    <small class="text-muted">Las variables del préstamo se reemplazan automáticamente</small>
                </div>

                <form action="{{ route('loans.contract.update', $loan) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <textarea id="contract-content" name="content" class="form-control mb-3" rows="14">{{ old('content', $loan->contract?->content) }}</textarea>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Adjuntar documentos</label>
                            <input type="file" name="attachments[]" class="form-control form-control-sm" multiple accept=".pdf,.jpg,.jpeg,.png">
                            @if($loan->contract?->attachments?->count())
                                <div class="mt-2">
                                    @foreach($loan->contract->attachments as $file)
                                        <span class="badge bg-light text-dark border me-1">
                                            <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank">{{ $file->file_name }}</a>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Firma del Prestamista --}}
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <label class="form-label small fw-semibold"><i class="bi bi-pen me-1"></i> Firma del Prestamista</label>
                                    <canvas id="lender-signature-pad" class="signature-canvas" width="400" height="120"></canvas>
                                    <input type="hidden" id="lender_signature_data" name="lender_signature_data">
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-light border clear-sig-btn" data-target="lender">
                                            <i class="bi bi-eraser me-1"></i> Limpiar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary capture-sig-btn" data-target="lender">
                                            <i class="bi bi-check2 me-1"></i> Capturar
                                        </button>
                                    </div>
                                    @if($loan->contract?->lender_signature_path)
                                        <div class="mt-2">
                                            <small class="text-success"><i class="bi bi-check-circle me-1"></i> Firma registrada</small>
                                            <img src="{{ asset('storage/' . $loan->contract->lender_signature_path) }}" class="img-fluid rounded border d-block mt-1" alt="Firma prestamista" style="max-height:50px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Firma del Cliente --}}
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <label class="form-label small fw-semibold"><i class="bi bi-pen me-1"></i> Firma del Cliente</label>
                                    <canvas id="client-signature-pad" class="signature-canvas" width="400" height="120"></canvas>
                                    <input type="hidden" id="client_signature_data" name="client_signature_data">
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-light border clear-sig-btn" data-target="client">
                                            <i class="bi bi-eraser me-1"></i> Limpiar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary capture-sig-btn" data-target="client">
                                            <i class="bi bi-check2 me-1"></i> Capturar
                                        </button>
                                    </div>
                                    @if($loan->contract?->client_signature_path)
                                        <div class="mt-2">
                                            <small class="text-success"><i class="bi bi-check-circle me-1"></i> Firma registrada</small>
                                            <img src="{{ asset('storage/' . $loan->contract->client_signature_path) }}" class="img-fluid rounded border d-block mt-1" alt="Firma cliente" style="max-height:50px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-save me-1"></i> Guardar contrato</button>
                </form>

                <form id="contract-export-form" method="POST" target="_blank" class="d-none">
                    @csrf
                    <input type="hidden" name="content" id="contract-export-content">
                </form>
            </div>

            {{-- ═══ Tab: Interés ═══ --}}
            <div class="tab-pane fade" id="pane-interest" role="tabpanel">
                {{-- Resumen --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 bg-light text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Capital vigente</small>
                            <div class="fw-bold fs-5 text-primary">Bs {{ number_format($currentCapital, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Tasa interés</small>
                            <div class="fw-bold fs-5">{{ $loan->interest_rate }}%</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Interés actual</small>
                            <div class="fw-bold fs-5 text-info">Bs {{ number_format($loan->calculatePeriodInterest(), 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Interés pendiente</small>
                            <div class="fw-bold fs-5 text-danger">Bs {{ number_format($unpaidInterest, 2) }}</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-calendar2-check me-1"></i> Periodos de interés</h6>

                @if($loan->interestPayments->isEmpty())
                    <div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i> No hay periodos de interés generados aún.</div>
                @else
                    <div class="row g-3">
                        @foreach($loan->interestPayments->sortBy('period_number') as $ip)
                            @php
                                $isPaid = $ip->status === 'paid';
                                $isOverdue = !$isPaid && $ip->period_end->lt(now());
                                $remaining = $ip->getRemainingAmount();
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                <div class="interest-period-card p-3 {{ $isPaid ? 'border-success' : ($isOverdue ? 'border-danger' : 'border-warning') }}">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="period-badge {{ $isPaid ? 'bg-success text-white' : ($isOverdue ? 'bg-danger text-white' : 'bg-warning text-dark') }}">
                                            #{{ $ip->period_number }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ $ip->period_start->format('d/m/Y') }} — {{ $ip->period_end->format('d/m/Y') }}</div>
                                            <div class="fw-bold">
                                                @if($isPaid)
                                                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Pagado</span>
                                                @elseif($isOverdue)
                                                    <span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i> Vencido</span>
                                                @else
                                                    <span class="text-warning"><i class="bi bi-clock me-1"></i> Pendiente</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-1 small mb-2">
                                        <div class="col-6 text-muted">Base capital:</div>
                                        <div class="col-6 fw-semibold">Bs {{ number_format((float) $ip->base_amount, 2) }}</div>
                                        <div class="col-6 text-muted">Interés ({{ $ip->interest_rate }}%):</div>
                                        <div class="col-6 fw-semibold">Bs {{ number_format((float) $ip->interest_amount, 2) }}</div>
                                        @if((float) $ip->paid_amount > 0)
                                            <div class="col-6 text-muted">Pagado:</div>
                                            <div class="col-6 fw-semibold text-success">Bs {{ number_format((float) $ip->paid_amount, 2) }}</div>
                                        @endif
                                    </div>

                                    @if(!$isPaid)
                                        <form action="{{ route('loans.interest.store', $loan) }}" method="POST" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="interest_payment_id" value="{{ $ip->id }}">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Bs</span>
                                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ number_format($remaining, 2, '.', '') }}" max="{{ $remaining }}" min="0.01" required>
                                                <select name="payment_method" class="form-select" style="max-width:120px;">
                                                    <option value="efectivo">Efectivo</option>
                                                    <option value="transferencia">Transfer.</option>
                                                    <option value="tarjeta">Tarjeta</option>
                                                </select>
                                                <button class="btn btn-success" type="submit" title="Pagar interés">
                                                    <i class="bi bi-check-lg"></i> Pagar
                                                </button>
                                            </div>
                                            <input type="text" name="reference" class="form-control form-control-sm mt-1" placeholder="Referencia (opcional)">
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ═══ Tab: Amortizar ═══ --}}
            <div class="tab-pane fade" id="pane-amortize" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 bg-light text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Capital inicial</small>
                            <div class="fw-bold fs-5">Bs {{ number_format((float) $loan->amount, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-primary bg-opacity-10 text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Capital vigente</small>
                            <div class="fw-bold fs-5 text-primary">Bs {{ number_format($currentCapital, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-light text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Total amortizado</small>
                            <div class="fw-bold fs-5 text-success">Bs {{ number_format((float) $loan->amount - $currentCapital, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info small py-2 mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Al amortizar, el capital vigente se reduce. El próximo periodo de interés se calculará sobre el nuevo capital.
                    <br>Ejemplo: Capital Bs {{ number_format($currentCapital, 2) }} - Amortización Bs 50 = Nuevo capital Bs {{ number_format(max(0, $currentCapital - 50), 2) }} → Interés {{ $loan->interest_rate }}% = Bs {{ number_format(max(0, $currentCapital - 50) * ((float)$loan->interest_rate / 100), 2) }}
                </div>

                <form action="{{ route('loans.amortize.store', $loan) }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Monto a amortizar</label>
                        <div class="input-group">
                            <span class="input-group-text">Bs</span>
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $currentCapital }}" class="form-control" placeholder="0.00" required>
                        </div>
                        <small class="text-muted">Máx: Bs {{ number_format($currentCapital, 2) }}</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Fecha</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Método de pago</label>
                        <select name="payment_method" class="form-select">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-arrow-down-circle me-1"></i> Amortizar</button>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Referencia</label>
                        <input type="text" name="reference" class="form-control form-control-sm" placeholder="Nro. comprobante">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Notas</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Observaciones">
                    </div>
                </form>

                {{-- Historial de amortizaciones --}}
                @if($loan->amortizations->count() > 0)
                    <hr class="my-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Historial de amortizaciones</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Capital antes</th>
                                    <th>Capital después</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loan->amortizations->sortByDesc('created_at') as $amort)
                                    <tr>
                                        <td>{{ $amort->payment_date->format('d/m/Y') }}</td>
                                        <td class="fw-semibold text-success">Bs {{ number_format((float) $amort->amount, 2) }}</td>
                                        <td>Bs {{ number_format((float) $amort->capital_before, 2) }}</td>
                                        <td class="fw-semibold">Bs {{ number_format((float) $amort->capital_after, 2) }}</td>
                                        <td>{{ ucfirst($amort->payment_method) }}</td>
                                        <td class="text-muted">{{ $amort->reference ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ═══ Tab: Liquidar ═══ --}}
            <div class="tab-pane fade" id="pane-liquidate" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 bg-primary bg-opacity-10 text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Capital vigente</small>
                            <div class="fw-bold fs-5 text-primary">Bs {{ number_format($currentCapital, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-warning bg-opacity-10 text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Interés pendiente</small>
                            <div class="fw-bold fs-5 text-warning">Bs {{ number_format($unpaidInterest, 2) }}</div>
                        </div>
                    </div>
                    @if((float) $loan->penalty_amount > 0)
                    <div class="col-md-4">
                        <div class="card border-0 bg-danger bg-opacity-10 text-center p-3">
                            <small class="text-muted text-uppercase" style="font-size:.7rem;">Multa</small>
                            <div class="fw-bold fs-5 text-danger">Bs {{ number_format((float) $loan->penalty_amount, 2) }}</div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="alert alert-warning py-2 mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <i class="bi bi-calculator me-1"></i>
                            <strong>Total para liquidar:</strong>
                            <span class="fs-5 fw-bold">Bs {{ number_format($liquidationAmount, 2) }}</span>
                        </div>
                        <div class="small">
                            Capital Bs {{ number_format($currentCapital, 2) }}
                            + Interés Bs {{ number_format($unpaidInterest, 2) }}
                            @if((float) $loan->penalty_amount > 0)
                                + Multa Bs {{ number_format((float) $loan->penalty_amount, 2) }}
                            @endif
                        </div>
                    </div>
                </div>

                <form action="{{ route('loans.liquidate.store', $loan) }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text">Bs</span>
                            <input type="number" name="amount" step="0.01" min="{{ $liquidationAmount }}" class="form-control" value="{{ number_format($liquidationAmount, 2, '.', '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Fecha</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Método</label>
                        <select name="payment_method" class="form-select">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Referencia</label>
                        <input type="text" name="reference" class="form-control" placeholder="Nro. comprobante">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Notas</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Observaciones">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-danger" type="submit" onclick="return confirm('¿Confirma liquidar este préstamo? Esta acción no se puede deshacer.')">
                            <i class="bi bi-lock-fill me-1"></i> Liquidar préstamo
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
$(function () {
    // ── Summernote ──
    const $editor = $('#contract-content');
    if ($editor.length && typeof $editor.summernote === 'function') {
        $editor.summernote({
            placeholder: 'Redacta aquí el contrato...',
            tabsize: 2,
            height: 350,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'hr']],
                ['view', ['codeview', 'help', 'fullscreen']],
                ['history', ['undo', 'redo']]
            ]
        });
    }

    // ── Template loader ──
    const templatesData = @json($contractTemplates->keyBy('id')->map(fn($t) => $t->applyToLoan($loan)));
    $('#load-template-btn').on('click', function () {
        const id = $('#template-selector').val();
        if (!id) return;
        const html = templatesData[id] || '';
        if (typeof $editor.summernote === 'function') {
            $editor.summernote('code', html);
        } else {
            $editor.val(html);
        }
    });

    function getContractContent() {
        if ($editor.length && typeof $editor.summernote === 'function') {
            return $editor.summernote('code') || '';
        }
        return $editor.val() || '';
    }

    function submitContractExport(actionUrl) {
        const form = document.getElementById('contract-export-form');
        const contentInput = document.getElementById('contract-export-content');
        if (!form || !contentInput) return;
        contentInput.value = getContractContent();
        form.action = actionUrl;
        form.submit();
    }

    $('#btn-preview-pdf').on('click', function () {
        submitContractExport("{{ route('loans.contract.download.pdf', $loan) }}");
    });

    $('#btn-download-word').on('click', function () {
        submitContractExport("{{ route('loans.contract.download.word', $loan) }}");
    });

    // ── Dual Signature Pads ──
    function initSignaturePad(canvasId, hiddenId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const hidden = document.getElementById(hiddenId);
        let drawing = false;

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const t = e.touches ? e.touches[0] : e;
            return { x: (t.clientX - rect.left) * scaleX, y: (t.clientY - rect.top) * scaleY };
        }

        function start(e) { drawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function move(e) { if (!drawing) return; const p = getPos(e); ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#1f2937'; ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
        function end() { drawing = false; }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);

        return { canvas, ctx, hidden };
    }

    const lenderPad = initSignaturePad('lender-signature-pad', 'lender_signature_data');
    const clientPad = initSignaturePad('client-signature-pad', 'client_signature_data');

    // Clear / Capture buttons
    document.querySelectorAll('.clear-sig-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.dataset.target;
            const pad = target === 'lender' ? lenderPad : clientPad;
            if (pad) {
                pad.ctx.clearRect(0, 0, pad.canvas.width, pad.canvas.height);
                pad.hidden.value = '';
            }
        });
    });

    document.querySelectorAll('.capture-sig-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.dataset.target;
            const pad = target === 'lender' ? lenderPad : clientPad;
            if (pad) {
                pad.hidden.value = pad.canvas.toDataURL('image/png');
                const icon = this.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'bi bi-check-circle-fill me-1';
                this.classList.add('btn-success');
                this.classList.remove('btn-outline-primary');
                setTimeout(() => {
                    icon.className = originalClass;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-primary');
                }, 2000);
            }
        });
    });
});
</script>
@endpush
@endsection
