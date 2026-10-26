<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseStatus;
use App\Exceptions\BillingException;
use App\Models\Expense;
use App\Models\User;

class ReviewExpense
{
    public function approve(Expense $expense, User $reviewer): Expense
    {
        return $this->review($expense, $reviewer, ExpenseStatus::Approved);
    }

    public function reject(Expense $expense, User $reviewer, string $reason): Expense
    {
        return $this->review($expense, $reviewer, ExpenseStatus::Rejected, $reason);
    }

    private function review(Expense $expense, User $reviewer, ExpenseStatus $status, ?string $reason = null): Expense
    {
        if (! $expense->isPendingApproval()) {
            throw BillingException::expenseAlreadyReviewed();
        }

        $expense->forceFill([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        return $expense;
    }
}
