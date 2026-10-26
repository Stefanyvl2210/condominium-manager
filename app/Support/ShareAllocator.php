<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Splits an amount of cents across weighted recipients so that the parts always
 * add up to the original amount (largest remainder method).
 *
 * Weights are ownership shares with up to six decimals, e.g. "2.153846".
 */
final class ShareAllocator
{
    private const SCALE = 1_000_000;

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, string|int|float>  $weights
     * @return array<TKey, int>
     */
    public static function allocate(int $amount, array $weights): array
    {
        if ($weights === []) {
            throw new InvalidArgumentException('At least one weight is required.');
        }

        $scaled = array_map(
            static fn (string|int|float $weight): int => (int) round(((float) $weight) * self::SCALE),
            $weights,
        );

        $totalWeight = array_sum($scaled);

        if ($totalWeight <= 0 || min($scaled) < 0) {
            throw new InvalidArgumentException('Weights must be positive.');
        }

        $parts = [];
        $remainders = [];

        foreach ($scaled as $key => $weight) {
            $exact = $amount * $weight;
            $parts[$key] = intdiv($exact, $totalWeight);
            $remainders[$key] = $exact % $totalWeight;
        }

        $leftover = $amount - array_sum($parts);

        // Hand out the remaining cents to the largest remainders; ties keep input order.
        $order = array_keys($remainders);
        usort($order, static fn ($a, $b): int => $remainders[$b] <=> $remainders[$a]);

        foreach (array_slice($order, 0, $leftover) as $key) {
            $parts[$key]++;
        }

        return $parts;
    }

    /**
     * Sum ownership shares exactly, returning millionths of a percent.
     *
     * @param  iterable<string|int|float>  $shares
     */
    public static function totalInMillionths(iterable $shares): int
    {
        $total = 0;

        foreach ($shares as $share) {
            $total += (int) round(((float) $share) * self::SCALE);
        }

        return $total;
    }

    public static function isComplete(int $totalInMillionths): bool
    {
        return $totalInMillionths === 100 * self::SCALE;
    }
}
