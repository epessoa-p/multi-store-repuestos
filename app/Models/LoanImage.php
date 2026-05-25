<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanImage extends Model
{
    protected $fillable = [
        'loan_id',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
