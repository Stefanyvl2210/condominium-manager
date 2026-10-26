<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\Currency;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Support\Money;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared by the back office and the owner portal.
 */
class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Payment')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('unit.label')
                            ->label('Unit'),
                        TextEntry::make('submitter.name')
                            ->label('Reported by')
                            ->placeholder('-'),
                        TextEntry::make('amount')
                            ->formatStateUsing(fn (Payment $record) => Money::format($record->amount, $record->currency))
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('amount_usd')
                            ->label('Credited in US dollars')
                            ->money('USD', divideBy: 100)
                            ->helperText(fn (Payment $record) => $record->currency === Currency::VES
                                ? "Converted at Bs. {$record->exchange_rate} per USD, the official rate on the payment date."
                                : null),
                        TextEntry::make('method')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('reference')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('paid_on')
                            ->label('Payment date')
                            ->date(),
                        TextEntry::make('created_at')
                            ->label('Reported')
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        ImageEntry::make('proof_path')
                            ->label('Proof of payment')
                            ->disk('uploads')
                            ->visibility('private')
                            ->imageHeight(320)
                            ->visible(fn (Payment $record) => $record->proof_path && ! Str::endsWith($record->proof_path, '.pdf'))
                            ->columnSpanFull(),
                        TextEntry::make('proof_pdf')
                            ->label('Proof of payment')
                            ->state('Open PDF')
                            ->url(fn (Payment $record) => Storage::disk('uploads')->temporaryUrl($record->proof_path, now()->addMinutes(15)))
                            ->openUrlInNewTab()
                            ->visible(fn (Payment $record) => $record->proof_path && Str::endsWith($record->proof_path, '.pdf')),
                    ]),
                Section::make('Review')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('-'),
                        TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('rejection_reason')
                            ->color('danger')
                            ->visible(fn (Payment $record) => filled($record->rejection_reason)),
                        RepeatableEntry::make('allocations')
                            ->label('Applied to')
                            ->placeholder('Not applied yet.')
                            ->contained(false)
                            ->schema([
                                TextEntry::make('summary')
                                    ->hiddenLabel()
                                    ->state(fn (PaymentAllocation $record) => sprintf(
                                        '%s → %s (%s)',
                                        Money::format($record->amount),
                                        $record->invoice->number,
                                        $record->invoice->billingPeriod->name,
                                    )),
                            ]),
                    ]),
            ]);
    }
}
