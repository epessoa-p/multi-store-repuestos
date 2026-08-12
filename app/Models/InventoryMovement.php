<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use HasFactory, SoftDeletes;

    const TYPES = [
        'in'         => ['label' => 'Entrada',      'color' => 'success'],
        'out'        => ['label' => 'Salida',        'color' => 'danger'],
        'transfer'   => ['label' => 'Transferencia', 'color' => 'info'],
        'adjustment' => ['label' => 'Ajuste',        'color' => 'warning'],
    ];

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'destination_warehouse_id',
        'branch_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'unit_cost',
        'reference',
        'notes',
        'movement_date',
        'adjustment_reason',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'unit_cost'     => 'decimal:2',
        'movement_date' => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Stock por almacén calculado desde el Kardex, para todos los productos:
     * [warehouse_id => [product_id => qty]].
     *
     * Entradas: 'in'/'adjustment' (warehouse_id) y 'transfer' (destination_warehouse_id).
     * Salidas:  'out'/'transfer' (warehouse_id).
     */
    public static function stockMatrix(?int $companyId): array
    {
        $base = self::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $inDirect = (clone $base)->whereIn('type', ['in', 'adjustment'])
            ->selectRaw('warehouse_id wid, product_id pid, SUM(quantity) q')->groupBy('warehouse_id', 'product_id')->get();
        $inTransfer = (clone $base)->where('type', 'transfer')->whereNotNull('destination_warehouse_id')
            ->selectRaw('destination_warehouse_id wid, product_id pid, SUM(quantity) q')->groupBy('destination_warehouse_id', 'product_id')->get();
        $outs = (clone $base)->whereIn('type', ['out', 'transfer'])
            ->selectRaw('warehouse_id wid, product_id pid, SUM(quantity) q')->groupBy('warehouse_id', 'product_id')->get();

        $map = [];
        $apply = function ($rows, $sign) use (&$map) {
            foreach ($rows as $r) {
                if (!$r->wid) continue;
                $map[$r->wid][$r->pid] = ($map[$r->wid][$r->pid] ?? 0) + $sign * (float) $r->q;
            }
        };
        $apply($inDirect, 1);
        $apply($inTransfer, 1);
        $apply($outs, -1);

        return $map;
    }
}
