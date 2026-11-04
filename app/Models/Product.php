<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'category', 'description', 'price', 'commission_rate', 'thumbnail', 'file_path', 'is_active'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Nominal komisi afiliator per penjualan (Rupiah).
     */
    public function commissionAmount(): int
    {
        return (int) round($this->price * $this->commission_rate / 100);
    }

    /**
     * Bagian product owner per penjualan (Rupiah).
     */
    public function ownerAmount(): int
    {
        return $this->price - $this->commissionAmount();
    }

    public function promoMaterials(): HasMany
    {
        return $this->hasMany(PromoMaterial::class);
    }

    public function referralLinks(): HasMany
    {
        return $this->hasMany(ReferralLink::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
