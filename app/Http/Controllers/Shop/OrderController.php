<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    /**
     * Daftar pesanan milik customer yang sedang login (terbaru dulu).
     */
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['product', 'paymentAccount'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('shop.orders.index', compact('orders'));
    }

    /**
     * Detail pesanan customer.
     */
    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->customer_id === (int) $request->user()->id, 403);

        $order->load(['product', 'paymentAccount']);

        return view('shop.orders.show', compact('order'));
    }

    /**
     * Halaman pembayaran: instruksi QRIS / transfer bank & upload bukti bayar.
     */
    public function pay(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless((int) $order->customer_id === (int) $request->user()->id, 403);

        if ($order->isPaid()) {
            return redirect()->route('shop.orders.show', $order);
        }

        $order->load(['product', 'paymentAccount']);

        return view('shop.orders.payment', compact('order'));
    }

    /**
     * Unggah bukti pembayaran oleh customer.
     */
    public function uploadProof(Request $request, Order $order): RedirectResponse
    {
        abort_unless((int) $order->customer_id === (int) $request->user()->id, 403);
        abort_unless(in_array($order->status, [OrderStatus::Pending, OrderStatus::Rejected], true), 403);

        $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'payment_proof.required' => 'File bukti pembayaran wajib diunggah.',
            'payment_proof.file' => 'Bukti pembayaran harus berupa file yang valid.',
            'payment_proof.mimes' => 'Format bukti pembayaran harus berupa JPG, JPEG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran file bukti pembayaran maksimal 2 MB (2048 KB).',
            'payment_proof.uploaded' => 'Bukti pembayaran gagal diunggah. Pastikan ukuran file tidak melebihi 2 MB.',
        ], [
            'payment_proof' => 'bukti pembayaran',
        ]);

        $newPath = $request->file('payment_proof')->store('payment-proofs', 'local');

        if ($order->payment_proof && Storage::disk('local')->exists($order->payment_proof)) {
            Storage::disk('local')->delete($order->payment_proof);
        }

        $order->update([
            'payment_proof' => $newPath,
            'status' => OrderStatus::WaitingVerification,
            'rejection_note' => null,
        ]);

        return redirect()
            ->route('shop.orders.show', $order)
            ->with('status', 'Bukti pembayaran berhasil diunggah')
            ->with('status_detail', "Pesanan {$order->invoice_number} sedang menunggu verifikasi admin.");
    }

    /**
     * Unduh file produk digital (hanya pemilik order dengan status paid).
     */
    public function download(Request $request, Order $order): StreamedResponse|RedirectResponse
    {
        abort_unless((int) $order->customer_id === (int) $request->user()->id, 403);
        abort_unless($order->isPaid(), 403);

        $product = $order->product;

        if (! $product || ! $product->file_path || ! Storage::disk('local')->exists($product->file_path)) {
            return redirect()
                ->route('shop.orders.show', $order)
                ->with('error', 'Maaf, file produk digital belum tersedia atau sedang disiapkan oleh admin. Silakan hubungi admin untuk bantuan.');
        }

        return Storage::disk('local')->download($product->file_path);
    }
}
