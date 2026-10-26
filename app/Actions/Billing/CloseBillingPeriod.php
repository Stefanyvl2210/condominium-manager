<?php

namespace App\Actions\Billing;

use App\Actions\Payments\ApplyUnitCredit;
use App\Enums\BillingPeriodStatus;
use App\Enums\ExpenseScope;
use App\Enums\InvoiceLineType;
use App\Enums\InvoiceStatus;
use App\Exceptions\BillingException;
use App\Models\BillingPeriod;
use App\Models\Condominium;
use App\Models\ExchangeRate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\InvoiceIssued;
use App\Support\Money;
use App\Support\ShareAllocator;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns the approved, unbilled expenses of a month into one invoice per unit.
 *
 * - Condominium expenses are split across all units by ownership share.
 * - Building expenses are split across that building's units by their relative share.
 * - Unit expenses are charged in full to that unit.
 * - The reserve fund is a percentage of each unit's share of common expenses.
 */
class CloseBillingPeriod
{
    public function __construct(private ApplyUnitCredit $applyUnitCredit) {}

    /**
     * @return Collection<int, Invoice>
     */
    public function handle(BillingPeriod $period, User $closedBy, ?CarbonInterface $issuedOn = null): Collection
    {
        $issuedOn = ($issuedOn ?? now())->copy()->startOfDay();

        $this->ensureCanBeClosed($period, $issuedOn);

        $units = Unit::query()->with('building')->get();
        $expenses = Expense::query()->with('vendor')->billableUntil($period->endsOn())->orderBy('incurred_on')->get();

        $this->ensureSharesAreComplete($units);

        $condominium = Condominium::current();
        $linesByUnit = $this->distributeExpenses($expenses, $units);

        $invoices = DB::transaction(function () use ($period, $closedBy, $issuedOn, $units, $expenses, $condominium, $linesByUnit) {
            $dueOn = $issuedOn->copy()->addDays($condominium->payment_term_days);
            $invoices = new Collection;
            $sequence = 0;

            $orderedUnits = $units->sortBy(fn (Unit $unit) => [$unit->building->code, $unit->number]);

            foreach ($orderedUnits as $unit) {
                $lines = $linesByUnit[$unit->id] ?? [];

                $commonAmount = collect($lines)
                    ->whereIn('type', [InvoiceLineType::CondominiumExpense, InvoiceLineType::BuildingExpense])
                    ->sum('amount');

                $reserveFund = Money::percentage($commonAmount, $condominium->reserve_fund_rate);

                if ($reserveFund > 0) {
                    $lines[] = [
                        'type' => InvoiceLineType::ReserveFund,
                        'description' => "Reserve fund ({$condominium->reserve_fund_rate}% of common expenses)",
                        'expense_id' => null,
                        'expense_total' => null,
                        'amount' => $reserveFund,
                    ];
                }

                $total = array_sum(array_column($lines, 'amount'));

                if ($total === 0) {
                    continue;
                }

                $invoice = Invoice::query()->create([
                    'billing_period_id' => $period->id,
                    'unit_id' => $unit->id,
                    'number' => sprintf('INV-%04d%02d-%04d', $period->year, $period->month, ++$sequence),
                    'issued_on' => $issuedOn,
                    'due_on' => $dueOn,
                    'total' => $total,
                    'paid_amount' => 0,
                    'status' => InvoiceStatus::Unpaid,
                ]);

                $invoice->lines()->createMany($lines);
                $invoices->push($invoice);
            }

            Expense::query()->whereKey($expenses->modelKeys())->update(['billing_period_id' => $period->id]);

            $period->update([
                'status' => BillingPeriodStatus::Closed,
                'issued_on' => $issuedOn,
                'due_on' => $dueOn,
                'reserve_fund_rate' => $condominium->reserve_fund_rate,
                'exchange_rate' => ExchangeRate::effectiveOn($issuedOn)?->rate,
                'closed_at' => now(),
                'closed_by' => $closedBy->id,
            ]);

            return $invoices;
        });

        foreach ($invoices as $invoice) {
            // Owners who paid in advance get their credit applied right away.
            $this->applyUnitCredit->handle($invoice->unit);
            $invoice->refresh();

            $invoice->unit->owner?->notify(new InvoiceIssued($invoice));
        }

        return $invoices;
    }

    private function ensureCanBeClosed(BillingPeriod $period, CarbonInterface $issuedOn): void
    {
        if (! $period->isOpen()) {
            throw BillingException::periodAlreadyClosed($period->name);
        }

        if ($issuedOn->lt($period->endsOn())) {
            throw BillingException::periodNotFinished($period->name);
        }

        $earlierOpen = BillingPeriod::query()
            ->where('status', BillingPeriodStatus::Open)
            ->where(fn ($query) => $query
                ->where('year', '<', $period->year)
                ->orWhere(fn ($query) => $query->where('year', $period->year)->where('month', '<', $period->month)))
            ->orderBy('year')
            ->orderBy('month')
            ->first();

        if ($earlierOpen) {
            throw BillingException::earlierPeriodOpen($earlierOpen->name);
        }
    }

    /**
     * @param  Collection<int, Unit>  $units
     */
    private function ensureSharesAreComplete(Collection $units): void
    {
        if ($units->isEmpty()) {
            throw BillingException::noUnits();
        }

        $total = ShareAllocator::totalInMillionths($units->pluck('ownership_share'));

        if (! ShareAllocator::isComplete($total)) {
            throw BillingException::sharesIncomplete(number_format($total / 1_000_000, 6));
        }
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @param  Collection<int, Unit>  $units
     * @return array<int, list<array<string, mixed>>>
     */
    private function distributeExpenses(Collection $expenses, Collection $units): array
    {
        $allShares = $units->pluck('ownership_share', 'id')->all();
        $sharesByBuilding = $units->groupBy('building_id')->map(fn ($group) => $group->pluck('ownership_share', 'id')->all());

        $linesByUnit = [];

        foreach ($expenses as $expense) {
            $portions = match ($expense->scope) {
                ExpenseScope::Condominium => ShareAllocator::allocate($expense->amount, $allShares),
                ExpenseScope::Building => ShareAllocator::allocate(
                    $expense->amount,
                    $sharesByBuilding[$expense->building_id] ?? throw BillingException::buildingWithoutUnits($expense->building->name),
                ),
                ExpenseScope::Unit => [$expense->unit_id => $expense->amount],
            };

            $description = $expense->vendor
                ? "{$expense->description} ({$expense->vendor->name})"
                : $expense->description;

            foreach ($portions as $unitId => $amount) {
                if ($amount === 0) {
                    continue;
                }

                $linesByUnit[$unitId][] = [
                    'type' => InvoiceLineType::forScope($expense->scope),
                    'description' => $description,
                    'expense_id' => $expense->id,
                    'expense_total' => $expense->amount,
                    'amount' => $amount,
                ];
            }
        }

        return $linesByUnit;
    }
}
