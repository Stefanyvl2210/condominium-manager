<?php

namespace App\Filament\Widgets;

use App\Models\ExchangeRate;
use Filament\Widgets\ChartWidget;

/**
 * Shown on the exchange rates page only.
 */
class ExchangeRateChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Bolívares per US dollar';

    protected ?string $maxHeight = '220px';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $rates = ExchangeRate::query()
            ->whereDate('effective_on', '>=', now()->subMonthsNoOverflow(6))
            ->orderBy('effective_on')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Bs. per USD',
                    'data' => $rates->map(fn (ExchangeRate $rate) => (float) $rate->rate)->all(),
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'pointRadius' => 0,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $rates->map(fn (ExchangeRate $rate) => $rate->effective_on->format('M j'))->all(),
        ];
    }
}
