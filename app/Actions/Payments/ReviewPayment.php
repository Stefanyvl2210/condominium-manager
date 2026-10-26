<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Exceptions\BillingException;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReviewed;
use Illuminate\Support\Facades\DB;

class ReviewPayment
{
    public function __construct(private ApplyUnitCredit $applyUnitCredit) {}

    public function approve(Payment $payment, User $reviewer): Payment
    {
        DB::transaction(function () use ($payment, $reviewer) {
            $this->markReviewed($payment, $reviewer, PaymentStatus::Approved);
            $this->applyUnitCredit->handle($payment->unit);
        });

        $this->notifySubmitter($payment);

        return $payment;
    }

    public function reject(Payment $payment, User $reviewer, string $reason): Payment
    {
        DB::transaction(fn () => $this->markReviewed($payment, $reviewer, PaymentStatus::Rejected, $reason));

        $this->notifySubmitter($payment);

        return $payment;
    }

    /**
     * The review is already saved; a failing email is reported instead of undoing it.
     */
    private function notifySubmitter(Payment $payment): void
    {
        rescue(fn () => $payment->submitter?->notify(new PaymentReviewed($payment)));
    }

    private function markReviewed(Payment $payment, User $reviewer, PaymentStatus $status, ?string $reason = null): void
    {
        $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

        if (! $locked->isPending()) {
            throw BillingException::paymentAlreadyReviewed();
        }

        $payment->forceFill([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
    }
}
