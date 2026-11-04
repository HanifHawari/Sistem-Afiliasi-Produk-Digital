<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Affiliate = 'affiliate';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Affiliate => 'Afiliator',
            self::Customer => 'Customer',
        };
    }
}
