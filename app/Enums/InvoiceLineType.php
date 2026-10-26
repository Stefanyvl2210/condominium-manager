<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceLineType: string implements HasLabel
{
    case CondominiumExpense = 'condominium_expense';
    case BuildingExpense = 'building_expense';
    case UnitCharge = 'unit_charge';
    case ReserveFund = 'reserve_fund';
    case LateFee = 'late_fee';

    public function getLabel(): string
    {
        return match ($this) {
            self::CondominiumExpense => 'Common',
            self::BuildingExpense => 'Building',
            self::UnitCharge => 'Unit charge',
            self::ReserveFund => 'Reserve fund',
            self::LateFee => 'Late fee',
        };
    }

    public static function forScope(ExpenseScope $scope): self
    {
        return match ($scope) {
            ExpenseScope::Condominium => self::CondominiumExpense,
            ExpenseScope::Building => self::BuildingExpense,
            ExpenseScope::Unit => self::UnitCharge,
        };
    }
}
