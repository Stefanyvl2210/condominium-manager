<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasDescription, HasLabel
{
    case Admin = 'admin';
    case BoardMember = 'board_member';
    case Owner = 'owner';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::BoardMember => 'Board member',
            self::Owner => 'Owner',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::BoardMember => 'warning',
            self::Owner => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Admin => 'Manages the condominium: units, expenses, billing and payments.',
            self::BoardMember => 'Read-only access to the back office, can approve or reject large expenses.',
            self::Owner => 'Access to the owner portal for their own units.',
        };
    }

    public function canAccessBackOffice(): bool
    {
        return $this !== self::Owner;
    }
}
