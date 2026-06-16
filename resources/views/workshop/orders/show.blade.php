@extends('layouts.app')
@section('title', 'OT: ' . $order->code)
@section('page')

@php
    $editable = !in_array($order->status, ['entregada', 'anulada']);
    $canEdit  = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.edit', auth()->user()->getCurrentCompany());
    $canDeliver = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.deliver', auth()->user()->getCurrentCompany());
@endphp

<div class="container-fluid">

    {{-- Print header (hidden on screen) --}}
    <div class="print-header d-none">
        <h3 class="fw-bold mb-1">VR Motors — Taller</h3>
        <p class="mb-0 text-muted small">Orden de Trabajo</p>
    </div>

    {{-- ── HEADER CARD ──────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden no-print-shadow">
        <div style="height:6px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff4d4d 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $order->code }}</h1>
                        <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle">
                            {{ $order->status_label }}
                        </span>
                        <span class="badge bg-{{ $order->payment_status_color }}-subtle text-{{ $order->payment_status_color }} border border-{{ $order->payment_status_color }}-subtle">
                            {{ $order->payment_status_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-person me-1"></i>{{ $order->client?->full_name ?? '—' }}</span>
                        <span><i class="bi bi-car-front me-1"></i>{{ $order->vehicle?->display_name ?? '—' }}</span>
                        @if($order->mechanic)
                        <span><i class="bi bi-person-gear me-1"></i>{{ $order->mechanic->name }}</span>
                        @endif
                        @if($order->branch)
                        <span><i class="bi bi-building me-1"></i>{{ $order->branch->name }}</span>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($order->reception_date)->format('d/m/Y') }}</span>
                        @if($order->createdBy)
                        <span><i class="bi bi-person-badge me-1"></i>{{ $order->createdBy->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">

                    {{-- Status change buttons --}}
                    @if($editable && $canEdit)
                        @if($order->status === 'recibida')
                        <form action="{{ route('workshop.orders.status', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="diagnosticada">
                            <button class="btn btn-light border btn-sm">
                                <i class="bi bi-search me-1"></i>Diagnosticada
                            </button>
                        </form>
                        @endif
                        @if(in_array($order->status, ['recibida', 'diagnosticada']))
                        <form action="{{ route('workshop.orders.status', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="en_proceso">
                            <button class="btn btn-light border btn-sm">
                                <i class="bi bi-wrench me-1"></i>En proceso
                            </button>
                        </form>
                        @endif
                        @if(in_array($order->status, ['recibida', 'diagnosticada', 'en_proceso']))
                        <form action="{{ route('workshop.orders.status', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="terminada">
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-check-circle me-1"></i>Terminada
                            </button>
                        </form>
                        @endif
                    @endif

                    {{-- Deliver button --}}
                    @if($order->status === 'terminada' && $canDeliver)
                    <a href="{{ route('workshop.deliveries.create', $order) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Entregar / Cobrar
                    </a>
                    @endif

                    {{-- Register payment if delivered with balance --}}
                    @if($order->status === 'entregada' && $order->balance > 0 && $canDeliver)
                    <button type="button" class="btn btn-primary btn-sm no-print"
                            data-bs-toggle="modal" data-bs-target="#woPayModal">
                        <i class="bi bi-cash-coin me-1"></i>Registrar pago
                    </button>
                    @endif

                    {{-- Edit / Cancel --}}
                    @if($editable && $canEdit)
                    <a href="{{ route('workshop.orders.edit', $order) }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    <form action="{{ route('workshop.orders.cancel', $order) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('¿Anular la OT {{ addslashes($order->code) }}? Esta acción no se puede deshacer.')">
                        @csrf
                        <button class="btn btn-sm btn-light border text-danger">
                            <i class="bi bi-x-circle me-1"></i>Anular
                        </button>
                    </form>
                    @endif

                    <button onclick="window.print()" class="btn btn-light border btn-sm">
                        <i class="bi bi-printer me-1"></i>Imprimir
                    </button>
                    <a href="{{ route('workshop.orders.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── LEFT COLUMN ──────────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Recepción --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2 me-2 text-muted"></i>Recepción</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-muted small mb-1">Kilometraje</div>
                            <div class="fw-semibold small">{{ $order->mileage ? number_format($order->mileage) . ' km' : '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small mb-1">Combustible</div>
                            <div class="fw-semibold small">{{ $order->fuel_level ?: '—' }}</div>
                        </div>
                        @if($order->reported_issue)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Falla reportada</div>
                            <div class="small">{{ $order->reported_issue }}</div>
                        </div>
                        @endif
                        @if($order->received_items)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Objetos / accesorios recibidos</div>
                            <div class="small">{{ $order->received_items }}</div>
                        </div>
                        @endif
                        @if($order->notes)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Notas</div>
                            <div class="small text-muted">{{ $order->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Diagnóstico --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-search me-2 text-muted"></i>Diagnóstico</h6>
                </div>
                <div class="card-body p-4">
                    @if($order->diagnosis)
                    <p class="small mb-3">{{ $order->diagnosis }}</p>
                    @else
                    <p class="text-muted small mb-3"><em>Sin diagnóstico registrado.</em></p>
                    @endif
                    @if($editable && $canEdit)
                    <form action="{{ route('workshop.orders.diagnosis', $order) }}" method="POST" class="no-print">
                        @csrf
                        <div class="d-flex gap-2 align-items-start">
                            <textarea name="diagnosis" rows="2"
                                      class="form-control form-control-sm flex-grow-1"
                                      placeholder="Ingresa el diagnóstico técnico...">{{ old('diagnosis', $order->diagnosis) }}</textarea>
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="bi bi-check-lg me-1"></i>Guardar
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Servicios --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2 text-muted"></i>Servicios</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Descripción</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Mecánico</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Precio</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="font-size:.72rem;">Cant.</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Subtotal</th>
                                    @if($editable && $canEdit)
                                    <th class="py-3 pe-3" style="width:44px;"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->services as $svc)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-2 small">{{ $svc->pivot->description ?? $svc->name }}</td>
                                    <td class="py-2 small text-muted">{{ $svc->pivot->mechanic?->name ?? '—' }}</td>
                                    <td class="py-2 text-end small">${{ number_format($svc->pivot->price ?? $svc->price, 2) }}</td>
                                    <td class="py-2 text-center small">{{ $svc->pivot->quantity ?? 1 }}</td>
                                    <td class="py-2 text-end fw-semibold small">
                                        ${{ number_format(($svc->pivot->price ?? $svc->price) * ($svc->pivot->quantity ?? 1), 2) }}
                                    </td>
                                    @if($editable && $canEdit)
                                    <td class="py-2 pe-3">
                                        <form action="{{ route('workshop.orders.services.remove', [$order, $svc]) }}" method="POST" class="d-inline no-print"
                                              onsubmit="return confirm('¿Quitar este servicio?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light border text-danger py-0 px-1">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ $editable && $canEdit ? 6 : 5 }}" class="text-center py-4 text-muted">
                                        <i class="bi bi-gear d-block fs-3 mb-1 opacity-25"></i>
                                        Sin servicios agregados.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Add service form --}}
                @if($editable && $canEdit)
                <div class="card-footer bg-light border-top p-4 no-print">
                    <p class="small fw-semibold mb-3"><i class="bi bi-plus-circle me-1 text-muted"></i>Agregar servicio</p>
                    <form action="{{ route('workshop.orders.services.add', $order) }}" method="POST">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold">Servicio</label>
                                <select name="service_id" class="form-select form-select-sm" id="svcSelect"
                                        onchange="onSvcChange()">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($services as $s)
                                    <option value="{{ $s->id }}" data-price="{{ $s->price }}" data-name="{{ $s->name }}">
                                        {{ $s->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold">Descripción</label>
                                <input type="text" name="description" id="svcDesc" class="form-control form-control-sm"
                                       placeholder="Descripción del servicio">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold">Precio</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">$</span>
                                    <input type="number" name="price" id="svcPrice" class="form-control"
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label form-label-sm fw-semibold">Cant.</label>
                                <input type="number" name="quantity" class="form-control form-control-sm"
                                       min="1" step="1" inputmode="numeric" value="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold">Mecánico</label>
                                <select name="mechanic_id" class="form-select form-select-sm">
                                    <option value="">Sin asignar</option>
                                    @foreach($mechanics as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
            </div>

            {{-- Repuestos --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2 text-muted"></i>Repuestos</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Producto</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="font-size:.72rem;">Cant.</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Precio</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Subtotal</th>
                                    @if($editable && $canEdit)
                                    <th class="py-3 pe-3" style="width:44px;"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->parts as $part)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-2 small fw-semibold">{{ $part->product?->name ?? '—' }}</td>
                                    <td class="py-2 text-center small">{{ $part->quantity }}</td>
                                    <td class="py-2 text-end small">${{ number_format($part->unit_price, 2) }}</td>
                                    <td class="py-2 text-end fw-semibold small">${{ number_format($part->quantity * $part->unit_price, 2) }}</td>
                                    @if($editable && $canEdit)
                                    <td class="py-2 pe-3">
                                        <form action="{{ route('workshop.orders.parts.remove', [$order, $part]) }}" method="POST" class="d-inline no-print"
                                              onsubmit="return confirm('¿Quitar este repuesto?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light border text-danger py-0 px-1">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ $editable && $canEdit ? 5 : 4 }}" class="text-center py-4 text-muted">
                                        <i class="bi bi-box-seam d-block fs-3 mb-1 opacity-25"></i>
                                        Sin repuestos agregados.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($editable && $canEdit)
                <div class="card-footer bg-light border-top p-4 no-print">
                    <p class="small fw-semibold mb-1"><i class="bi bi-plus-circle me-1 text-muted"></i>Agregar repuesto</p>
                    <p class="text-muted small mb-3">El stock se descuenta al entregar la OT.</p>
                    <form action="{{ route('workshop.orders.parts.add', $order) }}" method="POST">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label form-label-sm fw-semibold">Producto</label>
                                <select name="product_id" class="form-select form-select-sm" id="partSelect"
                                        onchange="onPartChange()">
                                    <option value="">— Seleccionar producto —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}"
                                            data-price="{{ $p->price }}"
                                            data-stock="{{ $p->current_stock }}">
                                        {{ $p->name }} (stock: {{ $p->current_stock }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold">Cantidad</label>
                                <input type="number" name="quantity" class="form-control form-control-sm"
                                       min="1" step="1" inputmode="numeric" value="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold">Precio unitario</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">$</span>
                                    <input type="number" name="unit_price" id="partPrice" class="form-control"
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
            </div>

        </div>

        {{-- ── RIGHT COLUMN ─────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="sticky-top" style="top:20px">

                {{-- Datos del cliente --}}
                @if($order->client)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person-vcard me-2 text-muted"></i>Datos del cliente</h6>
                    </div>
                    <div class="card-body p-4">
                        <dl class="row g-2 small mb-3">
                            <dt class="col-5 text-muted fw-normal">Nombre</dt>
                            <dd class="col-7 mb-0 fw-semibold">{{ $order->client->full_name }}</dd>
                            @if($order->client->id_number)
                            <dt class="col-5 text-muted fw-normal">Documento</dt>
                            <dd class="col-7 mb-0">{{ $order->client->id_number }}</dd>
                            @endif
                            @if($order->client->phone)
                            <dt class="col-5 text-muted fw-normal">Teléfono</dt>
                            <dd class="col-7 mb-0">
                                <a href="tel:{{ $order->client->phone }}" class="text-decoration-none">{{ $order->client->phone }}</a>
                            </dd>
                            @endif
                            @if($order->client->email)
                            <dt class="col-5 text-muted fw-normal">Email</dt>
                            <dd class="col-7 mb-0 text-truncate">
                                <a href="mailto:{{ $order->client->email }}" class="text-decoration-none">{{ $order->client->email }}</a>
                            </dd>
                            @endif
                        </dl>
                        <a href="{{ route('clients.show', $order->client) }}" class="btn btn-light border btn-sm w-100 no-print">
                            <i class="bi bi-person-lines-fill me-1"></i>Ver ficha del cliente
                        </a>
                    </div>
                </div>
                @endif

                {{-- Vehículo --}}
                @if($order->vehicle)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-car-front me-2 text-muted"></i>Vehículo</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-1">{{ $order->vehicle->display_name }}</div>
                        @if($order->vehicle->plate)
                        <div class="text-muted small"><i class="bi bi-tag me-1"></i>{{ $order->vehicle->plate }}</div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Mecánico asignado --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person-gear me-2 text-muted"></i>Mecánico asignado</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-3">
                            {{ $order->mechanic?->name ?? 'Sin asignar' }}
                        </div>
                        @if($editable && $canEdit)
                        <form action="{{ route('workshop.orders.mechanic', $order) }}" method="POST" class="no-print">
                            @csrf
                            <select name="mechanic_id" class="form-select form-select-sm mb-2">
                                <option value="">Sin asignar</option>
                                @foreach($mechanics as $m)
                                <option value="{{ $m->id }}" {{ $order->mechanic_id == $m->id ? 'selected' : '' }}>
                                    {{ $m->name }}{{ $m->specialty ? ' — ' . $m->specialty : '' }}
                                </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-light border btn-sm w-100">
                                <i class="bi bi-check-lg me-1"></i>Asignar
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Totales --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2 text-muted"></i>Totales</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Subtotal servicios</span>
                            <span class="fw-semibold">${{ number_format($order->subtotal_services, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Subtotal repuestos</span>
                            <span class="fw-semibold">${{ number_format($order->subtotal_parts, 2) }}</span>
                        </div>
                        @if($order->discount)
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Descuento</span>
                            <span class="fw-semibold text-danger">-${{ number_format($order->discount, 2) }}</span>
                        </div>
                        @endif
                        @if($order->tax)
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Impuesto</span>
                            <span class="fw-semibold">${{ number_format($order->tax, 2) }}</span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-2">
                            <span>Total</span>
                            <span class="fs-6">${{ number_format($order->total, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-success">
                            <span>Pagado</span>
                            <span class="fw-semibold">${{ number_format($order->paid_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2 {{ $order->balance > 0 ? 'text-danger' : 'text-muted' }}">
                            <span>Saldo</span>
                            <span>${{ number_format($order->balance, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Cuotas --}}
                @if($order->installments->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar2-check me-2 text-muted"></i>Cuotas</h6>
                        <span class="badge bg-light text-muted border">{{ $order->installments->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size:.83rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-2 fw-semibold text-muted" style="font-size:.7rem;">#</th>
                                        <th class="py-2 fw-semibold text-muted" style="font-size:.7rem;">Vence</th>
                                        <th class="py-2 fw-semibold text-muted text-end" style="font-size:.7rem;">Monto</th>
                                        <th class="py-2 fw-semibold text-muted pe-3" style="font-size:.7rem;">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->installments as $inst)
                                    <tr class="border-bottom border-light">
                                        <td class="ps-3 py-2 small">{{ $inst->number }}</td>
                                        <td class="py-2 small {{ $inst->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                            {{ $inst->due_date->format('d/m/Y') }}
                                            @if($inst->is_overdue)
                                            <span class="d-block badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.6rem;">VENCIDA</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-end small fw-semibold">
                                            ${{ number_format($inst->amount, 2) }}
                                            @if($inst->balance > 0)
                                            <div class="text-danger" style="font-size:.7rem;">Saldo: ${{ number_format($inst->balance, 2) }}</div>
                                            @endif
                                        </td>
                                        <td class="py-2 pe-3">
                                            <span class="badge bg-{{ $inst->status_color }}-subtle text-{{ $inst->status_color }} border border-{{ $inst->status_color }}-subtle" style="font-size:.65rem;">
                                                {{ $inst->status_label }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Pagos --}}
                @if($order->payments->isNotEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2 text-muted"></i>Pagos</h6>
                        <span class="badge bg-light text-muted border">{{ $order->payments->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        @foreach($order->payments as $payment)
                        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                            <div>
                                <div class="fw-semibold small">${{ number_format($payment->amount, 2) }}</div>
                                <div class="text-muted" style="font-size:.78rem;">
                                    {{ $payment->payment_date->format('d/m/Y') }}
                                    @if($payment->method) &middot; {{ ucfirst($payment->method) }} @endif
                                    @if($payment->reference) &middot; {{ $payment->reference }} @endif
                                </div>
                            </div>
                            <div class="text-muted small">{{ $payment->user?->name ?: '—' }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>

</div>

{{-- ── PAYMENT MODAL ─────────────────────────────────────────────────── --}}
@if($order->status === 'entregada' && $order->balance > 0 && $canDeliver)
<div class="modal fade" id="woPayModal" tabindex="-1" aria-labelledby="woPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="woPayModalLabel">
                    <i class="bi bi-cash-coin me-2 text-muted"></i>Registrar pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('workshop.orders.payment', $order) }}" method="POST">
                @csrf
                <div class="modal-body p-4">

                    <div class="rounded-3 border p-3 mb-4 bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">{{ $order->code }}</div>
                                <div class="text-muted small">{{ $order->client?->full_name ?? '—' }}</div>
                            </div>
                            <span class="badge bg-{{ $order->payment_status_color }}-subtle text-{{ $order->payment_status_color }} border border-{{ $order->payment_status_color }}-subtle">
                                {{ $order->payment_status_label }}
                            </span>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-4">
                                <div class="text-muted">Total</div>
                                <div class="fw-semibold">${{ number_format($order->total, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Pagado</div>
                                <div class="fw-semibold text-success">${{ number_format($order->paid_amount, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Saldo</div>
                                <div class="fw-bold text-danger fs-6">${{ number_format($order->balance, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_amount">
                                Monto <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" id="wo_amount" name="amount"
                                       step="0.01" min="0.01" max="{{ $order->balance }}"
                                       class="form-control"
                                       value="{{ number_format($order->balance, 2, '.', '') }}"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_date">
                                Fecha <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="wo_date" name="payment_date"
                                   class="form-control"
                                   value="{{ now()->format('Y-m-d') }}"
                                   required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_method">Método de pago</label>
                            <select name="method" id="wo_method" class="form-select">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_reference">Referencia</label>
                            <input type="text" name="reference" id="wo_reference"
                                   class="form-control"
                                   placeholder="N° transacción, etc.">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="wo_notes">Notas</label>
                        <textarea name="notes" id="wo_notes" class="form-control" rows="2"
                                  placeholder="Observaciones del cobro..."></textarea>
                    </div>

                    <div class="alert alert-info border-0 py-2 mb-0" style="font-size:.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Ingresa a tu caja abierta.
                    </div>

                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Registrar pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
@media print {
    .no-print, .no-print * { display: none !important; }
    .app-sidebar, .app-topbar { display: none !important; }
    .app-main { padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    .print-header { display: block !important; margin-bottom: 1rem; }
    .no-print-shadow { box-shadow: none !important; }
    .sticky-top { position: static !important; }
}
</style>
@endpush

@push('scripts')
<script>
function onSvcChange() {
    const sel = document.getElementById('svcSelect');
    const opt = sel.options[sel.selectedIndex];
    const price = opt.dataset.price || '';
    const name  = opt.dataset.name  || '';
    const priceInput = document.getElementById('svcPrice');
    const descInput  = document.getElementById('svcDesc');
    if (price && !priceInput.value) priceInput.value = parseFloat(price).toFixed(2);
    if (name  && !descInput.value)  descInput.value  = name;
}

function onPartChange() {
    const sel = document.getElementById('partSelect');
    const opt = sel.options[sel.selectedIndex];
    const price = opt.dataset.price || '';
    const priceInput = document.getElementById('partPrice');
    if (price) priceInput.value = parseFloat(price).toFixed(2);
}
</script>
@endpush

@endsection
