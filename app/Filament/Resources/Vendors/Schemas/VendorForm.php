<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Company')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('tax_id')
                            ->label('Tax ID')
                            ->maxLength(30)
                            ->placeholder('J-12345678-9'),
                        TextInput::make('service')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Elevator maintenance')
                            ->datalist(['Cleaning', 'Security', 'Elevator maintenance', 'Gardening', 'Plumbing', 'Electricity', 'Water']),
                        TextInput::make('address')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Contact')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('contact_name')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(30),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
