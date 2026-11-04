<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Admin\Concerns\ServesProofFiles;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\Commission\CommissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pencairan komisi afiliator.
 * Alur: pending (diajukan) -> approved (disetujui) -> paid (sudah ditransfer + bukti)
 *       pending/approved -> rejected (ditolak + alasan)
 */
class WithdrawalController extends Controller
{
    use ServesProofFiles;

    private const RELATIONS = ['affiliate', 'processor'];

    public function index(Request $request, CommissionService $commissions): View
    {
        // Nilai filter yang tidak valid diabaikan (tidak redirect, supaya tidak berputar-putar)
        $status = $request->query('status');
        if (! in_array($status, ['all', ...array_column(WithdrawalStatus::cases(), 'value')], true)) {
            $status = WithdrawalStatus::Pending->value;
        }
        $search = mb_substr(trim(is_string($q = $request->query('q')) ? $q : ''), 0, 100);
        $selectedId = filter_var($request->query('withdrawal'), FILTER_VALIDATE_INT) ?: null;

        $withdrawals = Withdrawal::with(self::RELATIONS)
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('account_holder', 'like', "%{$search}%")
                    ->orWhereHas('affiliate', fn (Builder $a) => $a
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->oldest()
            ->paginate(5)
            ->withQueryString();

        $selected = $selectedId
            ? Withdrawal::with(self::RELATIONS)->find($selectedId)
            : $withdrawals->first();

        // Ringkasan saldo afiliator untuk pengajuan yang sedang diperiksa
        $balance = null;
        if ($selected) {
            $affiliate = $selected->affiliate;
            $balance = [
                'earned' => (int) $affiliate->commissions()->where('status', CommissionStatus::Approved)->sum('amount'),
                'withdrawn' => (int) $affiliate->withdrawals()->where('status', '!=', WithdrawalStatus::Rejected)->sum('amount'),
                'remaining' => $commissions->balanceFor($affiliate),
            ];
        }

        $counts = Withdrawal::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'pending_sum' => (int) Withdrawal::where('status', WithdrawalStatus::Pending)->sum('amount'),
            'pending_count' => $counts[WithdrawalStatus::Pending->value] ?? 0,
            'pending_oldest' => Withdrawal::where('status', WithdrawalStatus::Pending)->oldest()->value('created_at'),
            'approved_sum' => (int) Withdrawal::where('status', WithdrawalStatus::Approved)->sum('amount'),
            'approved_count' => $counts[WithdrawalStatus::Approved->value] ?? 0,
            'paid_month' => (int) Withdrawal::where('status', WithdrawalStatus::Paid)
                ->where('processed_at', '>=', now()->startOfMonth())->sum('amount'),
            'paid_total' => (int) Withdrawal::where('status', WithdrawalStatus::Paid)->sum('amount'),
        ];

        return view('admin.withdrawals.index', compact('withdrawals', 'selected', 'balance', 'counts', 'stats', 'status', 'search'));
    }

    public function approve(Request $request, Withdrawal $withdrawal, CommissionService $commissions): RedirectResponse
    {
        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // Saldo sudah dikurangi semua pengajuan yang tidak ditolak, termasuk yang ini
        if ($commissions->balanceFor($withdrawal->affiliate) < 0) {
            return back()->with('error', 'Saldo komisi afiliator tidak mencukupi. Tolak pengajuan ini.');
        }

        $withdrawal->update([
            'status' => WithdrawalStatus::Approved,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return back()
            ->with('status', 'Pencairan disetujui')
            ->with('status_detail', 'Transfer '.rupiah($withdrawal->amount).' ke rekening afiliator, lalu unggah buktinya.');
    }

    public function pay(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate([
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'transfer_note' => ['nullable', 'string', 'max:500'],
        ], [], ['transfer_proof' => 'bukti transfer', 'transfer_note' => 'catatan']);

        if ($withdrawal->status !== WithdrawalStatus::Approved) {
            return back()->with('error', 'Hanya pengajuan yang sudah disetujui yang bisa ditandai lunas.');
        }

        $withdrawal->update([
            'status' => WithdrawalStatus::Paid,
            'transfer_proof' => $request->file('transfer_proof')->store('withdrawal-proofs', 'local'),
            'note' => $data['transfer_note'] ?? $withdrawal->note,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return back()
            ->with('status', 'Pencairan selesai')
            ->with('status_detail', rupiah($withdrawal->amount).' tercatat sudah ditransfer ke '.$withdrawal->affiliate->name.'.');
    }

    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ], [], ['note' => 'alasan penolakan']);

        if (! in_array($withdrawal->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true)) {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $withdrawal->update([
            'status' => WithdrawalStatus::Rejected,
            'note' => $data['note'],
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return back()
            ->with('status', 'Pencairan ditolak')
            ->with('status_detail', 'Saldo '.rupiah($withdrawal->amount).' kembali ke afiliator.');
    }

    /**
     * Tampilkan bukti transfer (file privat).
     */
    public function proof(Withdrawal $withdrawal): StreamedResponse
    {
        return $this->proofResponse($withdrawal->transfer_proof);
    }
}
