<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends BackOfficePolicy
{
    /**
     * @param  User  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && $user->isNot($model) && ! $model->units()->exists();
    }
}
