@extends('layouts.app')
@section('title', 'Estadísticas')
@section('page')
@php
    $tabs = [
        'resumen'    => ['Resumen', 'bi-grid-1x2'],
        'ventas'     => ['Ventas', 'bi-cart'],
        'personal'   => ['Personal', 'bi-people'],
        'clientes'   => ['Clientes', 'bi-person-vcard'],
        'compras'    => ['Compras', 'bi-bag'],
        'inventario' => ['Inventario', 'bi-box-seam'],
        'taller'     => ['Taller', 'bi-tools'],
        'alquileres' => ['Alquileres', 'bi-bicycle'],
    ];
    $chartData = [
        'ventasTrend'      => $stats['ventas']['trend'],
        'ventasCashCredit' => $stats['ventas']['cashCredit'],
        'ventasTop'        => $stats['ventas']['topProducts'],
        'personal'         => $stats['personal']['chart'],
        'clientesNew'      => $stats['clientes']['newTrend'],
        'clientesTop'      => $stats['clientes']['topBuyers'],
        'comprasTrend'     => $stats['compras']['trend'],
        'comprasTop'       => $stats['compras']['topSuppliers'],
        'inventario'       => $stats['inventario']['distribution'],
        'taller'           => $stats['taller']['distribution'],
        'alquileres'       => $stats['alquileres']['fleet'],
    ];
@endphp
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-5"><i class="bi bi-bar-chart-line me-2 text-danger"></i>Estadísticas</h1>
            <p class="text-muted mb-0 small">Análisis por área con recomendaciones automáticas.</p>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap" id="statPeriodForm" data-no-spinner>
            <input type="hidden" name="period" id="statPeriod" value="{{ $period }}">
            <span class="badge bg-dark text-white fw-normal" style="font-size:.72rem;border-radius:999px;">{{ $periodLabel }}</span>
            <div class="d-flex gap-1">
                @foreach(['daily' => 'Día', 'weekly' => 'Semana', 'monthly' => 'Mes'] as $key => $lbl)
                <button type="button" class="stat-pill {{ $period === $key ? 'active' : '' }}" data-period="{{ $key }}">{{ $lbl }}</button>
                @endforeach
            </div>
            <span class="stat-slot {{ $period === 'daily' ? 'is-active' : '' }}" data-slot="daily">
                <input type="date" name="date" value="{{ $dateValue }}" class="form-control form-control-sm" style="width:150px;">
            </span>
            <span class="stat-slot {{ $period === 'weekly' ? 'is-active' : '' }}" data-slot="weekly">
                <input type="week" name="week" value="{{ $weekValue }}" class="form-control form-control-sm" style="width:160px;">
            </span>
            <span class="stat-slot {{ $period === 'monthly' ? 'is-active' : '' }}" data-slot="monthly">
                <input type="month" name="month" value="{{ $monthValue }}" class="form-control form-control-sm" style="width:150px;">
            </span>
        </form>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-pills flex-nowrap mb-3 gap-1" id="statTabs" role="tablist" style="overflow-x:auto;">
        @foreach($tabs as $key => $t)
        <li class="nav-item flex-shrink-0" role="presentation">
            <button class="nav-link {{ $key === 'resumen' ? 'active' : '' }} py-1 px-3" id="t-{{ $key }}" data-bs-toggle="pill"
                    data-bs-target="#p-{{ $key }}" data-tab="{{ $key }}" type="button" role="tab" style="font-size:.82rem;">
                <i class="bi {{ $t[1] }} me-1"></i>{{ $t[0] }}
            </button>
        </li>
        @endforeach
    </ul>

    <div class="tab-content" id="statTabContent">

        {{-- ── RESUMEN ──────────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="p-resumen" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['resumen']['kpis']])
            @include('statistics.partials.insights', ['items' => $stats['resumen']['insights']])
        </div>

        {{-- ── VENTAS ───────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="p-ventas" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['ventas']['kpis']])
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Tendencia de ventas</h6></div><div class="card-body p-3"><canvas data-chart="ventasTrend" height="90"></canvas></div></div>
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-box-seam me-2 text-muted"></i>Top productos del período</h6></div><div class="card-body p-3"><canvas data-chart="ventasTop" height="120"></canvas></div></div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Contado vs Crédito</h6></div><div class="card-body p-3"><canvas data-chart="ventasCashCredit" height="160"></canvas></div></div>
                </div>
                <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['ventas']['insights']])</div>
            </div>
        </div>

        {{-- ── PERSONAL ─────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="p-personal" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['personal']['kpis']])
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-bar-chart me-2 text-muted"></i>Ventas por vendedor</h6></div><div class="card-body p-3"><canvas data-chart="personal" height="130"></canvas></div></div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-trophy me-2 text-muted"></i>Ranking</h6></div>
                        <div class="card-body p-0">
                            <table class="table table-sm align-middle mb-0" style="font-size:.8rem;">
                                <tbody>
                                @forelse($stats['personal']['ranking'] as $i => $r)
                                <tr><td class="ps-3">{{ $i + 1 }}. {{ $r['name'] }}<div class="text-muted" style="font-size:.7rem;">{{ $r['count'] }} ventas · {{ $r['pct'] }}%</div></td><td class="text-end pe-3 fw-semibold">Bs. {{ number_format($r['total'], 2) }}</td></tr>
                                @empty
                                <tr><td class="text-center py-3 text-muted small">Sin datos.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['personal']['insights']])</div>
            </div>
        </div>

        {{-- ── CLIENTES ─────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="p-clientes" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['clientes']['kpis']])
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Clientes nuevos</h6></div><div class="card-body p-3"><canvas data-chart="clientesNew" height="120"></canvas></div></div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-star me-2 text-muted"></i>Top compradores</h6></div><div class="card-body p-3"><canvas data-chart="clientesTop" height="150"></canvas></div></div>
                </div>
                <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['clientes']['insights']])</div>
            </div>
        </div>

        {{-- ── COMPRAS ──────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="p-compras" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['compras']['kpis']])
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Compras (tendencia)</h6></div><div class="card-body p-3"><canvas data-chart="comprasTrend" height="90"></canvas></div></div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-truck me-2 text-muted"></i>Top proveedores</h6></div><div class="card-body p-3"><canvas data-chart="comprasTop" height="160"></canvas></div></div>
                </div>
                <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['compras']['insights']])</div>
            </div>
        </div>

        {{-- ── INVENTARIO ───────────────────────────────────────── --}}
        <div class="tab-pane fade" id="p-inventario" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['inventario']['kpis']])
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Estado del stock</h6></div><div class="card-body p-3"><canvas data-chart="inventario" height="170"></canvas></div></div>
                </div>
                <div class="col-lg-7">@include('statistics.partials.insights', ['items' => $stats['inventario']['insights']])</div>
            </div>
        </div>

        {{-- ── TALLER ───────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="p-taller" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['taller']['kpis']])
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Órdenes por estado</h6></div><div class="card-body p-3"><canvas data-chart="taller" height="170"></canvas></div></div>
                </div>
                <div class="col-lg-7">@include('statistics.partials.insights', ['items' => $stats['taller']['insights']])</div>
            </div>
        </div>

        {{-- ── ALQUILERES ───────────────────────────────────────── --}}
        <div class="tab-pane fade" id="p-alquileres" role="tabpanel">
            @include('statistics.partials.kpis', ['kpis' => $stats['alquileres']['kpis']])
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Ocupación de flota</h6></div><div class="card-body p-3"><canvas data-chart="alquileres" height="170"></canvas></div></div>
                </div>
                <div class="col-lg-7">@include('statistics.partials.insights', ['items' => $stats['alquileres']['insights']])</div>
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
.stat-pill {
    border: 1.5px solid #dee2e6; background: #fff; color: #495057;
    border-radius: 50rem; padding: .18rem .8rem; font-size: .76rem; font-weight: 600;
    cursor: pointer; transition: all .15s ease; white-space: nowrap;
}
.stat-pill:hover { border-color: var(--brand-black,#0a0a0a); color: var(--brand-black,#0a0a0a); }
.stat-pill.active { background: var(--brand-black,#0a0a0a); border-color: var(--brand-black,#0a0a0a); color: #fff; }
.stat-slot { display: none; }
.stat-slot.is-active { display: inline-flex; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const DATA = @json($chartData);
    const built = {};
    const money = (v) => 'Bs. ' + Number(v).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const PALETTE = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#dc3545', '#0dcaf0', '#ffc107', '#20c997'];

    function lineChart(el, d, label) {
        new Chart(el, { type: 'line', data: { labels: d.labels, datasets: [{ label: label, data: d.data, borderColor: '#0a0a0a', backgroundColor: 'rgba(10,10,10,.08)', fill: true, tension: .3, pointRadius: 3 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
    }
    function barChart(el, labels, data, horizontal) {
        new Chart(el, { type: 'bar', data: { labels: labels, datasets: [{ data: data, backgroundColor: '#0a0a0a' }] },
            options: { indexAxis: horizontal ? 'y' : 'x', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { beginAtZero: true } } } });
    }
    function doughnut(el, labels, data) {
        new Chart(el, { type: 'doughnut', data: { labels: labels, datasets: [{ data: data, backgroundColor: PALETTE }] },
            options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } } });
    }

    function buildTab(tab) {
        const pane = document.getElementById('p-' + tab);
        if (!pane) return;
        pane.querySelectorAll('canvas[data-chart]').forEach(function (el) {
            const key = el.dataset.chart;
            if (built[key]) return;
            built[key] = true;
            try {
                if (key === 'ventasTrend')      lineChart(el, DATA.ventasTrend, 'Ventas');
                else if (key === 'comprasTrend')lineChart(el, DATA.comprasTrend, 'Compras');
                else if (key === 'clientesNew') lineChart(el, DATA.clientesNew, 'Nuevos');
                else if (key === 'ventasTop')   barChart(el, DATA.ventasTop.labels, DATA.ventasTop.data, true);
                else if (key === 'comprasTop')  barChart(el, DATA.comprasTop.labels, DATA.comprasTop.data, true);
                else if (key === 'clientesTop') barChart(el, DATA.clientesTop.labels, DATA.clientesTop.data, true);
                else if (key === 'personal')    barChart(el, DATA.personal.labels, DATA.personal.data, true);
                else if (key === 'ventasCashCredit') doughnut(el, ['Contado', 'Crédito'], [DATA.ventasCashCredit.cash, DATA.ventasCashCredit.credit]);
                else if (key === 'inventario')  doughnut(el, ['Con stock', 'Bajo', 'Sin stock'], [DATA.inventario.ok, DATA.inventario.low, DATA.inventario.none]);
                else if (key === 'taller')      doughnut(el, DATA.taller.labels, DATA.taller.data);
                else if (key === 'alquileres')  doughnut(el, ['Disponibles', 'Alquiladas', 'Mantenimiento'], [DATA.alquileres.disp, DATA.alquileres.alq, DATA.alquileres.mant]);
            } catch (e) { console.error(e); }
        });
    }

    // Init charts of a tab when shown (evita ancho 0 en tabs ocultas)
    document.querySelectorAll('#statTabs [data-bs-toggle="pill"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () { buildTab(this.dataset.tab); });
    });

    // ── Selector de período (pills + slot contextual + auto-submit) ──
    const pForm  = document.getElementById('statPeriodForm');
    const pInput = document.getElementById('statPeriod');
    document.querySelectorAll('.stat-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            const p = this.dataset.period;
            pInput.value = p;
            document.querySelectorAll('.stat-pill').forEach(b => b.classList.toggle('active', b === this));
            document.querySelectorAll('.stat-slot').forEach(s => s.classList.toggle('is-active', s.dataset.slot === p));
            pForm.submit();
        });
    });
    pForm.querySelectorAll('input[type=date], input[type=week], input[type=month]').forEach(function (inp) {
        inp.addEventListener('change', function () { pForm.submit(); });
    });
});
</script>
@endpush
@endsection
