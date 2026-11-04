<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'invoice_number', 'customer_id', 'product_id', 'referral_link_id', 'amount',
    'payment_method', 'payment_account_id', 'payment_proof', 'status',
    'verified_by', 'verified_at', 'rejection_note',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function referralLink(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }
}
