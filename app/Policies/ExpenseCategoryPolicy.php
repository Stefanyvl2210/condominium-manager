<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategoryPolicy extends BackOfficePolicy
{
    /**
     * @param  ExpenseCategory  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && ! $model->expenses()->exists();
    }
}
