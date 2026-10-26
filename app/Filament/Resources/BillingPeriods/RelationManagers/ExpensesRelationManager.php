<?php

namespace App\Filament\Resources\BillingPeriods\RelationManagers;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Billed expenses';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('incurred_on')
            ->recordUrl(fn (Expense $record) => ExpenseResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('incurred_on')->label('Date')->date(),
                TextColumn::make('description')->description(fn (Expense $record) => $record->vendor?->name),
                TextColumn::make('category.name')->badge()->color(fn (Expense $record) => $record->category->color),
                TextColumn::make('scope')->badge(),
                TextColumn::make('amount')
                    ->money('USD', divideBy: 100)
                    ->alignEnd()
                    ->summarize(Sum::make()->money('USD', divideBy: 100)),
            ]);
    }
}
