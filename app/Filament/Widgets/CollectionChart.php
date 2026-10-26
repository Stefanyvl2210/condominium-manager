<?php

namespace App\Filament\Widgets;

use App\Enums\BillingPeriodStatus;
use App\Models\BillingPeriod;
use Filament\Widgets\ChartWidget;

class CollectionChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Billed vs. collected';

    protected ?string $description = 'Last six closed billing periods, in US dollars.';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $periods = BillingPeriod::query()
            ->where('status', BillingPeriodStatus::Closed)
            ->withSum('invoices', 'total')
            ->withSum('invoices', 'paid_amount')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Billed',
                    'data' => $periods->map(fn (BillingPeriod $period) => round($period->invoices_sum_total / 100, 2))->all(),
                    'backgroundColor' => 'rgba(99, 102, 241, 0.35)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Collected',
                    'data' => $periods->map(fn (BillingPeriod $period) => round($period->invoices_sum_paid_amount / 100, 2))->all(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $periods->map(fn (BillingPeriod $period) => $period->startsOn()->format('M Y'))->all(),
        ];
    }
}
