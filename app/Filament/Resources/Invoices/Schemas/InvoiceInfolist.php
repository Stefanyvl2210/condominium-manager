<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\PaymentAllocation;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Shared by the back office and the owner portal.
 */
class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(['default' => 2, 'md' => 5])
                    ->schema([
                        TextEntry::make('unit.label')
                            ->label('Unit'),
                        TextEntry::make('unit.owner.name')
                            ->label('Owner')
                            ->placeholder('-'),
                        TextEntry::make('billingPeriod.name')
                            ->label('Period'),
                        TextEntry::make('due_on')
                            ->date()
                            ->color(fn (Invoice $record) => $record->isOverdue() ? 'danger' : null)
                            ->helperText(fn (Invoice $record) => $record->isOverdue() ? 'Overdue' : null),
                        TextEntry::make('status')
                            ->badge(),
                    ]),
                Section::make('Charges')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Description'),
                                TableColumn::make('Type'),
                                TableColumn::make('Expense total'),
                                TableColumn::make('Your share'),
                            ])
                            ->schema([
                                TextEntry::make('description'),
                                TextEntry::make('type')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('expense_total')
                                    ->money('USD', divideBy: 100)
                                    ->placeholder('-'),
                                TextEntry::make('amount')
                                    ->money('USD', divideBy: 100),
                            ]),
                    ]),
                Section::make('Summary')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('total')
                            ->money('USD', divideBy: 100)
                            ->size('lg'),
                        TextEntry::make('paid_amount')
                            ->label('Paid')
                            ->money('USD', divideBy: 100)
                            ->color('success'),
                        TextEntry::make('balance')
                            ->label('Balance due')
                            ->money('USD', divideBy: 100)
                            ->size('lg')
                            ->weight('bold')
                            ->color(fn (int $state) => $state > 0 ? 'danger' : 'success'),
                        TextEntry::make('balance_ves')
                            ->label('Balance in bolívares')
                            ->state(fn (Invoice $record) => self::balanceInBolivares($record))
                            ->helperText('At today\'s official rate.')
                            ->placeholder('-')
                            ->visible(fn (Invoice $record) => $record->balance > 0),
                        RepeatableEntry::make('allocations')
                            ->label('Payments applied')
                            ->placeholder('No payments yet.')
                            ->contained(false)
                            ->schema([
                                TextEntry::make('summary')
                                    ->hiddenLabel()
                                    ->state(fn (PaymentAllocation $record) => sprintf(
                                        '%s on %s (%s)',
                                        Money::format($record->amount),
                                        $record->payment->paid_on->toFormattedDateString(),
                                        $record->payment->method->getLabel(),
                                    )),
                            ]),
                    ]),
            ]);
    }

    private static function balanceInBolivares(Invoice $invoice): ?string
    {
        $rate = ExchangeRate::latest();

        return $rate ? Money::format(Money::usdToVes($invoice->balance, $rate->rate), Currency::VES) : null;
    }
}
