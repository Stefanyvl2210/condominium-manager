<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceLineType;
use App\Models\Condominium;
use App\Models\Invoice;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Charges a one-time late fee on every overdue invoice, calculated as a
 * percentage of the balance that was still owed when the fee was applied.
 */
class ApplyLateFees
{
    /**
     * @return int The number of invoices that received a late fee.
     */
    public function handle(?CarbonInterface $today = null): int
    {
        $today ??= now();
        $rate = Condominium::current()->late_fee_rate;

        if ((float) $rate <= 0) {
            return 0;
        }

        $applied = 0;

        Invoice::query()
            ->overdue($today)
            ->whereNull('late_fee_applied_at')
            ->lazyById()
            ->each(function (Invoice $invoice) use ($rate, $today, &$applied) {
                $fee = Money::percentage($invoice->balance, $rate);

                if ($fee === 0) {
                    return;
                }

                DB::transaction(function () use ($invoice, $fee, $rate, $today) {
                    $invoice->lines()->create([
                        'type' => InvoiceLineType::LateFee,
                        'description' => "Late fee ({$rate}% of the overdue balance)",
                        'amount' => $fee,
                    ]);

                    $invoice->total += $fee;
                    $invoice->late_fee_applied_at = Carbon::instance($today);
                    $invoice->refreshStatus();
                    $invoice->save();
                });

                $applied++;
            });

        return $applied;
    }
}
