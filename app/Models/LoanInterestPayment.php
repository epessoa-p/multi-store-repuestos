<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInterestPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'period_number',
        'period_start',
        'period_end',
        'base_amount',
        'interest_rate',
        'interest_amount',
        'paid_amount',
        'status',
        'paid_at',
        'payment_method',
        'reference',
        'notes',
        'registered_by',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function getRemainingAmount(): float
    {
        return max(0, round((float) $this->interest_amount - (float) $this->paid_amount, 2));
    }
}
