<?php

namespace App\Filament\Resources\BillingPeriods;

use App\Actions\Billing\CloseBillingPeriod;
use App\Enums\ExpenseStatus;
use App\Exceptions\BillingException;
use App\Models\BillingPeriod;
use App\Models\Condominium;
use App\Models\Expense;
use App\Models\Unit;
use App\Support\Money;
use App\Support\ShareAllocator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Closes a month: bills every approved expense and issues one statement per unit.
 */
final class CloseBillingPeriodAction
{
    public static function make(): Action
    {
        return Action::make('close')
            ->label('Close & bill')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('primary')
            ->authorize('close')
            ->disabled(fn (BillingPeriod $record) => $record->endsOn()->isFuture())
            ->tooltip(fn (BillingPeriod $record) => $record->endsOn()->isFuture()
                ? 'Available after '.$record->endsOn()->toFormattedDateString()
                : null)
            ->modalHeading(fn (BillingPeriod $record) => "Close {$record->name}")
            ->modalDescription(fn (BillingPeriod $record) => self::preview($record))
            ->modalSubmitActionLabel('Issue statements')
            ->schema([
                DatePicker::make('issued_on')
                    ->label('Issue date')
                    ->default(now())
                    ->required()
                    ->minDate(fn (BillingPeriod $record) => $record->endsOn())
                    ->helperText(fn () => 'Statements are due '.Condominium::current()->payment_term_days.' days after this date.'),
            ])
            ->action(function (BillingPeriod $record, array $data, Action $action) {
                try {
                    $invoices = app(CloseBillingPeriod::class)->handle($record, auth()->user(), now()->parse($data['issued_on']));
                } catch (BillingException $exception) {
                    Notification::make()->title('The period could not be closed')->body($exception->getMessage())->danger()->send();

                    $action->halt();

                    return;
                }

                Notification::make()
                    ->title("{$record->name} closed")
                    ->body("{$invoices->count()} statements issued for ".Money::format($invoices->sum('total')).'. Owners were notified by email.')
                    ->success()
                    ->send();
            });
    }

    private static function preview(BillingPeriod $period): HtmlString
    {
        $expenses = Expense::query()->billableUntil($period->endsOn());
        $pending = Expense::query()
            ->where('status', ExpenseStatus::PendingApproval)
            ->whereDate('incurred_on', '<=', $period->endsOn())
            ->count();

        $shares = ShareAllocator::totalInMillionths(Unit::query()->pluck('ownership_share'));
        $reserveRate = Condominium::current()->reserve_fund_rate;

        $lines = [
            sprintf('<strong>%d</strong> approved expenses totalling <strong>%s</strong> will be billed.', $expenses->count(), e(Money::format((int) $expenses->sum('amount')))),
            sprintf('A reserve fund of <strong>%s %%</strong> is added to each unit\'s share of common expenses.', e($reserveRate)),
        ];

        if ($pending > 0) {
            $lines[] = "<span style=\"color: var(--warning-600)\">{$pending} expenses are still waiting for board approval and will roll over to the next period.</span>";
        }

        if (! ShareAllocator::isComplete($shares)) {
            $lines[] = sprintf('<span style="color: var(--danger-600)">Ownership shares add up to %s %%. Fix them before closing.</span>', number_format($shares / 1_000_000, 6));
        }

        $lines[] = 'This cannot be undone.';

        return new HtmlString(implode('<br>', $lines));
    }
}
