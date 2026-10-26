<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceLineType;
use App\Enums\PaymentStatus;
use App\Filament\Pages\Delinquency;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\ExchangeRate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinanceOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $collectedByMonth = collect(range(5, 0))->map(fn (int $monthsAgo) => (int) Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->whereBetween('paid_on', [now()->subMonthsNoOverflow($monthsAgo)->startOfMonth(), now()->subMonthsNoOverflow($monthsAgo)->endOfMonth()])
            ->sum('amount_usd') / 100);

        $outstanding = (int) Invoice::query()->outstanding()->sum(DB::raw('total - paid_amount'));
        $overdueUnits = Invoice::query()->overdue()->distinct()->count('unit_id');

        $pendingPayments = Payment::query()->where('status', PaymentStatus::Pending);
        $pendingExpenses = Expense::query()->where('status', ExpenseStatus::PendingApproval);

        $reserveFund = (int) InvoiceLine::query()->where('type', InvoiceLineType::ReserveFund)->sum('amount');
        $rate = ExchangeRate::latest();

        return [
            Stat::make('Collected this month', Money::format((int) ($collectedByMonth->last() * 100)))
                ->description('Approved payments, last 6 months')
                ->chart($collectedByMonth->all())
                ->color('success')
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Outstanding', Money::format($outstanding))
                ->description("{$overdueUnits} units with overdue statements")
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($overdueUnits > 0 ? 'danger' : 'success')
                ->url(Delinquency::getUrl()),
            Stat::make('Payments to review', $pendingPayments->count())
                ->description(Money::format((int) $pendingPayments->sum('amount_usd')).' reported by owners')
                ->color('warning')
                ->icon(Heroicon::OutlinedClock)
                ->url(PaymentResource::getUrl('index', ['tab' => 'pending'])),
            Stat::make('Expenses awaiting the board', $pendingExpenses->count())
                ->description(Money::format((int) $pendingExpenses->sum('amount')).' to approve')
                ->color('warning')
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->url(ExpenseResource::getUrl('index', ['filters' => ['status' => ['value' => ExpenseStatus::PendingApproval->value]]])),
            Stat::make('Reserve fund billed', Money::format($reserveFund))
                ->description('Accumulated across all statements')
                ->icon(Heroicon::OutlinedBuildingLibrary),
            Stat::make('Exchange rate', $rate ? 'Bs. '.number_format((float) $rate->rate, 2) : 'Not set')
                ->description($rate ? 'Per USD since '.$rate->effective_on->toFormattedDateString() : 'Register today\'s rate')
                ->icon(Heroicon::OutlinedArrowsRightLeft),
        ];
    }
}
