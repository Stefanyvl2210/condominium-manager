<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\DownloadInvoiceAction;
use App\Models\Builders\InvoiceBuilder;
use App\Models\Invoice;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table, bool $showPeriod = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['unit.building', 'unit.owner', 'billingPeriod']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('billingPeriod.name')
                    ->label('Period')
                    ->visible($showPeriod),
                TextColumn::make('unit.label')
                    ->label('Unit')
                    ->description(fn (Invoice $record) => $record->unit->owner?->name),
                TextColumn::make('total')
                    ->money('USD', divideBy: 100)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->money('USD', divideBy: 100)),
                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('USD', divideBy: 100)
                    ->alignEnd()
                    ->summarize(Sum::make()->money('USD', divideBy: 100)),
                TextColumn::make('balance')
                    ->money('USD', divideBy: 100)
                    ->alignEnd()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('due_on')
                    ->date()
                    ->sortable()
                    ->color(fn (Invoice $record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn (Invoice $record) => $record->isOverdue() ? Heroicon::OutlinedExclamationTriangle : null)
                    ->tooltip(fn (Invoice $record) => $record->isOverdue() ? 'Overdue' : null),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),
                SelectFilter::make('billingPeriod')
                    ->label('Period')
                    ->relationship('billingPeriod', 'id', fn (Builder $query) => $query->orderByDesc('year')->orderByDesc('month'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->visible($showPeriod),
                SelectFilter::make('building')
                    ->relationship('unit.building', 'name'),
                Filter::make('overdue')
                    ->query(fn (InvoiceBuilder $query) => $query->overdue())
                    ->toggle(),
            ])
            ->recordActions([
                DownloadInvoiceAction::make()->iconButton(),
                ViewAction::make(),
            ]);
    }
}
