<?php

namespace App\Models;

use App\Enums\PromoMaterialType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'title', 'type', 'file_path', 'caption'])]
class PromoMaterial extends Model
{
    protected function casts(): array
    {
        return [
            'type' => PromoMaterialType::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
