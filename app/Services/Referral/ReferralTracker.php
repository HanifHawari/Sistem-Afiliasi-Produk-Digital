<?php

namespace App\Services\Referral;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ReferralLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public const CLICK_WINDOW_MINUTES = 5;

    /** Link yang boleh dipakai untuk kunjungan dan checkout baru. */
    public function eligibleLinks(): Builder
    {
        return ReferralLink::query()
            ->whereHas('affiliate', fn (Builder $affiliate) => $affiliate
                ->where('role', UserRole::Affiliate)->where('is_active', true))
            ->whereHas('product', fn (Builder $product) => $product->active());
    }

    /**
     * Catat satu klik ke tabel referral_clicks.
     */
    public function recordClick(ReferralLink $link, Request $request): void
    {
        $channel = $request->query('ch') ?: $request->query('utm_source');
        $channel = is_string($channel) ? mb_substr(trim($channel), 0, 30) : null;

        DB::transaction(function () use ($link, $request, $channel) {
            // Serialisasi klik pada link yang sama agar refresh bersamaan tidak dihitung dua kali.
            ReferralLink::whereKey($link->id)->lockForUpdate()->firstOrFail();

            $duplicate = $request->ip() !== null && $link->clicks()
                ->where('ip_address', $request->ip())
                ->where('user_agent', $request->userAgent())
                ->where('clicked_at', '>=', now()->subMinutes(self::CLICK_WINDOW_MINUTES))
                ->exists();

            if (! $duplicate) {
                $link->clicks()->create([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'channel' => $channel ?: null,
                    'clicked_at' => now(),
                ]);
            }
        });
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
        $code = $request->input('referral_code');

        if ($code === null || (is_string($code) && trim($code) === '')) {
            $code = $request->cookie(self::COOKIE_NAME);
        }

        if (! is_string($code) || trim($code) === '' || mb_strlen(trim($code)) > 32) {
            return null;
        }

        return $this->eligibleLinks()->where('code', trim($code))
            ->where('product_id', $product->id)
            ->when($request->user(), fn ($query) => $query->where('affiliate_id', '!=', $request->user()->id))
            ->first();
    }
}
