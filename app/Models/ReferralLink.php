<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['affiliate_id', 'product_id', 'code'])]
class ReferralLink extends Model
{
    /**
     * URL yang dibagikan afiliator, mis. https://domain/r/ANDI123
     */
    public function url(): string
    {
        return route('referral.redirect', $this->code);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ReferralClick::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
