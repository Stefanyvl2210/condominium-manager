<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Who pays for an expense when a billing period is closed.
 */
enum ExpenseScope: string implements HasColor, HasDescription, HasLabel
{
    /** Shared by every unit in the condominium, weighted by ownership share. */
    case Condominium = 'condominium';

    /** Shared by the units of a single building, weighted by their relative ownership share. */
    case Building = 'building';

    /** Charged in full to a single unit. */
    case Unit = 'unit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Condominium => 'Whole condominium',
            self::Building => 'Single building',
            self::Unit => 'Single unit',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Condominium => 'Split across all units by ownership share.',
            self::Building => 'Split across the units of one building by their relative share.',
            self::Unit => 'Charged in full to one unit.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Condominium => 'primary',
            self::Building => 'info',
            self::Unit => 'gray',
        };
    }
}
