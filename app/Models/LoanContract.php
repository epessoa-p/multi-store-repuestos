<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id',
        'company_id',
        'content',
        'signature_path',
        'signed_at',
        'signed_by',
        'lender_signature_path',
        'lender_signed_at',
        'lender_signed_by',
        'client_signature_path',
        'client_signed_at',
        'status',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'lender_signed_at' => 'datetime',
        'client_signed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LoanContractAttachment::class);
    }
}
