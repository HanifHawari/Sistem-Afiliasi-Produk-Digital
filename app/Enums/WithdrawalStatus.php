<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Diajukan',
            self::Approved => 'Disetujui',
            self::Paid => 'Sudah Ditransfer',
            self::Rejected => 'Ditolak',
        };
    }
}
