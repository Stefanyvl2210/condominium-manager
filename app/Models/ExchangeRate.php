<?php

namespace App\Models;

use App\Enums\ExchangeRateSource;
use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Official VES per USD rate, effective from `effective_on` until the next entry.
 */
#[Fillable(['effective_on', 'rate', 'source'])]
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'effective_on' => 'date',
            'rate' => 'decimal:4',
            'source' => ExchangeRateSource::class,
        ];
    }

    /**
     * The most recent rate published on or before the given date.
     */
    public static function effectiveOn(\DateTimeInterface $date): ?self
    {
        return static::query()
            ->whereDate('effective_on', '<=', $date)
            ->orderByDesc('effective_on')
            ->first();
    }

    public static function latest(): ?self
    {
        return static::query()->orderByDesc('effective_on')->first();
    }
}
