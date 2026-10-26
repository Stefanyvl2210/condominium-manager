<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExchangeRateSource: string implements HasLabel
{
    case Manual = 'manual';
    case CentralBank = 'central_bank';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual entry',
            self::CentralBank => 'Central bank (BCV)',
        };
    }
}
