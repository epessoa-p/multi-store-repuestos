<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'interest_rate',
        'max_amount',
        'max_term_months',
        'description',
        'active',
    ];

    protected $casts = [
        'interest_rate' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
