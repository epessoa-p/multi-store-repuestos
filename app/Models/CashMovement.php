<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasFactory;

    const CATEGORIES = [
        'sale'                => ['label' => 'Venta',               'type' => 'income'],
        'sale_return'         => ['label' => 'Devolución de venta', 'type' => 'expense'],
        'purchase_supplier'   => ['label' => 'Compra a proveedor',  'type' => 'expense'],
        'expense_operational' => ['label' => 'Gasto operativo',     'type' => 'expense'],
        'expense_supplier'    => ['label' => 'Pago a proveedor',    'type' => 'expense'],
        'advance_customer'    => ['label' => 'Anticipo de cliente', 'type' => 'income'],
        'advance_return'      => ['label' => 'Dev. de anticipo',    'type' => 'expense'],
        'cash_adjustment_in'  => ['label' => 'Ajuste positivo',     'type' => 'income'],
        'cash_adjustment_out' => ['label' => 'Ajuste negativo',     'type' => 'expense'],
    ];

    protected $fillable = [
        'company_id',
        'cash_register_id',
        'cash_register_session_id',
        'user_id',
        'type',
        'category',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'movement_date',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? $this->category;
    }
}
