<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseStatus;
use App\Models\ExpenseCategory;
use Filament\Widgets\ChartWidget;

class ExpensesByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Where the money goes';

    protected ?string $description = 'Approved expenses in the last six months.';

    protected ?string $maxHeight = '280px';

    /**
     * Chart.js colors for the category color names used in the UI.
     */
    private const PALETTE = [
        'primary' => 'rgb(99, 102, 241)',
        'info' => 'rgb(59, 130, 246)',
        'success' => 'rgb(16, 185, 129)',
        'warning' => 'rgb(245, 158, 11)',
        'danger' => 'rgb(239, 68, 68)',
        'gray' => 'rgb(148, 163, 184)',
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $categories = ExpenseCategory::query()
            ->withSum(['expenses' => fn ($query) => $query
                ->where('status', ExpenseStatus::Approved)
                ->whereDate('incurred_on', '>=', now()->subMonthsNoOverflow(6)->startOfMonth())], 'amount')
            ->get()
            ->filter(fn (ExpenseCategory $category) => $category->expenses_sum_amount > 0)
            ->sortByDesc('expenses_sum_amount')
            ->values();

        return [
            'datasets' => [
                [
                    'data' => $categories->map(fn (ExpenseCategory $category) => round($category->expenses_sum_amount / 100, 2))->all(),
                    'backgroundColor' => $categories->map(fn (ExpenseCategory $category) => self::PALETTE[$category->color] ?? self::PALETTE['gray'])->all(),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'right']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
