<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanContractAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_contract_id',
        'file_name',
        'file_path',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(LoanContract::class, 'loan_contract_id');
    }
}
