<?php

namespace App\Http\Controllers;

use App\Services\Referral\ReferralTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PIC: Muhammad Fadhil AL Fikry (feature/referral-tracking)
 */
class ReferralController extends Controller
{
    public function redirect(Request $request, string $code, ReferralTracker $tracker): RedirectResponse
    {
        $link = $tracker->eligibleLinks()->where('code', $code)->with('product')->firstOrFail();

        $tracker->recordClick($link, $request);

        return redirect()
            ->route('shop.products.show', $link->product)
            ->withCookie($tracker->makeCookie($link));
    }
}
