<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Builders\InvoiceBuilder;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A monthly statement for one unit. All amounts are USD cents.
 */
#[Fillable([
    'billing_period_id',
    'unit_id',
    'number',
    'issued_on',
    'due_on',
    'total',
    'paid_amount',
    'status',
    'late_fee_applied_at',
])]
#[UseEloquentBuilder(InvoiceBuilder::class)]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'due_on' => 'date',
            'total' => 'integer',
            'paid_amount' => 'integer',
            'status' => InvoiceStatus::class,
            'late_fee_applied_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<int, never>
     */
    protected function balance(): Attribute
    {
        return Attribute::get($this->computeBalance(...));
    }

    private function computeBalance(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function isOverdue(?\DateTimeInterface $today = null): bool
    {
        return $this->status !== InvoiceStatus::Paid
            && $this->due_on->lt(($today ?? now())->format('Y-m-d'));
    }

    public function refreshStatus(): void
    {
        $this->status = InvoiceStatus::forAmounts($this->total, $this->paid_amount);
    }

    /**
     * @return BelongsTo<BillingPeriod, $this>
     */
    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
