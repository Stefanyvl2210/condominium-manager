<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Models\Vendor;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withSum('expenses', 'amount'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->weight('medium')
                    ->description(fn (Vendor $record) => $record->tax_id)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('contact_name')
                    ->label('Contact')
                    ->description(fn (Vendor $record) => $record->phone)
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->copyable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('expenses_sum_amount')
                    ->label('Total spent')
                    ->money('USD', divideBy: 100)
                    ->placeholder('$0.00')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
