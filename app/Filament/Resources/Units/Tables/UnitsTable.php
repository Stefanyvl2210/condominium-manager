<?php

namespace App\Filament\Resources\Units\Tables;

use App\Enums\InvoiceStatus;
use App\Enums\UnitType;
use App\Models\Unit;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        $outstanding = fn (Builder $query) => $query->where('status', '!=', InvoiceStatus::Paid);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['building', 'owner'])
                ->withSum(['invoices as outstanding_total' => $outstanding], 'total')
                ->withSum(['invoices as outstanding_paid' => $outstanding], 'paid_amount'))
            ->defaultSort(fn (Builder $query) => $query->orderBy('building_id')->orderBy('number'))
            ->columns([
                TextColumn::make('label')
                    ->label('Unit')
                    ->weight('medium')
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('number', 'like', "%{$search}%")),
                TextColumn::make('type')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('owner.name')
                    ->placeholder('No owner')
                    ->description(fn (Unit $record) => $record->owner?->email)
                    ->searchable(),
                TextColumn::make('area')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' m²')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ownership_share')
                    ->label('Share')
                    ->numeric(decimalPlaces: 6)
                    ->suffix(' %')
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Owes')
                    ->state(fn (Unit $record): int => (int) $record->outstanding_total - (int) $record->outstanding_paid)
                    ->money('USD', divideBy: 100)
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('building')
                    ->relationship('building', 'name'),
                SelectFilter::make('type')
                    ->options(UnitType::class),
                TernaryFilter::make('owner_id')
                    ->label('Has owner')
                    ->nullable(),
                TernaryFilter::make('in_debt')
                    ->label('In debt')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('invoices', $outstanding),
                        false: fn (Builder $query) => $query->whereDoesntHave('invoices', $outstanding),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
