<?php

namespace App\Http\Controllers\Affiliate;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Commission\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct(
        protected CommissionService $commissions,
    ) {}

    /**
     * Tampilkan halaman penarikan komisi: saldo, info rekening, form, dan riwayat penarikan.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->affiliateProfile;
        $balance = $this->commissions->balanceFor($user);

        $withdrawals = $user->withdrawals()
            ->latest()
            ->paginate(10);

        $isBankComplete = $profile
            && ! empty(trim((string) $profile->bank_name))
            && ! empty(trim((string) $profile->account_number))
            && ! empty(trim((string) $profile->account_holder));

        return view('affiliate.withdrawals.index', compact(
            'user',
            'profile',
            'balance',
            'withdrawals',
            'isBankComplete',
        ));
    }

    /**
     * Ajukan penarikan komisi baru dengan perlindungan transaksi dan pessimistic locking.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ], [
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.integer' => 'Nominal penarikan harus berupa angka bulat positif.',
            'amount.min' => 'Nominal penarikan minimal Rp 1.',
        ]);

        $amount = (int) $validated['amount'];

        $withdrawal = DB::transaction(function () use ($request, $amount) {
            // 1. Ambil ulang record User berdasarkan ID user login dengan lockForUpdate() di dalam transaksi
            $lockedUser = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            // 2. Ambil ulang AffiliateProfile melalui relasi user di dalam transaksi dengan lockForUpdate() bila tersedia
            $profile = $lockedUser->affiliateProfile()->lockForUpdate()->first();

            // 3. Periksa kembali kelengkapan rekening dari record yang baru diambil
            if (! $profile
                || empty(trim((string) $profile->bank_name))
                || empty(trim((string) $profile->account_number))
                || empty(trim((string) $profile->account_holder))) {
                throw ValidationException::withMessages([
                    'amount' => 'Profil rekening bank belum lengkap. Harap lengkapi rekening bank Anda terlebih dahulu.',
                ]);
            }

            // 4. Hitung saldo terkini menggunakan CommissionService::balanceFor($lockedUser)
            $currentBalance = $this->commissions->balanceFor($lockedUser);

            // 5. Pastikan nominal tidak melebihi saldo terkini
            if ($amount > $currentBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal penarikan melebihi saldo yang tersedia (' . rupiah($currentBalance) . ').',
                ]);
            }

            // 6. Buat withdrawal melalui relasi $lockedUser->withdrawals()
            // 7. Simpan snapshot rekening dari profil yang baru dibaca di dalam transaksi
            // 8. Set status WithdrawalStatus::Pending
            return $lockedUser->withdrawals()->create([
                'amount' => $amount,
                'bank_name' => $profile->bank_name,
                'account_number' => $profile->account_number,
                'account_holder' => $profile->account_holder,
                'status' => WithdrawalStatus::Pending,
            ]);
        });

        return redirect()
            ->route('affiliate.withdrawals.index')
            ->with('status', 'Pengajuan penarikan berhasil')
            ->with('status_detail', 'Pengajuan penarikan sebesar ' . rupiah($withdrawal->amount) . ' berhasil diajukan dan sedang menunggu verifikasi admin.');
    }
}
