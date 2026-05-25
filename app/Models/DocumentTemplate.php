<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'description',
        'content',
        'active',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    const TYPES = [
        'contrato'     => 'Contrato',
        'boleta'       => 'Boleta',
        'recibo'       => 'Recibo',
        'amortizacion' => 'Amortización',
        'liquidacion'  => 'Liquidación',
        'otro'         => 'Otro',
    ];

    const TYPE_BADGES = [
        'contrato'     => 'primary',
        'boleta'       => 'success',
        'recibo'       => 'info',
        'amortizacion' => 'warning',
        'liquidacion'  => 'danger',
        'otro'         => 'secondary',
    ];

    const PLACEHOLDERS = [
        'cliente_nombre'    => 'Nombre del cliente',
        'cliente_cedula'    => 'Cédula del cliente',
        'cliente_telefono'  => 'Teléfono del cliente',
        'cliente_email'     => 'Email del cliente',
        'cliente_direccion' => 'Dirección del cliente',
        'prestamo_id'       => 'ID del préstamo',
        'prestamo_monto'    => 'Monto del préstamo',
        'prestamo_tasa'     => 'Tasa de interés',
        'prestamo_plazo'    => 'Plazo en meses',
        'prestamo_cuota'    => 'Cuota mensual',
        'prestamo_total'    => 'Total a pagar',
        'prestamo_saldo'    => 'Saldo pendiente',
        'empresa_nombre'    => 'Nombre de la empresa',
        'sucursal_nombre'   => 'Nombre de la sucursal',
        'fecha_actual'      => 'Fecha actual',
        'fecha_inicio'      => 'Fecha de inicio del préstamo',
        'fecha_fin'         => 'Fecha de vencimiento',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeBadgeAttribute(): string
    {
        return self::TYPE_BADGES[$this->type] ?? 'secondary';
    }

    /**
     * Replace template placeholders with actual loan / client data.
     */
    public function applyToLoan(Loan $loan): string
    {
        $replacements = [
            '{{cliente_nombre}}'    => $loan->client?->name ?? '',
            '{{cliente_cedula}}'    => $loan->client?->id_number ?? '',
            '{{cliente_telefono}}'  => $loan->client?->phone ?? '',
            '{{cliente_email}}'     => $loan->client?->email ?? '',
            '{{cliente_direccion}}' => $loan->client?->address ?? '',
            '{{prestamo_id}}'       => '#' . $loan->id,
            '{{prestamo_monto}}'    => number_format((float) $loan->amount, 2),
            '{{prestamo_tasa}}'     => $loan->interest_rate . '%',
            '{{prestamo_plazo}}'    => $loan->term_months . ' meses',
            '{{prestamo_cuota}}'    => number_format((float) $loan->monthly_payment, 2),
            '{{prestamo_total}}'    => number_format((float) $loan->total_to_pay, 2),
            '{{prestamo_saldo}}'    => number_format(max(0, (float) $loan->total_to_pay - (float) $loan->total_paid), 2),
            '{{empresa_nombre}}'    => $loan->company?->name ?? '',
            '{{sucursal_nombre}}'   => $loan->branch?->name ?? '',
            '{{fecha_actual}}'      => now()->format('d/m/Y'),
            '{{fecha_inicio}}'      => $loan->start_date?->format('d/m/Y') ?? '',
            '{{fecha_fin}}'         => $loan->end_date?->format('d/m/Y') ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), (string) $this->content);
    }
}
