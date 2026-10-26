<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\UnitType;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property-read int|string|null $outstanding_total
 * @property-read int|string|null $outstanding_paid
 */
#[Fillable(['building_id', 'owner_id', 'number', 'type', 'floor', 'area', 'ownership_share'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'floor' => 'integer',
            'area' => 'decimal:2',
            'ownership_share' => 'decimal:6',
        ];
    }

    /**
     * Human friendly identifier such as "A-0302".
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get($this->formatLabel(...));
    }

    private function formatLabel(): string
    {
        return $this->building->code.'-'.$this->number;
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Amount owed across all unpaid invoices, in USD cents.
     */
    public function outstandingBalance(): int
    {
        return (int) $this->invoices()
            ->where('status', '!=', InvoiceStatus::Paid)
            ->sum(DB::raw('total - paid_amount'));
    }

    /**
     * Approved payment money that has not been applied to any invoice yet, in USD cents.
     */
    public function availableCredit(): int
    {
        $paid = (int) $this->payments()->where('status', PaymentStatus::Approved)->sum('amount_usd');

        $allocated = (int) PaymentAllocation::query()
            ->whereHas('payment', fn (Builder $query) => $query->whereBelongsTo($this))
            ->sum('amount');

        return $paid - $allocated;
    }

    /**
     * @param  Builder<Unit>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('owner_id', $user->getKey());
    }
}
