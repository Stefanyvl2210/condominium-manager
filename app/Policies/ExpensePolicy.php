<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExpensePolicy extends BackOfficePolicy
{
    /**
     * Billed expenses are part of issued invoices and can no longer change.
     *
     * @param  Expense  $model
     */
    public function update(User $user, Model $model): bool
    {
        return $user->isAdmin() && ! $model->isBilled();
    }

    /**
     * @param  Expense  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && ! $model->isBilled();
    }

    /**
     * Approval is a board responsibility, which keeps the administrator who
     * records an expense from also signing it off.
     */
    public function review(User $user, Expense $expense): bool
    {
        return $user->isBoardMember() && $expense->isPendingApproval();
    }
}
