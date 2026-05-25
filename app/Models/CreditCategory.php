<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'min_amount',
        'max_amount',
        'penalty_grace_days',
        'penalty_rate',
        'penalty_fixed_amount',
        'active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'penalty_grace_days' => 'integer',
        'penalty_rate' => 'decimal:2',
        'penalty_fixed_amount' => 'decimal:2',
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CreditCategoryRule::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
