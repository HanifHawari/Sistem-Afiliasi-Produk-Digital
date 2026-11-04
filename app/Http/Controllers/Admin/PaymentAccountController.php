<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentAccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentAccountRequest;
use App\Models\PaymentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * QRIS & rekening tujuan pembayaran customer.
 * Data yang aktif ditampilkan di halaman checkout (bagian Arif).
 */
class PaymentAccountController extends Controller
{
    public function index(): View
    {
        $accounts = PaymentAccount::query()
            ->withCount('orders')
            ->withCount(['orders as paid_count' => fn ($q) => $q->where('status', OrderStatus::Paid)])
            ->withSum(['orders as paid_sum' => fn ($q) => $q->where('status', OrderStatus::Paid)], 'amount')
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        [$qrisAccounts, $bankAccounts] = $accounts->partition->isQris();
        $active = $accounts->where('is_active', true);

        $stats = [
            'active_qris' => $active->filter->isQris()->count(),
            'active_bank' => $active->reject->isQris()->count(),
            'paid_month' => (int) PaymentAccount::query()
                ->join('orders', 'orders.payment_account_id', '=', 'payment_accounts.id')
                ->where('orders.status', OrderStatus::Paid)
                ->where('orders.verified_at', '>=', now()->startOfMonth())
                ->sum('orders.amount'),
        ];

        return view('admin.payment-accounts.index', compact('qrisAccounts', 'bankAccounts', 'active', 'stats'));
    }

    public function store(PaymentAccountRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('qris_image')) {
            $data['qris_image'] = $request->file('qris_image')->store('qris', 'public');
        }

        $account = PaymentAccount::create($data);

        return redirect()->route('admin.payment-accounts.index')
            ->with('status', $account->isQris() ? 'QRIS ditambahkan' : 'Rekening ditambahkan')
            ->with('status_detail', $account->is_active ? 'Sudah tampil di halaman checkout customer.' : 'Masih nonaktif, belum tampil di checkout.');
    }

    public function update(PaymentAccountRequest $request, PaymentAccount $paymentAccount): RedirectResponse
    {
        $data = $this->payload($request);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('qris_image')) {
            Storage::disk('public')->delete((string) $paymentAccount->qris_image);
            $data['qris_image'] = $request->file('qris_image')->store('qris', 'public');
        } elseif ($data['type'] === PaymentAccountType::Bank) {
            // Berubah dari QRIS ke rekening bank: gambar lama tidak dipakai lagi
            Storage::disk('public')->delete((string) $paymentAccount->qris_image);
            $data['qris_image'] = null;
        }

        $paymentAccount->update($data);

        return back()->with('status', 'Metode pembayaran diperbarui');
    }

    public function toggle(PaymentAccount $paymentAccount): RedirectResponse
    {
        $paymentAccount->update(['is_active' => ! $paymentAccount->is_active]);

        return back()
            ->with('status', $paymentAccount->is_active ? 'Metode pembayaran diaktifkan' : 'Metode pembayaran dinonaktifkan')
            ->with('status_detail', $paymentAccount->label());
    }

    public function destroy(PaymentAccount $paymentAccount): RedirectResponse
    {
        if ($paymentAccount->orders()->exists()) {
            return back()->with('error', 'Sudah dipakai di transaksi. Nonaktifkan saja, jangan dihapus.');
        }

        Storage::disk('public')->delete((string) $paymentAccount->qris_image);
        $paymentAccount->delete();

        return back()->with('status', 'Metode pembayaran dihapus');
    }

    /**
     * Ambil field sesuai jenis: QRIS tidak menyimpan nama bank & nomor rekening.
     *
     * @return array<string, mixed>
     */
    private function payload(PaymentAccountRequest $request): array
    {
        $type = PaymentAccountType::from($request->validated('type'));

        return [
            'type' => $type,
            'account_holder' => $request->validated('account_holder'),
            'bank_name' => $type === PaymentAccountType::Bank ? strtoupper(trim($request->validated('bank_name'))) : null,
            'account_number' => $type === PaymentAccountType::Bank ? preg_replace('/\D/', '', $request->validated('account_number')) : null,
        ];
    }
}
