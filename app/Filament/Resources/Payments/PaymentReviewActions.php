<?php

namespace App\Filament\Resources\Payments;

use App\Actions\Payments\ReviewPayment;
use App\Models\Payment;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class PaymentReviewActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('review')
            ->requiresConfirmation()
            ->modalDescription(fn (Payment $record) => sprintf(
                'Confirm that %s arrived. %s will be applied to unit %s, oldest statement first.',
                Money::format($record->amount, $record->currency),
                Money::format($record->amount_usd),
                $record->unit->label,
            ))
            ->action(function (Payment $record) {
                app(ReviewPayment::class)->approve($record, auth()->user());

                Notification::make()->title('Payment approved')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('review')
            ->schema([
                Textarea::make('reason')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('The reference does not appear in the bank statement.'),
            ])
            ->modalDescription('The owner will be notified with the reason.')
            ->action(function (Payment $record, array $data) {
                app(ReviewPayment::class)->reject($record, auth()->user(), $data['reason']);

                Notification::make()->title('Payment rejected')->success()->send();
            });
    }
}
