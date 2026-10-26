<?php

namespace App\Actions\Expenses;

use App\Actions\Expenses\Concerns\HandlesExpenseApproval;
use App\Models\Expense;
use App\Models\User;

/**
 * Registers an expense. Expenses at or above the approval threshold need a
 * board member's sign-off before they can be billed to owners.
 */
class RecordExpense
{
    use HandlesExpenseApproval;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $recordedBy, array $data): Expense
    {
        $expense = Expense::query()->create([
            ...$this->withScopeTargets($data),
            'status' => $this->statusFor((int) $data['amount']),
            'created_by' => $recordedBy->id,
        ]);

        if ($expense->isPendingApproval()) {
            $this->notifyBoard($expense);
        }

        return $expense;
    }
}
