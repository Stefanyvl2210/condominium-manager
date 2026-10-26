<?php

namespace App\Filament\Resources\ExchangeRates;

use App\Enums\ExchangeRateSource;
use App\Filament\NavigationGroup;
use App\Filament\Resources\ExchangeRates\Pages\ListExchangeRates;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Billing;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'effective_on';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('effective_on')
                    ->label('Effective from')
                    ->required()
                    ->default(now())
                    ->maxDate(now()->addDay())
                    ->unique(ignoreRecord: true),
                TextInput::make('rate')
                    ->label('Bolívares per US dollar')
                    ->required()
                    ->numeric()
                    ->minValue(0.0001)
                    ->step(0.0001)
                    ->prefix('Bs.'),
                Select::make('source')
                    ->options(ExchangeRateSource::class)
                    ->default(ExchangeRateSource::CentralBank)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('effective_on', 'desc')
            ->columns([
                TextColumn::make('effective_on')
                    ->label('Effective from')
                    ->date()
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Bs. per USD')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->color('gray'),
            ])
            ->headerActions([
                CreateAction::make()->label('Register rate'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExchangeRates::route('/'),
        ];
    }
}
