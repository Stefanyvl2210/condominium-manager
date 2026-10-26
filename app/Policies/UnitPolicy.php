<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UnitPolicy extends BackOfficePolicy
{
    /**
     * Listing is scoped per panel: staff see every unit, owners only their own.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * @param  Unit  $model
     */
    public function view(User $user, Model $model): bool
    {
        return $user->role->canAccessBackOffice() || $user->ownsUnit($model);
    }

    /**
     * Units with billing history cannot be removed.
     *
     * @param  Unit  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin()
            && ! $model->invoices()->exists()
            && ! $model->payments()->exists();
    }
}
