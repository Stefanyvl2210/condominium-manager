<?php

namespace App\Filament\Resources\BillingPeriods\Schemas;

use App\Models\BillingPeriod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Unique;

class BillingPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        $next = self::nextPeriod();

        return $schema
            ->columns(2)
            ->components([
                Select::make('month')
                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $month) => [$month => Carbon::create(2000, $month)->format('F')]))
                    ->default($next->month)
                    ->required(),
                TextInput::make('year')
                    ->integer()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default($next->year)
                    ->required()
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('month', $get('month')))
                    ->validationMessages(['unique' => 'This billing period already exists.']),
            ]);
    }

    /**
     * The month after the latest period, or the current month when there are none.
     */
    private static function nextPeriod(): Carbon
    {
        $latest = BillingPeriod::query()->orderByDesc('year')->orderByDesc('month')->first();

        return $latest ? $latest->startsOn()->addMonthNoOverflow() : now()->startOfMonth();
    }
}
