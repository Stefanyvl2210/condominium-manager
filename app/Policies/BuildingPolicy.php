<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BuildingPolicy extends BackOfficePolicy
{
    /**
     * @param  Building  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && ! $model->units()->exists();
    }
}
