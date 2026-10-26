<?php

namespace App\Models;

use Database\Factories\CondominiumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The application manages a single condominium. Its settings live in one row
 * that is created with sensible defaults the first time it is requested.
 */
#[Table(name: 'condominiums')]
#[Fillable([
    'name',
    'tax_id',
    'address',
    'email',
    'phone',
    'reserve_fund_rate',
    'late_fee_rate',
    'payment_term_days',
    'expense_approval_threshold',
])]
class Condominium extends Model
{
    /** @use HasFactory<CondominiumFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'reserve_fund_rate' => 'decimal:2',
            'late_fee_rate' => 'decimal:2',
            'payment_term_days' => 'integer',
            'expense_approval_threshold' => 'integer',
        ];
    }

    /**
     * Resolved once per request through a scoped container binding.
     */
    public static function current(): self
    {
        return app(self::class);
    }

    public static function resolveCurrent(): self
    {
        return static::query()->firstOrCreate([], [
            'name' => config('app.name'),
        ]);
    }

    protected static function booted(): void
    {
        static::saved(fn () => app()->forgetInstance(self::class));
    }

    public function requiresApproval(int $amount): bool
    {
        return $amount >= $this->expense_approval_threshold;
    }
}
