<?php

namespace App\Filament\Resources\BillingPeriods\Schemas;

use App\Models\BillingPeriod;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillingPeriodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(['default' => 2, 'md' => 4])
                    ->schema([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('billed')
                            ->state(fn (BillingPeriod $record) => (int) $record->invoices()->sum('total'))
                            ->money('USD', divideBy: 100),
                        TextEntry::make('collected')
                            ->state(fn (BillingPeriod $record) => (int) $record->invoices()->sum('paid_amount'))
                            ->money('USD', divideBy: 100)
                            ->color('success'),
                        TextEntry::make('outstanding')
                            ->state(fn (BillingPeriod $record) => (int) $record->invoices()->sum('total') - (int) $record->invoices()->sum('paid_amount'))
                            ->money('USD', divideBy: 100)
                            ->color('danger'),
                        TextEntry::make('issued_on')
                            ->date()
                            ->placeholder('Not issued'),
                        TextEntry::make('due_on')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('reserve_fund_rate')
                            ->label('Reserve fund')
                            ->suffix(' %')
                            ->placeholder('-'),
                        TextEntry::make('exchange_rate')
                            ->label('Exchange rate at issue')
                            ->prefix('Bs. ')
                            ->suffix(' / USD')
                            ->placeholder('-'),
                        TextEntry::make('closer.name')
                            ->label('Closed by')
                            ->placeholder('-'),
                        TextEntry::make('closed_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
