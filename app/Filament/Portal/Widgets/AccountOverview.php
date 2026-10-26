<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\Currency;
use App\Enums\PaymentStatus;
use App\Filament\Portal\Resources\Invoices\InvoiceResource;
use App\Filament\Portal\Resources\Payments\PaymentResource;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AccountOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $owner = auth()->user();
        $outstanding = Invoice::query()->forOwner($owner)->outstanding();
        $balance = (int) (clone $outstanding)->sum(DB::raw('total - paid_amount'));
        $nextDue = (clone $outstanding)->orderBy('due_on')->first();
        $overdue = Invoice::query()->forOwner($owner)->overdue()->count();
        $rate = ExchangeRate::latest();

        $pending = Payment::query()->forOwner($owner)->where('status', PaymentStatus::Pending);
        $lastPayment = Payment::query()->forOwner($owner)->latest('paid_on')->first();

        return [
            Stat::make('Balance due', Money::format($balance))
                ->description($rate && $balance > 0
                    ? Money::format(Money::usdToVes($balance, $rate->rate), Currency::VES).' at today\'s rate'
                    : 'You are up to date')
                ->color($balance > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->url(InvoiceResource::getUrl('index')),
            Stat::make('Next due date', $nextDue?->due_on->toFormattedDateString() ?? 'Nothing due')
                ->description(match (true) {
                    $overdue > 0 => "{$overdue} ".str('statement')->plural($overdue).' overdue',
                    $nextDue !== null => "Statement {$nextDue->number}",
                    default => 'No pending statements',
                })
                ->descriptionIcon($overdue > 0 ? Heroicon::OutlinedExclamationTriangle : null)
                ->color($overdue > 0 ? 'danger' : 'gray')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Payments under review', $pending->count())
                ->description($lastPayment
                    ? 'Last payment '.$lastPayment->paid_on->toFormattedDateString().' · '.$lastPayment->status->getLabel()
                    : 'No payments reported yet')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url(PaymentResource::getUrl('index')),
        ];
    }
}
