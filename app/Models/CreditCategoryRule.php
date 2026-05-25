<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditCategoryRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'credit_category_id',
        'name',
        'interest_rate',
        'interest_period',
        'term_months_limit',
        'min_amount',
        'max_amount',
        'active',
    ];

    protected $casts = [
        'interest_rate' => 'decimal:2',
        'term_months_limit' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CreditCategory::class, 'credit_category_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'credit_category_rule_id');
    }
}
