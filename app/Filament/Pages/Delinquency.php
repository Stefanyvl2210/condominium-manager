<?php

namespace App\Filament\Pages;

use App\Filament\NavigationGroup;
use App\Models\Unit;
use App\Reports\DelinquencyReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Accounts receivable aging: what every unit owes, grouped by how late it is.
 */
class Delinquency extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Billing;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Delinquency';

    protected static ?string $title = 'Accounts receivable aging';

    protected ?string $subheading = 'Outstanding balances by days past due. Units without debt are hidden.';

    protected string $view = 'filament.pages.delinquency';

    public function table(Table $table): Table
    {
        $money = fn (TextColumn $column) => $column
            ->money('USD', divideBy: 100)
            ->alignEnd()
            ->placeholder('-')
            ->summarize(Sum::make()->money('USD', divideBy: 100));

        return $table
            ->query(fn (): Builder => DelinquencyReport::query())
            ->defaultSort('total', 'desc')
            ->columns([
                TextColumn::make('label')
                    ->label('Unit')
                    ->weight('medium'),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->description(fn (Unit $record) => $record->owner?->phone)
                    ->placeholder('-'),
                $money(TextColumn::make('current')->label('Not yet due')),
                $money(TextColumn::make('days_1_30')->label('1–30 days')),
                $money(TextColumn::make('days_31_60')->label('31–60 days')),
                $money(TextColumn::make('days_61_90')->label('61–90 days')),
                $money(TextColumn::make('days_over_90')->label('Over 90 days'))->color(fn ($state) => $state > 0 ? 'danger' : null),
                $money(TextColumn::make('total')->label('Total owed')->weight('bold')->sortable()),
            ])
            ->filters([
                SelectFilter::make('building')->relationship('building', 'name'),
            ])
            ->paginated([25, 50, 100]);
    }
}
