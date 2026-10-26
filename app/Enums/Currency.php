<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Billing is always done in USD. Owners may pay in USD or in bolívares (VES),
 * which are converted with the exchange rate in effect on the payment date.
 */
enum Currency: string implements HasLabel
{
    case USD = 'USD';
    case VES = 'VES';

    public function getLabel(): string
    {
        return match ($this) {
            self::USD => 'US dollar (USD)',
            self::VES => 'Venezuelan bolívar (VES)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::VES => 'Bs.',
        };
    }
}
