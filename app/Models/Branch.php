<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'name',
        'code',
        'phone',
        'email',
        'address',
        'manager_name',
        'color',
        'active',
        'catalog_token',
        'catalog_enabled',
    ];

    public function getColorOrDefaultAttribute(): string
    {
        return $this->color ?: '#6c757d';
    }

    protected $casts = [
        'active'          => 'boolean',
        'catalog_enabled' => 'boolean',
        'deleted_at'      => 'datetime',
    ];

    /** Garantiza que la sucursal tenga un token de catálogo (lo genera si falta). */
    public function ensureCatalogToken(): string
    {
        if (!$this->catalog_token) {
            $this->catalog_token = Str::random(40);
            $this->save();
        }
        return $this->catalog_token;
    }

    /** URL pública del catálogo de esta sucursal. */
    public function catalogUrl(): string
    {
        return route('catalog.public.show', $this->ensureCatalogToken());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }
}