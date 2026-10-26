<?php

namespace App\Actions\Expenses;

use App\Actions\Expenses\Concerns\HandlesExpenseApproval;
use App\Exceptions\BillingException;
use App\Models\Expense;

/**
 * Edits an expense that has not been billed yet. Changing what the board
 * reviews (amount, who pays, vendor or description) discards any previous
 * review and applies the approval threshold again, so an expense can never
 * grow past the threshold, or change after sign-off, without a new approval.
 */
class UpdateExpense
{
    use HandlesExpenseApproval;

    private const REVIEWED_ATTRIBUTES = ['amount', 'scope', 'building_id', 'unit_id', 'vendor_id', 'description'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Expense $expense, array $data): Expense
    {
        if ($expense->isBilled()) {
            throw BillingException::expenseAlreadyBilled();
        }

        $expense->fill($this->withScopeTargets($data));

        $needsNewReview = $expense->isDirty(self::REVIEWED_ATTRIBUTES);

        if ($needsNewReview) {
            $expense->forceFill([
                'status' => $this->statusFor($expense->amount),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);
        }

        $expense->save();

        if ($needsNewReview && $expense->isPendingApproval()) {
            $this->notifyBoard($expense);
        }

        return $expense;
    }
}
