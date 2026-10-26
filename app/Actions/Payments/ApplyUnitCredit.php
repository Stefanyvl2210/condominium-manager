<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Applies a unit's unallocated approved payments to its outstanding invoices,
 * oldest invoice first. Anything left over stays as credit for future invoices.
 */
class ApplyUnitCredit
{
    /**
     * @return int USD cents applied to invoices.
     */
    public function handle(Unit $unit): int
    {
        return DB::transaction(function () use ($unit) {
            $payments = Payment::query()
                ->whereBelongsTo($unit)
                ->where('status', PaymentStatus::Approved)
                ->withSum('allocations', 'amount')
                ->orderBy('paid_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->filter(fn (Payment $payment) => $payment->amount_usd > (int) $payment->allocations_sum_amount);

            if ($payments->isEmpty()) {
                return 0;
            }

            $invoices = Invoice::query()
                ->whereBelongsTo($unit)
                ->outstanding()
                ->orderBy('due_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $applied = 0;

            foreach ($payments as $payment) {
                $available = $payment->amount_usd - (int) $payment->allocations_sum_amount;

                foreach ($invoices as $invoice) {
                    if ($available === 0) {
                        break;
                    }

                    $amount = min($available, $invoice->balance);

                    if ($amount === 0) {
                        continue;
                    }

                    $payment->allocations()->create([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                    ]);

                    $invoice->paid_amount += $amount;
                    $invoice->refreshStatus();
                    $invoice->save();

                    $available -= $amount;
                    $applied += $amount;
                }
            }

            return $applied;
        });
    }
}
