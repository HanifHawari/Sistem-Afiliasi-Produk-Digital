<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case WaitingVerification = 'waiting_verification';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::WaitingVerification => 'Menunggu Verifikasi',
            self::Paid => 'Berhasil',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Warna badge: success | warning | danger | info | neutral (lihat <x-badge>).
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::WaitingVerification => 'warning',
            self::Paid => 'success',
            self::Rejected => 'danger',
        };
    }
}
