<?php

namespace App\Filament\Resources\Buildings\Tables;

use App\Filament\Resources\Units\UnitResource;
use App\Models\Building;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BuildingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('units')->withSum('units', 'ownership_share'))
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('floors')
                    ->numeric()
                    ->placeholder('-'),
                TextColumn::make('units_count')
                    ->label('Units')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('units_sum_ownership_share')
                    ->label('Share of condominium')
                    ->numeric(decimalPlaces: 4)
                    ->suffix(' %'),
            ])
            ->recordActions([
                Action::make('units')
                    ->icon(Heroicon::OutlinedHome)
                    ->color('gray')
                    ->url(fn (Building $record) => UnitResource::getUrl('index', [
                        'filters' => ['building' => ['value' => $record->id]],
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
