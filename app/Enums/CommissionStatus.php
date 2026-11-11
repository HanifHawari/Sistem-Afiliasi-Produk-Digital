<?php

namespace App\Enums;

enum CommissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Warna badge: success | warning | danger | info | neutral (lihat <x-badge>).
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Cancelled => 'danger',
        };
    }
}
