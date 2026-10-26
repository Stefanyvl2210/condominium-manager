<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum UnitType: string implements HasIcon, HasLabel
{
    case Apartment = 'apartment';
    case Commercial = 'commercial';
    case Parking = 'parking';
    case Storage = 'storage';

    public function getLabel(): string
    {
        return match ($this) {
            self::Apartment => 'Apartment',
            self::Commercial => 'Commercial space',
            self::Parking => 'Parking space',
            self::Storage => 'Storage room',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Apartment => Heroicon::OutlinedHome,
            self::Commercial => Heroicon::OutlinedBuildingStorefront,
            self::Parking => Heroicon::OutlinedTruck,
            self::Storage => Heroicon::OutlinedArchiveBox,
        };
    }
}
