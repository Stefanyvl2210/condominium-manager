<?php

namespace App\Filament\Resources\Expenses;

use App\Actions\Expenses\ReviewExpense;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Board members sign off on expenses above the approval threshold.
 */
final class ExpenseReviewActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Approved expenses are billed to owners when the billing period closes.')
            ->authorize('review')
            ->action(function (Expense $record) {
                app(ReviewExpense::class)->approve($record, auth()->user());

                Notification::make()->title('Expense approved')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->schema([
                Textarea::make('reason')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ask for a second quote before going ahead.'),
            ])
            ->authorize('review')
            ->action(function (Expense $record, array $data) {
                app(ReviewExpense::class)->reject($record, auth()->user(), $data['reason']);

                Notification::make()->title('Expense rejected')->success()->send();
            });
    }
}
