<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Qris = 'qris';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::BankTransfer => 'Transfer Bank',
        };
    }
}
