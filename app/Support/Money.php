<?php

namespace App\Support;

use App\Enums\Currency;
use InvalidArgumentException;

/**
 * Money is stored as integers in minor units (cents). These helpers keep every
 * calculation in integer arithmetic so rounding is explicit and predictable.
 */
final class Money
{
    public static function format(?int $minorUnits, Currency $currency = Currency::USD): string
    {
        $value = number_format(($minorUnits ?? 0) / 100, 2, '.', ',');

        // Match Filament's money columns: "$1,234.56" and "Bs. 1,234.56".
        return $currency === Currency::USD ? '$'.$value : $currency->symbol().' '.$value;
    }

    /**
     * Parse a user supplied decimal ("1,234.56", "12.5", 12.5) into minor units.
     */
    public static function toMinorUnits(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $normalized = str_replace([',', ' '], '', (string) $amount);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException("[{$amount}] is not a valid amount.");
        }

        return (int) round(((float) $normalized) * 100);
    }

    public static function toDecimal(?int $minorUnits): ?string
    {
        if ($minorUnits === null) {
            return null;
        }

        return number_format($minorUnits / 100, 2, '.', '');
    }

    /**
     * Convert bolívar cents to USD cents using a VES-per-USD rate, rounding half up.
     */
    public static function vesToUsd(int $vesCents, string|float $rate): int
    {
        $rateTenThousandths = self::rateToInteger($rate);

        if ($rateTenThousandths <= 0) {
            throw new InvalidArgumentException('The exchange rate must be greater than zero.');
        }

        return intdiv($vesCents * 10_000 * 2 + $rateTenThousandths, $rateTenThousandths * 2);
    }

    /**
     * Convert USD cents to bolívar cents using a VES-per-USD rate, rounding half up.
     */
    public static function usdToVes(int $usdCents, string|float $rate): int
    {
        return intdiv($usdCents * self::rateToInteger($rate) * 2 + 10_000, 20_000);
    }

    /**
     * Apply a percentage (e.g. "10.00" for 10 %) to an amount, rounding half up.
     */
    public static function percentage(int $minorUnits, string|float $rate): int
    {
        $basisPoints = (int) round(((float) $rate) * 100);

        return intdiv($minorUnits * $basisPoints * 2 + 10_000, 20_000);
    }

    private static function rateToInteger(string|float $rate): int
    {
        return (int) round(((float) $rate) * 10_000);
    }
}
