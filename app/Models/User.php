<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * The back office (`admin` panel) is for administrators and board members.
     * The owner portal is for anyone who owns at least one unit, so a board
     * member who is also an owner can use both.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role->canAccessBackOffice(),
            'portal' => $this->role === UserRole::Owner || $this->units()->exists(),
            default => false,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isBoardMember(): bool
    {
        return $this->role === UserRole::BoardMember;
    }

    public function ownsUnit(Unit $unit): bool
    {
        return $unit->owner_id === $this->getKey();
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'owner_id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function submittedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'submitted_by');
    }
}
