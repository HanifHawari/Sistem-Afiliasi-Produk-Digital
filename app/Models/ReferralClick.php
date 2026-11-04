<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['referral_link_id', 'ip_address', 'user_agent', 'channel', 'clicked_at'])]
class ReferralClick extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    public function referralLink(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class);
    }
}
