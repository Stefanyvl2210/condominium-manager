<?php

namespace App\Filament\Resources\ExpenseCategories;

use App\Filament\NavigationGroup;
use App\Filament\Resources\ExpenseCategories\Pages\ListExpenseCategories;
use App\Models\ExpenseCategory;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Small lookup table, managed from a single page with modals.
 */
class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Expenses;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $recordTitleAttribute = 'name';

    public const COLORS = [
        'primary' => 'Indigo',
        'info' => 'Blue',
        'success' => 'Green',
        'warning' => 'Amber',
        'danger' => 'Red',
        'gray' => 'Gray',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('color')
                    ->options(self::COLORS)
                    ->default('gray')
                    ->required(),
                TextInput::make('description')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('expenses')->withSum('expenses', 'amount'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->badge()
                    ->color(fn (ExpenseCategory $record) => $record->color)
                    ->searchable(),
                TextColumn::make('description')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('expenses_count')
                    ->label('Expenses')
                    ->numeric(),
                TextColumn::make('expenses_sum_amount')
                    ->label('Total')
                    ->money('USD', divideBy: 100)
                    ->placeholder('$0.00'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseCategories::route('/'),
        ];
    }
}
