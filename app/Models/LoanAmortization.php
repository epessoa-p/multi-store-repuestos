<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanAmortization extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'amount',
        'capital_before',
        'capital_after',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'registered_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'capital_before' => 'decimal:2',
        'capital_after' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
