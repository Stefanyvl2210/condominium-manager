<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Listing is scoped per panel: staff see every payment, owners only their own.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->role->canAccessBackOffice() || $user->ownsUnit($payment->unit);
    }

    /**
     * Owners report their own payments; administrators can record one on an owner's behalf.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->units()->exists();
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * A payment can be withdrawn by whoever reported it while it is still pending.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $payment->isPending() && ($user->isAdmin() || $payment->submitted_by === $user->id);
    }

    public function review(User $user, Payment $payment): bool
    {
        return $user->isAdmin() && $payment->isPending();
    }
}
