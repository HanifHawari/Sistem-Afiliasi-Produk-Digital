<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    /**
     * Tampilkan form edit rekening bank milik afiliator yang sedang login.
     * Afiliator hanya dapat melihat rekeningnya sendiri.
     */
    public function edit(): View
    {
        $user = auth()->user();
        $profile = $user->affiliateProfile;

        return view('affiliate.bank-account.edit', compact('profile'));
    }

    /**
     * Simpan (create atau update) rekening bank afiliator yang sedang login.
     * updateOrCreate menggunakan user_id sebagai kunci sehingga afiliator
     * tidak dapat mengubah rekening milik pengguna lain.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            // Nomor rekening: wajib berupa string digit agar angka nol di awal tetap tersimpan.
            // Panjang maks 50 sesuai struktur kolom account_number di tabel affiliate_profiles.
            'account_number' => ['required', 'string', 'digits_between:1,50'],
            'account_holder' => ['required', 'string', 'max:255'],
        ]);

        AffiliateProfile::updateOrCreate(
            ['user_id' => $user->id],
            $validated,
        );

        return back()
            ->with('status', 'Rekening berhasil disimpan')
            ->with('status_detail', "Rekening {$validated['bank_name']} a.n. {$validated['account_holder']} telah diperbarui.");
    }
}
