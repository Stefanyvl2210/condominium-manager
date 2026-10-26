<?php

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel
{
    case Billing;
    case Expenses;
    case Property;
    case Community;
    case Administration;

    public function getLabel(): string
    {
        return $this->name;
    }
}
