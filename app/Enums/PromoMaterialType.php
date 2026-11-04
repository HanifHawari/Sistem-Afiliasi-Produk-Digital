<?php

namespace App\Enums;

enum PromoMaterialType: string
{
    case Image = 'image';
    case Video = 'video';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Gambar',
            self::Video => 'Video',
            self::Text => 'Teks / Caption',
        };
    }
}
