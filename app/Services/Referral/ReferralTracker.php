<?php

namespace App\Services\Referral;

use App\Models\Product;
use App\Models\ReferralLink;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * PIC: Muhammad Fadhil AL Fikry (feature/referral-tracking)
 *
 * Dipakai oleh:
 * - ReferralController  -> recordClick(), makeCookie()
 * - Checkout (Arif)     -> resolveForCheckout()
 */
class ReferralTracker
{
    public const COOKIE_NAME = 'ref';

    public const COOKIE_DAYS = 30;

    /**
     * Catat satu klik ke tabel referral_clicks.
     */
    public function recordClick(ReferralLink $link, Request $request): void
    {
        // TODO (Fadhil): simpan ip_address, user_agent, channel (?ch=tiktok / utm_source),
        //               dan cegah klik dobel dari pengunjung yang sama dalam waktu singkat.
        $link->clicks()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'channel' => $request->query('ch'),
            'clicked_at' => now(),
        ]);
    }

    /**
     * Cookie berisi kode referral, berlaku COOKIE_DAYS hari.
     */
    public function makeCookie(ReferralLink $link): Cookie
    {
        return cookie(self::COOKIE_NAME, $link->code, self::COOKIE_DAYS * 24 * 60);
    }

    /**
     * Cari referral link yang berlaku untuk order ini.
     * Urutan: kode yang diketik customer di form checkout, lalu cookie.
     * Return null jika tidak ada / tidak valid.
     */
    public function resolveForCheckout(Request $request, Product $product): ?ReferralLink
    {
        // TODO (Fadhil): aturan validasi, mis.
        //  - link harus untuk produk yang sama
        //  - afiliator harus aktif
        //  - afiliator tidak boleh membeli lewat link sendiri (self-referral)
        $code = $request->input('referral_code') ?: $request->cookie(self::COOKIE_NAME);

        if (! $code) {
            return null;
        }

        return ReferralLink::where('code', $code)
            ->where('product_id', $product->id)
            ->first();
    }
}
