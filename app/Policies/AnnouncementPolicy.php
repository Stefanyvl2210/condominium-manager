<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AnnouncementPolicy extends BackOfficePolicy
{
    /**
     * Everyone can read the notice board; the portal only lists published announcements.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * @param  Announcement  $model
     */
    public function view(User $user, Model $model): bool
    {
        return $user->role->canAccessBackOffice() || ($model->published_at !== null && $model->published_at->isPast());
    }
}
