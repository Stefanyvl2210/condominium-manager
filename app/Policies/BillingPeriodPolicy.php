<?php

namespace App\Policies;

use App\Models\BillingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BillingPeriodPolicy extends BackOfficePolicy
{
    /**
     * Periods are only edited through the close action.
     */
    public function update(User $user, Model $model): bool
    {
        return false;
    }

    /**
     * @param  BillingPeriod  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && $model->isOpen() && ! $model->invoices()->exists();
    }

    public function close(User $user, BillingPeriod $period): bool
    {
        return $user->isAdmin() && $period->isOpen();
    }
}
