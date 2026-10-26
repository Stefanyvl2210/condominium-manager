<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Enums\ExpenseScope;
use App\Filament\Forms\MoneyInput;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Models\Condominium;
use App\Models\Unit;
use App\Support\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Expense')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('expense_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->preload()
                            ->createOptionForm(fn (Schema $schema) => ExpenseCategoryResource::form($schema)),
                        Select::make('vendor_id')
                            ->relationship('vendor', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),
                        MoneyInput::make('amount')
                            ->required()
                            ->helperText(fn () => 'Expenses of '.Money::format(Condominium::current()->expense_approval_threshold).' or more need board approval.'),
                        DatePicker::make('incurred_on')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                        TextInput::make('invoice_number')
                            ->label('Vendor invoice number')
                            ->maxLength(50),
                        FileUpload::make('attachment_path')
                            ->label('Invoice or receipt')
                            ->disk('uploads')
                            ->directory('expenses')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->openable()
                            ->downloadable(),
                    ]),
                Section::make('Who pays')
                    ->columnSpan(1)
                    ->schema([
                        Radio::make('scope')
                            ->hiddenLabel()
                            ->options(ExpenseScope::class)
                            ->default(ExpenseScope::Condominium)
                            ->required()
                            ->live(),
                        Select::make('building_id')
                            ->relationship('building', 'name')
                            ->preload()
                            ->visible(fn (Get $get) => self::scope($get) === ExpenseScope::Building)
                            ->required(fn (Get $get) => self::scope($get) === ExpenseScope::Building),
                        Select::make('unit_id')
                            ->label('Unit')
                            ->options(fn () => Unit::query()->with('building')->get()->sortBy('label')->pluck('label', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get) => self::scope($get) === ExpenseScope::Unit)
                            ->required(fn (Get $get) => self::scope($get) === ExpenseScope::Unit),
                    ]),
                Section::make('Payment to vendor')
                    ->columnSpan(1)
                    ->columnStart(3)
                    ->collapsible()
                    ->schema([
                        DatePicker::make('paid_on')
                            ->maxDate(now()),
                        TextInput::make('payment_reference')
                            ->maxLength(100),
                    ]),
            ]);
    }

    public static function scope(Get $get): ?ExpenseScope
    {
        $scope = $get('scope');

        return $scope instanceof ExpenseScope ? $scope : ExpenseScope::tryFrom((string) $scope);
    }
}
