<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Money reported by an owner. `amount` is in minor units of `currency`;
 * `amount_usd` is the converted value in USD cents used for balances.
 *
 * @property-read int|string|null $allocations_sum_amount
 */
#[Fillable([
    'unit_id',
    'submitted_by',
    'currency',
    'amount',
    'exchange_rate',
    'amount_usd',
    'method',
    'reference',
    'paid_on',
    'proof_path',
    'notes',
    'status',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'amount' => 'integer',
            'exchange_rate' => 'decimal:4',
            'amount_usd' => 'integer',
            'method' => PaymentMethod::class,
            'paid_on' => 'date',
            'status' => PaymentStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopeForOwner(Builder $query, User $owner): void
    {
        $query->whereHas('unit', fn (Builder $units) => $units->where('owner_id', $owner->getKey()));
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
