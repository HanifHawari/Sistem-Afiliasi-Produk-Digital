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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
