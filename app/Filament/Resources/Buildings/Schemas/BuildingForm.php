<?php

namespace App\Filament\Resources\Buildings\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BuildingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Tower A'),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->helperText('Short prefix used in unit labels, e.g. "A" for A-0302.')
                            ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state)),
                        TextInput::make('floors')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(200),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
