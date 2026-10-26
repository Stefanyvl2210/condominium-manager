<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case BankTransfer = 'bank_transfer';
    case MobilePayment = 'mobile_payment';
    case Zelle = 'zelle';
    case Cash = 'cash';

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer',
            self::MobilePayment => 'Mobile payment',
            self::Zelle => 'Zelle',
            self::Cash => 'Cash',
        };
    }

    public function requiresReference(): bool
    {
        return $this !== self::Cash;
    }
}
