<?php

namespace App\Reports;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the aging buckets in SQL so the table can sort, filter and paginate.
 * Bucket boundaries are computed in PHP to stay portable across MySQL and SQLite.
 */
final class DelinquencyReport
{
    /**
     * @return Builder<Unit>
     */
    public static function query(?CarbonInterface $today = null): Builder
    {
        $today = ($today ?? now())->copy()->startOfDay();

        $buckets = [
            'current' => ['>=', $today, null],
            'days_1_30' => ['between', $today->copy()->subDays(30), $today->copy()->subDay()],
            'days_31_60' => ['between', $today->copy()->subDays(60), $today->copy()->subDays(31)],
            'days_61_90' => ['between', $today->copy()->subDays(90), $today->copy()->subDays(61)],
            'days_over_90' => ['<', $today->copy()->subDays(90), null],
        ];

        $balances = Invoice::query()
            ->select('unit_id')
            ->selectRaw('SUM(total - paid_amount) as total')
            ->where('status', '!=', InvoiceStatus::Paid->value)
            ->groupBy('unit_id');

        foreach ($buckets as $alias => [$operator, $from, $to]) {
            $condition = match ($operator) {
                'between' => 'due_on BETWEEN ? AND ?',
                default => "due_on {$operator} ?",
            };

            $bindings = $to ? [$from->toDateString(), $to->toDateString()] : [$from->toDateString()];

            $balances->selectRaw("SUM(CASE WHEN {$condition} THEN total - paid_amount ELSE 0 END) as {$alias}", $bindings);
        }

        return Unit::query()
            ->with(['building', 'owner'])
            ->joinSub($balances, 'balances', 'balances.unit_id', '=', 'units.id')
            ->select('units.*', 'balances.total', ...array_map(fn (string $alias) => "balances.{$alias}", array_keys($buckets)))
            ->where('balances.total', '>', 0);
    }
}
