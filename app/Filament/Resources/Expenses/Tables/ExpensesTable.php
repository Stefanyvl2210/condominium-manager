<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Enums\ExpenseScope;
use App\Enums\ExpenseStatus;
use App\Filament\Resources\Expenses\ExpenseReviewActions;
use App\Models\Expense;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'vendor', 'building', 'unit.building', 'billingPeriod']))
            ->defaultSort('incurred_on', 'desc')
            ->columns([
                TextColumn::make('incurred_on')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->weight('medium')
                    ->description(fn (Expense $record) => $record->vendor?->name)
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn (Expense $record) => strlen($record->description) > 45 ? $record->description : null),
                TextColumn::make('category.name')
                    ->badge()
                    ->color(fn (Expense $record) => $record->category->color),
                TextColumn::make('scope')
                    ->badge()
                    ->formatStateUsing(fn (Expense $record) => match ($record->scope) {
                        ExpenseScope::Condominium => 'Everyone',
                        ExpenseScope::Building => $record->building?->name,
                        ExpenseScope::Unit => 'Unit '.$record->unit?->label,
                    }),
                TextColumn::make('amount')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->alignEnd()
                    ->summarize(Sum::make()->money('USD', divideBy: 100)->label('Total')),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('billingPeriod.name')
                    ->label('Billed in')
                    ->placeholder('Not billed')
                    ->toggleable(),
                IconColumn::make('paid_on')
                    ->label('Vendor paid')
                    ->boolean()
                    ->state(fn (Expense $record) => $record->paid_on !== null)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ExpenseStatus::class),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->preload(),
                SelectFilter::make('vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('scope')
                    ->options(ExpenseScope::class),
                TernaryFilter::make('billing_period_id')
                    ->label('Billed')
                    ->nullable(),
                TernaryFilter::make('paid_on')
                    ->label('Vendor paid')
                    ->nullable(),
                Filter::make('incurred_on')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn ($query, $date) => $query->whereDate('incurred_on', '>=', $date))
                        ->when($data['until'], fn ($query, $date) => $query->whereDate('incurred_on', '<=', $date))),
            ])
            ->recordActions([
                ExpenseReviewActions::approve()->iconButton(),
                ExpenseReviewActions::reject()->iconButton(),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
