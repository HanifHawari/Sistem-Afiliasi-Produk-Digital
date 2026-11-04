<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentAccountType;
use App\Enums\PaymentMethod;
use App\Enums\PromoMaterialType;
use App\Enums\WithdrawalStatus;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\User;
use App\Services\Commission\CommissionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data contoh untuk development. Semua akun memakai password: password
 *
 *   admin@afiliasi.test      -> Admin
 *   afiliator@afiliasi.test  -> Afiliator (Budi Santoso)
 *   customer@afiliasi.test   -> Customer
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Afiliasi',
            'email' => 'admin@afiliasi.test',
        ]);

        $affiliate = User::factory()->affiliate()->create([
            'name' => 'Budi Santoso',
            'email' => 'afiliator@afiliasi.test',
            'phone' => '081234567890',
        ]);
        $affiliate->affiliateProfile()->create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Budi Santoso',
        ]);

        $customer = User::factory()->customer()->create([
            'name' => 'Rian Firmansyah',
            'email' => 'customer@afiliasi.test',
            'phone' => '081288231194',
        ]);

        // Produk contoh, diambil dari desain Stitch
        $products = collect([
            ['Mastering TikTok Organic & Affiliate 2025', 'E-Course', 150000, 40],
            ['Template Notion All-in-One Freelancer OS', 'Template Notion', 99000, 50],
            ['Formula 100 Juta Pertama Bisnis Digital', 'Ebook', 75000, 40],
            ['Preset Lightroom & CapCut Cinematic Pack', 'Creative Assets', 120000, 35],
        ])->map(fn ($p) => Product::create([
            'name' => $p[0],
            'slug' => Str::slug($p[0]),
            'category' => $p[1],
            'description' => 'Deskripsi contoh untuk '.$p[0].'.',
            'price' => $p[2],
            'commission_rate' => $p[3],
        ]));

        $products->first()->promoMaterials()->create([
            'title' => 'Caption TikTok',
            'type' => PromoMaterialType::Text,
            'caption' => 'Mau FYP tanpa iklan? Pelajari strateginya di sini 👇',
        ]);

        $qris = PaymentAccount::create([
            'type' => PaymentAccountType::Qris,
            'account_holder' => 'Afiliasi Produk Digital',
            'qris_image' => $this->fakeQris(),
        ]);
        $bca = PaymentAccount::create([
            'type' => PaymentAccountType::Bank,
            'bank_name' => 'BCA',
            'account_number' => '8091129900',
            'account_holder' => 'PT Afiliasi Produk Digital',
        ]);

        // Link referral Budi untuk semua produk
        $links = $products->mapWithKeys(fn (Product $product) => [
            $product->id => $affiliate->referralLinks()->create([
                'product_id' => $product->id,
                'code' => 'BUDI'.$product->id,
            ]),
        ]);

        // Klik contoh di link Budi (supaya angka konversi terlihat)
        $channels = ['tiktok', 'instagram', 'wa', 'facebook', null];
        foreach ($links->values() as $i => $link) {
            foreach (range(1, [40, 25, 12, 8][$i] ?? 5) as $n) {
                $link->clicks()->create([
                    'ip_address' => '10.0.0.'.$n,
                    'channel' => $channels[$n % count($channels)],
                    'clicked_at' => now()->subHours($n * 3),
                ]);
            }
        }

        // Afiliator lain: satu aktif tanpa penjualan, satu nonaktif
        $dina = User::factory()->affiliate()->create([
            'name' => 'Dina Putri',
            'email' => 'dina@afiliasi.test',
            'phone' => '081377889900',
        ]);
        $dina->affiliateProfile()->create(['bank_name' => 'MANDIRI', 'account_number' => '1370019283', 'account_holder' => 'Dina Putri']);
        $dina->referralLinks()->create(['product_id' => $products[1]->id, 'code' => 'DINA2'])
            ->clicks()->create(['channel' => 'instagram', 'clicked_at' => now()->subDay()]);

        User::factory()->affiliate()->create([
            'name' => 'Reza Hardiansyah',
            'email' => 'reza@afiliasi.test',
            'is_active' => false,
        ]);

        // Order contoh dengan berbagai status, supaya halaman admin bisa langsung diuji
        $orders = [
            [$products[0], $links[$products[0]->id], PaymentMethod::BankTransfer, $bca, OrderStatus::WaitingVerification],
            [$products[1], $links[$products[1]->id], PaymentMethod::Qris, $qris, OrderStatus::Paid],
            [$products[2], null, PaymentMethod::BankTransfer, $bca, OrderStatus::Pending],
            [$products[3], $links[$products[3]->id], PaymentMethod::Qris, $qris, OrderStatus::Rejected],
        ];

        foreach ($orders as $i => [$product, $link, $method, $account, $status]) {
            $order = Order::create([
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'referral_link_id' => $link?->id,
                'amount' => $product->price,
                'payment_method' => $method,
                'payment_account_id' => $account->id,
                'status' => $status,
                'verified_by' => in_array($status, [OrderStatus::Paid, OrderStatus::Rejected]) ? $admin->id : null,
                'verified_at' => in_array($status, [OrderStatus::Paid, OrderStatus::Rejected]) ? now() : null,
                'rejection_note' => $status === OrderStatus::Rejected ? 'Nominal transfer tidak sesuai.' : null,
            ]);

            if ($status !== OrderStatus::Pending) {
                $order->update(['payment_proof' => $this->fakeReceipt("payment-proofs/{$order->invoice_number}.png", [
                    'Tanggal : '.$order->created_at->format('d/m/Y H:i'),
                    'Ke      : '.($account->bank_name ?? 'QRIS'),
                    'Rek     : '.($account->account_number ?? '-'),
                    'Nominal : Rp '.number_format($order->amount, 0, ',', '.'),
                    'Berita  : '.$order->invoice_number,
                ])]);
            }

            if ($order->isPaid()) {
                app(CommissionService::class)->createForOrder($order);
            }
        }

        // Pengajuan pencairan contoh (total 45.000, masih di bawah komisi Budi 49.500)
        $bank = ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Budi Santoso'];

        $paid = $affiliate->withdrawals()->create($bank + [
            'amount' => 15000,
            'status' => WithdrawalStatus::Paid,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'note' => 'Ref. transfer 889120',
        ]);
        $paid->update(['transfer_proof' => $this->fakeReceipt("withdrawal-proofs/WD-{$paid->id}.png", [
            'Ke      : BCA 1234567890',
            'a.n     : Budi Santoso',
            'Nominal : Rp 15.000',
            'Berita  : Pencairan komisi',
        ])]);

        $affiliate->withdrawals()->create($bank + [
            'amount' => 10000,
            'status' => WithdrawalStatus::Approved,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $affiliate->withdrawals()->create($bank + [
            'amount' => 20000,
            'status' => WithdrawalStatus::Pending,
        ]);
    }

    /**
     * Gambar mirip kode QR (bukan QRIS asli), hanya untuk tampilan contoh.
     */
    private function fakeQris(): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $cells = 29;
        $size = 10;
        $image = imagecreatetruecolor($cells * $size, $cells * $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 11, 28, 48);
        imagefill($image, 0, 0, $white);

        mt_srand(2026);
        for ($y = 0; $y < $cells; $y++) {
            for ($x = 0; $x < $cells; $x++) {
                if (mt_rand(0, 1)) {
                    imagefilledrectangle($image, $x * $size, $y * $size, ($x + 1) * $size - 1, ($y + 1) * $size - 1, $black);
                }
            }
        }
        // Tiga penanda sudut khas kode QR
        foreach ([[0, 0], [$cells - 7, 0], [0, $cells - 7]] as [$cx, $cy]) {
            imagefilledrectangle($image, $cx * $size, $cy * $size, ($cx + 7) * $size - 1, ($cy + 7) * $size - 1, $black);
            imagefilledrectangle($image, ($cx + 1) * $size, ($cy + 1) * $size, ($cx + 6) * $size - 1, ($cy + 6) * $size - 1, $white);
            imagefilledrectangle($image, ($cx + 2) * $size, ($cy + 2) * $size, ($cx + 5) * $size - 1, ($cy + 5) * $size - 1, $black);
        }

        ob_start();
        imagepng($image);
        Storage::disk('public')->put('qris/contoh-qris.png', ob_get_clean());
        imagedestroy($image);

        return 'qris/contoh-qris.png';
    }

    /**
     * Gambar struk transfer sederhana untuk data contoh (bukti bayar & bukti pencairan).
     *
     * @param  list<string>  $lines
     */
    private function fakeReceipt(string $path, array $lines): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor(360, 480);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 11, 28, 48);
        $green = imagecolorallocate($image, 0, 108, 73);
        imagefill($image, 0, 0, $white);

        imagestring($image, 5, 24, 40, 'TRANSFER BERHASIL', $green);
        foreach ($lines as $i => $text) {
            imagestring($image, 5, 24, 104 + $i * 32, $text, $ink);
        }

        ob_start();
        imagepng($image);
        Storage::disk('local')->put($path, ob_get_clean());
        imagedestroy($image);

        return $path;
    }
}
