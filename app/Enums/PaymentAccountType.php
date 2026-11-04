<?php

namespace App\Enums;

enum PaymentAccountType: string
{
    case Qris = 'qris';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::Bank => 'Rekening Bank',
        };
    }
}
