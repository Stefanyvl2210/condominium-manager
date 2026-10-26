<?php

namespace App\Models\Builders;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Invoice>
 */
class InvoiceBuilder extends Builder
{
    public function outstanding(): static
    {
        return $this->whereNot('status', InvoiceStatus::Paid);
    }

    public function overdue(?\DateTimeInterface $today = null): static
    {
        return $this->outstanding()->whereDate('due_on', '<', $today ?? now());
    }

    public function forOwner(User $owner): static
    {
        return $this->whereHas('unit', fn (Builder $units) => $units->where('owner_id', $owner->getKey()));
    }
}
