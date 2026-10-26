<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Enums\ExpenseScope;
use App\Models\Expense;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ExpenseInfolist
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
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('amount')
                            ->money('USD', divideBy: 100)
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('incurred_on')
                            ->date(),
                        TextEntry::make('category.name')
                            ->badge()
                            ->color(fn (Expense $record) => $record->category->color),
                        TextEntry::make('vendor.name')
                            ->placeholder('-'),
                        TextEntry::make('scope')
                            ->label('Charged to')
                            ->badge()
                            ->formatStateUsing(fn (Expense $record) => match ($record->scope) {
                                ExpenseScope::Condominium => 'All units, by ownership share',
                                ExpenseScope::Building => "Units of {$record->building?->name}, by relative share",
                                ExpenseScope::Unit => "Unit {$record->unit?->label}",
                            }),
                        TextEntry::make('invoice_number')
                            ->label('Vendor invoice number')
                            ->placeholder('-'),
                        TextEntry::make('attachment_path')
                            ->label('Invoice or receipt')
                            ->formatStateUsing(fn () => 'Open file')
                            ->url(fn (Expense $record) => $record->attachment_path
                                ? Storage::disk('uploads')->temporaryUrl($record->attachment_path, now()->addMinutes(15))
                                : null)
                            ->openUrlInNewTab()
                            ->placeholder('No file'),
                        TextEntry::make('billingPeriod.name')
                            ->label('Billed in')
                            ->placeholder('Not billed yet'),
                    ]),
                Section::make('Approval')
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
                            ->visible(fn (Expense $record) => filled($record->rejection_reason)),
                        TextEntry::make('creator.name')
                            ->label('Recorded by')
                            ->placeholder('-'),
                    ]),
                Section::make('Payment to vendor')
                    ->columnSpan(1)
                    ->columnStart(3)
                    ->schema([
                        TextEntry::make('paid_on')
                            ->date()
                            ->placeholder('Not paid yet'),
                        TextEntry::make('payment_reference')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
