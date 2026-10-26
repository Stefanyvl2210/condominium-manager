<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Payments\PaymentReviewActions;
use App\Models\Payment;
use App\Support\Money;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table, bool $forOwner = false): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['unit.building', 'unit.owner']))
            ->defaultSort('paid_on', 'desc')
            ->columns([
                TextColumn::make('paid_on')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('unit.label')
                    ->label('Unit')
                    ->description(fn (Payment $record) => $forOwner ? null : $record->unit->owner?->name),
                TextColumn::make('amount')
                    ->formatStateUsing(fn (Payment $record) => Money::format($record->amount, $record->currency))
                    ->description(fn (Payment $record) => $record->currency === Currency::VES
                        ? '≈ '.Money::format($record->amount_usd).' at '.$record->exchange_rate
                        : null)
                    ->alignEnd(),
                TextColumn::make('method')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('reference')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Reported')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('method')->options(PaymentMethod::class),
                SelectFilter::make('currency')->options(Currency::class),
                SelectFilter::make('building')
                    ->relationship('unit.building', 'name')
                    ->visible(! $forOwner),
            ])
            ->recordActions([
                PaymentReviewActions::approve()->iconButton(),
                PaymentReviewActions::reject()->iconButton(),
                ActionGroup::make([
                    ViewAction::make(),
                    DeleteAction::make()->label('Withdraw'),
                ]),
            ]);
    }
}
