<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Branch;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'product_id',
        'credit_category_id',
        'credit_category_rule_id',
        'client_id',
        'created_by',
        'approved_by',
        'amount',
        'current_capital',
        'interest_rate',
        'term_months',
        'monthly_payment',
        'total_to_pay',
        'total_paid',
        'start_date',
        'end_date',
        'notes',
        'status',
        'penalty_status',
        'penalty_amount',
        'last_interest_generated_at',
        'interest_period',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_capital' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_to_pay' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_interest_generated_at' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creditCategory(): BelongsTo
    {
        return $this->belongsTo(CreditCategory::class);
    }

    public function creditCategoryRule(): BelongsTo
    {
        return $this->belongsTo(CreditCategoryRule::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(LoanContract::class);
    }

    public function collateral(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(LoanImage::class);
    }

    public function interestPayments(): HasMany
    {
        return $this->hasMany(LoanInterestPayment::class);
    }

    public function amortizations(): HasMany
    {
        return $this->hasMany(LoanAmortization::class);
    }

    /**
     * Capital vigente (se reduce con amortizaciones).
     */
    public function getCurrentCapital(): float
    {
        return max(0, (float) ($this->current_capital ?? $this->amount));
    }

    /**
     * Calcula el interés simple del periodo actual sobre el capital vigente.
     * Ejemplo: capital 300, tasa 10% → interés = 30
     */
    public function calculatePeriodInterest(?float $capital = null): float
    {
        $base = $capital ?? $this->getCurrentCapital();
        $rate = (float) $this->interest_rate;
        return round($base * ($rate / 100), 2);
    }

    /**
     * Genera periodos de interés pendientes según la fecha de creación y periodo.
     */
    public function generatePendingInterestPeriods(): void
    {
        $periodMonths = $this->getPeriodInMonths();
        $startDate = $this->start_date ?? $this->created_at->toImmutable();

        // El primer periodo empieza desde la fecha de creación
        $lastGenerated = $this->last_interest_generated_at;
        $existingPeriods = $this->interestPayments()->max('period_number') ?? 0;

        $now = now();
        $nextPeriodNumber = $existingPeriods + 1;

        // Calcular la fecha del siguiente periodo a generar
        if ($existingPeriods === 0) {
            // Primer periodo: se genera inmediatamente al crear el préstamo
            $periodStart = $startDate->copy();
            $periodEnd = $startDate->copy()->addMonths($periodMonths)->subDay();
        } else {
            $periodStart = $startDate->copy()->addMonths($existingPeriods * $periodMonths);
            $periodEnd = $periodStart->copy()->addMonths($periodMonths)->subDay();
        }

        // Generar periodos que ya debieron haberse creado
        while ($periodStart->lte($now)) {
            // Verificar que no exista ya este periodo
            $exists = $this->interestPayments()->where('period_number', $nextPeriodNumber)->exists();
            if (!$exists) {
                $capital = $this->getCurrentCapital();
                $interestAmount = $this->calculatePeriodInterest($capital);

                $this->interestPayments()->create([
                    'period_number' => $nextPeriodNumber,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'base_amount' => $capital,
                    'interest_rate' => $this->interest_rate,
                    'interest_amount' => $interestAmount,
                    'status' => 'pending',
                ]);

                $this->update(['last_interest_generated_at' => $periodStart->toDateString()]);
            }

            $nextPeriodNumber++;
            $periodStart = $periodStart->addMonths($periodMonths);
            $periodEnd = $periodStart->copy()->addMonths($periodMonths)->subDay();
        }
    }

    /**
     * Obtiene los meses por periodo según interest_period.
     */
    public function getPeriodInMonths(): int
    {
        return match ($this->interest_period) {
            'quarterly' => 3,
            'semiannual' => 6,
            'annual' => 12,
            default => 1, // monthly
        };
    }

    /**
     * Calcula el total de intereses no pagados.
     */
    public function getUnpaidInterestTotal(): float
    {
        return (float) $this->interestPayments()
            ->where('status', '!=', 'paid')
            ->selectRaw('SUM(interest_amount - paid_amount) as total')
            ->value('total') ?? 0;
    }

    /**
     * Calcula el monto para liquidar: capital vigente + intereses no pagados + multas.
     */
    public function getLiquidationAmount(): float
    {
        return round($this->getCurrentCapital() + $this->getUnpaidInterestTotal() + (float) $this->penalty_amount, 2);
    }

    /**
     * Verifica estado de mora: si excedió el plazo sin pagar intereses.
     */
    public function checkOverdueStatus(): void
    {
        if (!in_array($this->status, ['active', 'overdue'])) {
            return;
        }

        $category = $this->creditCategory;
        if (!$category) {
            return;
        }

        $termMonths = (int) $this->term_months;
        $startDate = $this->start_date ?? $this->created_at;
        $endDate = $startDate->copy()->addMonths($termMonths);
        $graceDays = (int) ($category->penalty_grace_days ?? 10);

        // Verificar si hay intereses pendientes más allá del plazo
        $pendingInterests = $this->interestPayments()->where('status', 'pending')->count();

        if ($pendingInterests > 0 && now()->gt($endDate)) {
            // Pasó el plazo → overdue
            if ($this->penalty_status === 'normal') {
                $this->update([
                    'penalty_status' => 'overdue',
                    'status' => 'overdue',
                ]);
            }

            // Check grace period
            $graceEnd = $endDate->copy()->addDays($graceDays);
            if (now()->gt($graceEnd) && $this->penalty_status !== 'defaulted') {
                // Aplicar multa
                $penaltyAmount = (float) $category->penalty_fixed_amount;
                if ($penaltyAmount <= 0) {
                    $penaltyAmount = round($this->getCurrentCapital() * ((float) $category->penalty_rate / 100), 2);
                }

                $this->update([
                    'penalty_status' => 'defaulted',
                    'penalty_amount' => $penaltyAmount,
                ]);
            }
        }
    }
    public function calculateMonthlyPayment(): float
    {
        $monthlyRate = ($this->getMonthlyInterestRate() / 100);
        $n = $this->term_months;
        $principal = $this->amount;

        if ($monthlyRate == 0) {
            return $principal / $n;
        }

        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $n)) / (pow(1 + $monthlyRate, $n) - 1);
        return round($payment, 2);
    }

    public function getMonthlyInterestRate(): float
    {
        return match ($this->interest_period) {
            'quarterly' => ((float) $this->interest_rate) / 3,
            'semiannual' => ((float) $this->interest_rate) / 6,
            'annual' => ((float) $this->interest_rate) / 12,
            default => (float) $this->interest_rate,
        };
    }

    /**
     * Calcular total a pagar
     */
    public function calculateTotalToPay(): float
    {
        return $this->calculateMonthlyPayment() * $this->term_months;
    }

    /**
     * Pagos pendientes
     */
    public function getPendingAmount(): float
    {
        $total = $this->calculateTotalToPay();
        return $total - $this->total_paid;
    }

    /**
     * Número de pagos realizados
     */
    public function getPaymentCount(): int
    {
        return $this->payments()->count();
    }

    /**
     * Próximo pago vencido
     */
    public function getDaysOverdue(): int
    {
        if (!$this->start_date || $this->status != 'active') {
            return 0;
        }

        $paymentsMade = $this->getPaymentCount();
        $nextPaymentDate = now()->copy()
            ->setDay(1)
            ->addMonths($paymentsMade);

        if ($nextPaymentDate->isPast()) {
            return $nextPaymentDate->diffInDays(now());
        }

        return 0;
    }

    public function getCapitalPaid(): float
    {
        return (float) $this->payments()->sum('capital');
    }

    public function getOutstandingPrincipal(): float
    {
        return max(0, (float) $this->amount - $this->getCapitalPaid());
    }

    public function calculateMonthlyPaymentForPrincipal(float $principal, int $months): float
    {
        $monthlyRate = ($this->getMonthlyInterestRate() / 100);

        if ($months <= 0) {
            return 0;
        }

        if ($monthlyRate == 0) {
            return round($principal / $months, 2);
        }

        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);

        return round($payment, 2);
    }
}
