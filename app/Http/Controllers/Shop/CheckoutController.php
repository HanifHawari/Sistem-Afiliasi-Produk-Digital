<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentAccountType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ReferralLink;
use App\Models\User;
use App\Services\Referral\ReferralTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Checkout customer (desain: design/stitch/arif_storefront-checkout)
 */
class CheckoutController extends Controller
{
    public function create(Request $request, Product $product): View
    {
        abort_unless($product->is_active, 404);

        $accounts = PaymentAccount::active()->orderBy('id')->get();
        $qrisAccount = $accounts->first(fn (PaymentAccount $account) => $account->isQris());
        $bankAccounts = $accounts->reject(fn (PaymentAccount $account) => $account->isQris())->values();

        $pendingOrder = $request->user()->orders()
            ->where('product_id', $product->id)
            ->where('status', OrderStatus::Pending)
            ->latest('id')
            ->first();

        $referralCode = (string) old('referral_code', (string) $request->cookie(ReferralTracker::COOKIE_NAME, ''));

        return view('shop.checkout', compact('product', 'qrisAccount', 'bankAccounts', 'pendingOrder', 'referralCode'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $methods = $this->availableMethods();
        if ($methods === []) {
            return back()->with('error', 'Metode pembayaran belum tersedia. Silakan hubungi admin.');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', Rule::in($methods)],
            'payment_account_id' => [
                'exclude_unless:payment_method,'.PaymentMethod::BankTransfer->value,
                'required', 'integer',
                Rule::exists('payment_accounts', 'id')
                    ->where('type', PaymentAccountType::Bank->value)
                    ->where('is_active', true),
            ],
        ], [
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran yang dipilih tidak tersedia.',
            'payment_account_id.required' => 'Rekening tujuan wajib dipilih untuk metode transfer bank.',
            'payment_account_id.integer' => 'Rekening tujuan yang dipilih tidak valid.',
            'payment_account_id.exists' => 'Rekening tujuan yang dipilih tidak valid atau sudah tidak aktif.',
        ], [
            'payment_method' => 'metode pembayaran',
            'payment_account_id' => 'rekening tujuan',
        ]);

        $user = $request->user();
        $method = PaymentMethod::from($data['payment_method']);
        $referralLink = $this->resolveReferral((string) $request->input('referral_code', ''), $product, $user);

        $order = DB::transaction(function () use ($user, $product, $method, $data, $referralLink) {
            // Kunci baris user supaya klik dobel tidak membuat 2 order pending
            User::whereKey($user->id)->lockForUpdate()->first();

            $account = $method === PaymentMethod::Qris
                ? PaymentAccount::active()->where('type', PaymentAccountType::Qris)->orderBy('id')->first()
                : PaymentAccount::active()->where('type', PaymentAccountType::Bank)->find($data['payment_account_id']);

            if (! $account) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Metode pembayaran yang dipilih sudah tidak tersedia. Silakan pilih yang lain.',
                ]);
            }

            $existing = Order::where('customer_id', $user->id)
                ->where('product_id', $product->id)
                ->where('status', OrderStatus::Pending)
                ->latest('id')
                ->first();

            // Pakai order pending yang sudah ada: nominal & invoice tetap,
            // metode pembayaran mengikuti pilihan terbaru.
            if ($existing) {
                $existing->payment_method = $method;
                $existing->payment_account_id = $account->id;
                if (! $existing->referral_link_id && $referralLink) {
                    $existing->referral_link_id = $referralLink->id;
                }
                $existing->save();

                return $existing;
            }

            return Order::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $user->id,
                'product_id' => $product->id,
                'referral_link_id' => $referralLink?->id,
                'amount' => $product->price,
                'payment_method' => $method,
                'payment_account_id' => $account->id,
                'status' => OrderStatus::Pending,
            ]);
        });

        return redirect()
            ->route('shop.orders.pay', $order)
            ->with('status', "Pesanan {$order->invoice_number} dibuat")
            ->with('status_detail', 'Selesaikan pembayaran sesuai instruksi di bawah.');
    }

    /**
     * Metode yang punya minimal satu akun pembayaran aktif.
     *
     * @return list<string>
     */
    private function availableMethods(): array
    {
        $types = PaymentAccount::active()->distinct()->pluck('type')
            ->map(fn ($type) => $type instanceof PaymentAccountType ? $type : PaymentAccountType::from($type));

        $methods = [];
        if ($types->contains(PaymentAccountType::Qris)) {
            $methods[] = PaymentMethod::Qris->value;
        }
        if ($types->contains(PaymentAccountType::Bank)) {
            $methods[] = PaymentMethod::BankTransfer->value;
        }

        return $methods;
    }

    /**
     * Kode referral hanya dipakai jika ada, untuk produk yang sama, dan bukan milik pembeli sendiri.
     * Selain itu diabaikan tanpa error.
     */
    private function resolveReferral(string $code, Product $product, User $user): ?ReferralLink
    {
        $code = trim($code);
        if ($code === '' || mb_strlen($code) > 50) {
            return null;
        }

        $link = ReferralLink::where('code', $code)
            ->where('product_id', $product->id)
            ->first();

        if (! $link || (int) $link->affiliate_id === (int) $user->id) {
            return null;
        }

        return $link;
    }

    /**
     * Format INV-YYYYMMDD-XXXX (XXXX = 4 karakter huruf besar/angka acak).
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';

        do {
            $number = $prefix.Str::upper(Str::random(4));
        } while (Order::where('invoice_number', $number)->exists());

        return $number;
    }
}

