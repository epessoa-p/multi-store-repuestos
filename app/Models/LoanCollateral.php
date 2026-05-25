<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanCollateral extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'loan_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'inventory_movement_id',
        'status',
        'retained_at',
        'expected_release_date',
        'sellable_at',
        'released_at',
        'notes',
    ];

    protected $casts = [
        'retained_at' => 'datetime',
        'expected_release_date' => 'date',
        'sellable_at' => 'datetime',
        'released_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class);
    }
}
