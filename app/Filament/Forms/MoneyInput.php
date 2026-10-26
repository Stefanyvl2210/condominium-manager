<?php

namespace App\Filament\Forms;

use App\Enums\Currency;
use App\Support\Money;
use Filament\Forms\Components\TextInput;

/**
 * A decimal input for amounts that are stored as integer minor units (cents).
 */
final class MoneyInput
{
    public static function make(string $name, Currency $currency = Currency::USD): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0.01)
            ->step(0.01)
            ->prefix($currency->symbol())
            ->formatStateUsing(fn (mixed $state): ?string => is_numeric($state) ? Money::toDecimal((int) $state) : null)
            ->dehydrateStateUsing(fn (mixed $state): int => Money::toMinorUnits($state));
    }
}
