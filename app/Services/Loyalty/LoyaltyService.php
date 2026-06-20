<?php

namespace App\Services\Loyalty;

use App\Models\Branch;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Loyalty\LoyaltyPointMovement;
use App\Models\Loyalty\LoyaltyRedemption;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltySetting;
use App\Models\Product;
use App\Models\Sales\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoyaltyService
{
    /** Puntos otorgados por un monto, según las reglas de la empresa (por bloques). */
    public function pointsForAmount(float $amount, LoyaltySetting $s): int
    {
        $earnAmount = (float) $s->earn_amount;
        if ($earnAmount <= 0 || $s->earn_points <= 0 || $amount <= 0) {
            return 0;
        }
        $blocks = $amount / $earnAmount;
        $blocks = match ($s->rounding) {
            'up'      => ceil($blocks),
            'nearest' => round($blocks),
            default   => floor($blocks),
        };
        return (int) ($blocks * $s->earn_points);
    }

    /**
     * Acredita puntos por una venta (idempotente). Solo si el módulo está
     * habilitado, hay cliente real y el total alcanza el mínimo configurado.
     */
    public function award(Sale $sale): void
    {
        if (!$sale->client_id) {
            return;
        }

        $settings = LoyaltySetting::where('company_id', $sale->company_id)->first();
        if (!$settings || !$settings->enabled) {
            return;
        }
        if ((float) $sale->total < (float) $settings->min_purchase) {
            return;
        }

        // Idempotencia: no acreditar dos veces la misma venta
        $already = LoyaltyPointMovement::where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('type', 'earn')
            ->exists();
        if ($already) {
            return;
        }

        $points = $this->pointsForAmount((float) $sale->total, $settings);
        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($sale, $points) {
            LoyaltyPointMovement::create([
                'company_id'  => $sale->company_id,
                'client_id'   => $sale->client_id,
                'type'        => 'earn',
                'points'      => $points,
                'source_type' => Sale::class,
                'source_id'   => $sale->id,
                'description' => 'Compra ' . $sale->code,
                'user_id'     => auth()->id(),
            ]);
            Client::where('id', $sale->client_id)->increment('points_balance', $points);
        });
    }

    /** Revierte los puntos acreditados por una venta (al anularla). */
    public function reverse(Sale $sale): void
    {
        $earned = LoyaltyPointMovement::where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('type', 'earn')
            ->sum('points');

        if ($earned <= 0) {
            return;
        }

        DB::transaction(function () use ($sale, $earned) {
            $client = Client::find($sale->client_id);
            // No dejar el saldo en negativo
            $deduct = min((int) $earned, (int) ($client->points_balance ?? 0));

            LoyaltyPointMovement::create([
                'company_id'  => $sale->company_id,
                'client_id'   => $sale->client_id,
                'type'        => 'adjust',
                'points'      => -$deduct,
                'source_type' => Sale::class,
                'source_id'   => $sale->id,
                'description' => 'Reverso por anulación ' . $sale->code,
                'user_id'     => auth()->id(),
            ]);
            if ($client && $deduct > 0) {
                $client->decrement('points_balance', $deduct);
            }
        });
    }

    /**
     * Canjea una recompensa para un cliente: valida saldo y stock, descuenta
     * puntos, registra el canje y (si la recompensa apunta a un producto)
     * descuenta inventario.
     *
     * @throws ValidationException
     */
    public function redeem(Client $client, LoyaltyReward $reward, ?int $branchId = null, ?Sale $sale = null): LoyaltyRedemption
    {
        if ((int) $client->points_balance < (int) $reward->points_cost) {
            throw ValidationException::withMessages([
                'reward' => "Saldo insuficiente: el cliente tiene {$client->points_balance} y la recompensa cuesta {$reward->points_cost} puntos.",
            ]);
        }
        if ($reward->stock !== null && $reward->stock <= 0) {
            throw ValidationException::withMessages(['reward' => 'La recompensa no tiene stock disponible.']);
        }

        return DB::transaction(function () use ($client, $reward, $branchId, $sale) {
            $redemption = LoyaltyRedemption::create([
                'company_id'   => $client->company_id,
                'client_id'    => $client->id,
                'reward_id'    => $reward->id,
                'points_spent' => $reward->points_cost,
                'sale_id'      => $sale?->id,
                'status'       => 'completed',
                'user_id'      => auth()->id(),
                'redeemed_at'  => now(),
            ]);

            LoyaltyPointMovement::create([
                'company_id'  => $client->company_id,
                'client_id'   => $client->id,
                'type'        => 'redeem',
                'points'      => -1 * (int) $reward->points_cost,
                'source_type' => LoyaltyRedemption::class,
                'source_id'   => $redemption->id,
                'description' => 'Canje: ' . $reward->name,
                'user_id'     => auth()->id(),
            ]);

            $client->decrement('points_balance', (int) $reward->points_cost);

            // Descontar stock de la recompensa (stock propio) o del producto enlazado
            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }
            if ($reward->product_id) {
                $this->dischargeProductStock($reward, $branchId, $redemption);
            }

            return $redemption;
        });
    }

    /** Descuenta inventario del producto enlazado a la recompensa. */
    private function dischargeProductStock(LoyaltyReward $reward, ?int $branchId, LoyaltyRedemption $redemption): void
    {
        $warehouseId = null;
        if ($branchId) {
            $warehouseId = Branch::find($branchId)?->warehouse_id;
        }
        if (!$warehouseId) {
            $warehouseId = Branch::where('company_id', $reward->company_id)
                ->whereNotNull('warehouse_id')->value('warehouse_id');
        }

        if ($warehouseId) {
            InventoryMovement::create([
                'company_id'    => $reward->company_id,
                'warehouse_id'  => $warehouseId,
                'branch_id'     => $branchId,
                'product_id'    => $reward->product_id,
                'user_id'       => auth()->id(),
                'type'          => 'out',
                'quantity'      => 1,
                'unit_cost'     => 0,
                'reference'     => 'CANJE-' . $redemption->id,
                'notes'         => 'Canje de recompensa: ' . $reward->name,
                'movement_date' => now()->toDateString(),
            ]);
        }

        Product::where('id', $reward->product_id)->decrement('current_stock', 1);
    }
}
