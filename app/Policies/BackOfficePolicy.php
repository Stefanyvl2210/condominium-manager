<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Default rules for back-office records: administrators manage them, board
 * members can only look.
 */
abstract class BackOfficePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canAccessBackOffice();
    }

    public function view(User $user, Model $model): bool
    {
        return $user->role->canAccessBackOffice();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Model $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
