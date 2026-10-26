<?php

namespace App\Filament\Resources\BillingPeriods\Tables;

use App\Enums\BillingPeriodStatus;
use App\Filament\Resources\BillingPeriods\CloseBillingPeriodAction;
use App\Models\BillingPeriod;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillingPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount('invoices')
                ->withSum('invoices', 'total')
                ->withSum('invoices', 'paid_amount'))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('year')->orderByDesc('month'))
            ->columns([
                TextColumn::make('name')
                    ->label('Period')
                    ->weight('medium'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('invoices_count')
                    ->label('Statements')
                    ->numeric(),
                TextColumn::make('invoices_sum_total')
                    ->label('Billed')
                    ->money('USD', divideBy: 100)
                    ->placeholder('-'),
                TextColumn::make('invoices_sum_paid_amount')
                    ->label('Collected')
                    ->money('USD', divideBy: 100)
                    ->placeholder('-'),
                TextColumn::make('collection_rate')
                    ->label('Collection')
                    ->state(fn (BillingPeriod $record) => $record->invoices_sum_total > 0
                        ? round($record->invoices_sum_paid_amount / $record->invoices_sum_total * 100, 1)
                        : null)
                    ->suffix(' %')
                    ->placeholder('-')
                    ->color(fn (?float $state) => match (true) {
                        $state === null => 'gray',
                        $state >= 90 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('due_on')
                    ->date()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')->options(BillingPeriodStatus::class),
            ])
            ->recordActions([
                CloseBillingPeriodAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }
}
