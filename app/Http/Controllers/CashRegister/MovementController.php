<?php

namespace App\Http\Controllers\CashRegister;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\Purchases\Purchase;
use App\Models\Sales\Sale;
use App\Models\Workshop\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MovementController extends Controller
{
    /** Pagina una colección en memoria con su propio nombre de página */
    private function paginateCollection(Collection $items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page  = LengthAwarePaginator::resolveCurrentPage($pageName);
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator($slice, $items->count(), $perPage, $page, [
            'path'     => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return $paginator->withQueryString();
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $branches = Branch::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get();

        // ── Filtros ───────────────────────────────────────────
        $branch = $request->get('branch', 'all');
        $period = $request->get('period', 'daily');
        $q      = trim((string) $request->get('q', ''));
        [$from, $to, $periodLabel] = $this->resolveRange($period, $request);

        // Valores precargados de cada selector según el período activo
        $dateValue  = $request->get('date', now()->toDateString());
        $weekValue  = $from->format('o-\WW');   // ISO año-semana, ej. 2026-W23
        $monthValue = $from->format('Y-m');     // ej. 2026-06

        $branchId = ($branch !== 'all' && $branches->firstWhere('id', (int) $branch)) ? (int) $branch : null;

        // ── Movimientos de caja (ingresos/egresos) ────────────
        $movQuery = CashMovement::with(['cashRegister.branch', 'user'])
            ->when($cid, fn ($qq) => $qq->where('company_id', $cid))
            ->whereBetween('movement_date', [$from, $to])
            ->when($branchId, fn ($qq) => $qq->whereHas('cashRegister', fn ($r) => $r->where('branch_id', $branchId)))
            ->when($q !== '', fn ($qq) => $qq->where('description', 'like', "%{$q}%"))
            ->orderByDesc('movement_date')->orderByDesc('id');

        // KPIs sobre el total filtrado (sin paginar)
        $ventas  = (float) (clone $movQuery)->where('type', 'income')->sum('amount');
        $gastos  = (float) (clone $movQuery)->where('type', 'expense')->sum('amount');
        $balance = $ventas - $gastos;

        // Listas paginadas (página independiente por pestaña)
        $ingresos = (clone $movQuery)->where('type', 'income')->paginate(15, ['*'], 'ip')->withQueryString();
        $egresos  = (clone $movQuery)->where('type', 'expense')->paginate(15, ['*'], 'ep')->withQueryString();

        // ── Por cobrar (ventas crédito + OT crédito) ──────────
        $porCobrar = collect();
        Sale::with('client')
            ->where('sale_type', 'credit')->where('status', 'completed')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->when($cid, fn ($qq) => $qq->where('company_id', $cid))
            ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId))
            ->whereBetween('sale_date', [$from, $to])
            ->get()->each(function ($s) use (&$porCobrar) {
                $porCobrar->push((object) [
                    'concept' => ($s->client?->full_name ?? 'Cliente general') . ' · ' . $s->code,
                    'value'   => (float) $s->balance,
                    'date'    => $s->sale_date,
                    'url'     => route('sales.show', $s),
                    'tag'     => 'Venta',
                ]);
            });
        WorkOrder::with('client')
            ->whereIn('payment_status', ['pendiente', 'parcial'])
            ->where('status', 'entregada')
            ->when($cid, fn ($qq) => $qq->where('company_id', $cid))
            ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId))
            ->whereBetween('delivered_at', [$from, $to])
            ->get()->each(function ($o) use (&$porCobrar) {
                $porCobrar->push((object) [
                    'concept' => ($o->client?->full_name ?? '—') . ' · ' . $o->code,
                    'value'   => (float) $o->balance,
                    'date'    => $o->delivered_at ?? $o->reception_date,
                    'url'     => route('workshop.orders.show', $o),
                    'tag'     => 'Taller',
                ]);
            });
        $porCobrar = $porCobrar->filter(fn ($r) => $r->value > 0.001)->sortByDesc('value')->values();
        $totalCobrar = (float) $porCobrar->sum('value');
        $porCobrar = $this->paginateCollection($porCobrar, 15, 'cp');

        // ── Por pagar (compras pendientes) ────────────────────
        $porPagar = Purchase::with('supplier')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->when($cid, fn ($qq) => $qq->where('company_id', $cid))
            ->whereBetween('purchase_date', [$from, $to])
            ->get()
            ->map(fn ($p) => (object) [
                'concept' => ($p->supplier?->name ?? '—') . ' · ' . $p->code,
                'value'   => (float) ($p->total - $p->paid_amount),
                'date'    => $p->purchase_date,
                'url'     => route('purchases.show', $p),
            ])
            ->filter(fn ($r) => $r->value > 0.001)->sortByDesc('value')->values();
        $totalPagar = (float) $porPagar->sum('value');
        $porPagar = $this->paginateCollection($porPagar, 15, 'pp');

        // ── Cierres de caja ───────────────────────────────────
        $closures = CashRegisterSession::with(['cashRegister.branch', 'closedBy'])
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$from, $to])
            ->when($cid, fn ($qq) => $qq->whereHas('cashRegister', fn ($r) => $r->where('company_id', $cid)))
            ->when($branchId, fn ($qq) => $qq->whereHas('cashRegister', fn ($r) => $r->where('branch_id', $branchId)))
            ->orderByDesc('closed_at')->paginate(15, ['*'], 'clp')->withQueryString();

        return view('cash.movements.index', compact(
            'branches', 'branch', 'period', 'periodLabel', 'q', 'from', 'to',
            'ingresos', 'egresos', 'ventas', 'gastos', 'balance',
            'porCobrar', 'totalCobrar', 'porPagar', 'totalPagar', 'closures',
            'dateValue', 'weekValue', 'monthValue'
        ) + ['date' => $dateValue,
             'fromDate' => $request->get('from'), 'toDate' => $request->get('to')]);
    }

    /** Devuelve [Carbon from, Carbon to, label] según el período */
    private function resolveRange(string $period, Request $request): array
    {
        if ($period === 'weekly') {
            $week = (string) $request->get('week', '');
            if (preg_match('/^(\d{4})-W(\d{1,2})$/', $week, $m)) {
                $start = Carbon::now()->setISODate((int) $m[1], (int) $m[2])->startOfWeek();
            } else {
                $start = now()->startOfWeek();
            }
            $end = $start->copy()->endOfWeek();
            return [$start, $end, 'Semana del ' . $start->format('d/m') . ' al ' . $end->format('d/m/Y')];
        }

        if ($period === 'monthly') {
            $month = (string) $request->get('month', '');
            $base  = preg_match('/^\d{4}-\d{2}$/', $month) ? Carbon::parse($month . '-01') : now();
            return [$base->copy()->startOfMonth(), $base->copy()->endOfMonth(), ucfirst($base->translatedFormat('F Y'))];
        }

        if ($period === 'all') {
            return [Carbon::parse('2000-01-01')->startOfDay(), now()->endOfDay(), 'Todo el historial'];
        }

        if ($period === 'range') {
            return [
                ($request->get('from') ? Carbon::parse($request->get('from')) : now())->startOfDay(),
                ($request->get('to') ? Carbon::parse($request->get('to')) : now())->endOfDay(),
                'Rango personalizado',
            ];
        }

        // daily (default)
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();
        $label = $date->isToday() ? 'Hoy' : $date->format('d/m/Y');
        return [$date->copy()->startOfDay(), $date->copy()->endOfDay(), $label];
    }
}
