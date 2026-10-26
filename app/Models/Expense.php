<?php

namespace App\Models;

use App\Enums\ExpenseScope;
use App\Enums\ExpenseStatus;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'expense_category_id',
    'vendor_id',
    'scope',
    'building_id',
    'unit_id',
    'description',
    'amount',
    'incurred_on',
    'invoice_number',
    'attachment_path',
    'status',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'billing_period_id',
    'paid_on',
    'payment_reference',
    'created_by',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scope' => ExpenseScope::class,
            'status' => ExpenseStatus::class,
            'amount' => 'integer',
            'incurred_on' => 'date',
            'reviewed_at' => 'datetime',
            'paid_on' => 'date',
        ];
    }

    public function isBilled(): bool
    {
        return $this->billing_period_id !== null;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === ExpenseStatus::PendingApproval;
    }

    /**
     * Approved expenses that have not been billed yet and were incurred up to the given date.
     *
     * @param  Builder<Expense>  $query
     */
    public function scopeBillableUntil(Builder $query, \DateTimeInterface $date): void
    {
        $query
            ->where('status', ExpenseStatus::Approved)
            ->whereNull('billing_period_id')
            ->whereDate('incurred_on', '<=', $date);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<BillingPeriod, $this>
     */
    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
