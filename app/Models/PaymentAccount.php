<?php

namespace App\Models;

use App\Enums\PaymentAccountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'bank_name', 'account_number', 'account_holder', 'qris_image', 'is_active'])]
class PaymentAccount extends Model
{
    protected function casts(): array
    {
        return [
            'type' => PaymentAccountType::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isQris(): bool
    {
        return $this->type === PaymentAccountType::Qris;
    }

    /**
     * Label singkat, mis. "BCA 8091129900 a.n. PT Afiliasi" atau "QRIS · Afiliasi Produk Digital"
     */
    public function label(): string
    {
        return $this->isQris()
            ? 'QRIS · '.$this->account_holder
            : "{$this->bank_name} {$this->account_number} a.n. {$this->account_holder}";
    }

    /**
     * URL gambar QRIS untuk ditampilkan di checkout.
     */
    public function qrisImageUrl(): ?string
    {
        return $this->qris_image ? asset('storage/'.$this->qris_image) : null;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
