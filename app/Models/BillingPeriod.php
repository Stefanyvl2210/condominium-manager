<?php

namespace App\Models;

use App\Enums\BillingPeriodStatus;
use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int|string|null $invoices_sum_total
 * @property-read int|string|null $invoices_sum_paid_amount
 */
#[Fillable([
    'year',
    'month',
    'status',
    'issued_on',
    'due_on',
    'reserve_fund_rate',
    'exchange_rate',
    'closed_at',
    'closed_by',
])]
class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'status' => BillingPeriodStatus::class,
            'issued_on' => 'date',
            'due_on' => 'date',
            'reserve_fund_rate' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'closed_at' => 'datetime',
        ];
    }

    public static function for(int $year, int $month): self
    {
        return static::query()->firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['status' => BillingPeriodStatus::Open],
        );
    }

    public function isOpen(): bool
    {
        return $this->status === BillingPeriodStatus::Open;
    }

    public function startsOn(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfDay();
    }

    public function endsOn(): Carbon
    {
        return $this->startsOn()->endOfMonth()->startOfDay();
    }

    /**
     * E.g. "October 2026".
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get($this->formatName(...));
    }

    private function formatName(): string
    {
        return $this->startsOn()->format('F Y');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
