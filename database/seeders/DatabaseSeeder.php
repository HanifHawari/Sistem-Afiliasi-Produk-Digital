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
use App\Models\ReferralLink;
use App\Models\User;
use App\Services\Commission\CommissionService;
use App\Services\Media\ProductThumbnail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data contoh untuk development. Semua akun memakai password: password
 *
 *   atmind@afiliasi.test  -> Admin
 *   gibran@afiliasi.test  -> Afiliator utama
 *   fadhil@afiliasi.test  -> Afiliator kedua
 *   bagus@afiliasi.test   -> Customer
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Produk contoh: nama, kategori, harga, komisi (%), deskripsi.
     * Empat pertama diambil dari desain Stitch dan dipakai order contoh di bawah; jangan ubah urutannya.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: int, 4: string}>
     */
    private const PRODUCTS = [
        ['Mastering TikTok Organic & Affiliate 2025', 'E-Course', 150000, 40,
            'Kelas video langkah demi langkah membangun akun TikTok dari nol sampai menghasilkan komisi afiliasi, tanpa iklan berbayar.'],
        ['Template Notion All-in-One Freelancer OS', 'Template Notion', 99000, 50,
            'Satu ruang kerja Notion untuk mengatur klien, proyek, invoice, dan keuangan freelancer. Tinggal duplikat dan pakai.'],
        ['Formula 100 Juta Pertama Bisnis Digital', 'Ebook', 75000, 40,
            'Ebook 120 halaman berisi peta jalan memilih produk, membangun audiens, dan mencapai omzet 100 juta pertama.'],
        ['Preset Lightroom & CapCut Cinematic Pack', 'Creative Assets', 120000, 35,
            'Kumpulan 60 preset Lightroom dan 25 template CapCut bernuansa sinematik untuk konten foto dan video pendek.'],
        ['Kelas Copywriting Jualan Online dari Nol', 'E-Course', 135000, 40,
            'Belajar menulis caption, headline, dan halaman penjualan yang membuat calon pembeli langsung checkout. Berisi 24 video dan lembar latihan.'],
        ['Template Canva 150+ Konten Instagram UMKM', 'Template Canva', 69000, 45,
            'Lebih dari 150 desain feed, story, dan carousel siap edit di Canva untuk promosi, testimoni, dan katalog produk UMKM.'],
        ['Panduan Shopee Affiliate untuk Pemula', 'Ebook', 59000, 45,
            'Panduan praktis memilih produk laris, membuat konten yang menarik pembeli, dan menaikkan komisi Shopee Affiliate dari bulan pertama.'],
        ['Lisensi Plugin Auto Caption Video Pendek', 'Software / License Key', 199000, 30,
            'Lisensi seumur hidup plugin pembuat subtitle otomatis berbahasa Indonesia untuk Reels, Shorts, dan TikTok.'],
        ['Bundle 500+ Sound Effect & Musik Bebas Royalti', 'Creative Assets', 89000, 35,
            'Lebih dari 500 efek suara dan musik latar bebas royalti, aman dipakai untuk konten YouTube, podcast, dan iklan.'],
    ];

    /** Format gambar sampul contoh yang dicari di database/seeders/sampul, berurutan. */
    public const THUMBNAIL_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png'];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->removeUnusedUploads();

        $admin = User::factory()->admin()->create([
            'name' => 'Hanif',
            'email' => 'atmind@afiliasi.test',
            'phone' => '085264019832',
        ]);

        $affiliate = User::factory()->affiliate()->create([
            'name' => 'Gibran',
            'email' => 'gibran@afiliasi.test',
            'phone' => '081371904526',
        ]);
        $affiliate->affiliateProfile()->create([
            'bank_name' => 'BCA',
            'account_number' => '7712045318',
            'account_holder' => 'Gibran',
        ]);

        $customer = User::factory()->customer()->create([
            'name' => 'Bagus',
            'email' => 'bagus@afiliasi.test',
            'phone' => '082169785312',
        ]);

        $products = collect(self::PRODUCTS)->map(fn ($p) => Product::create([
            'name' => $p[0],
            'slug' => Str::slug($p[0]),
            'category' => $p[1],
            'description' => $p[4],
            'price' => $p[2],
            'commission_rate' => $p[3],
            // Gambar sampul dari database/seeders/sampul/{slug}.webp|jpg|png (kosong jika filenya belum ada)
            'thumbnail' => $this->sampleThumbnail(Str::slug($p[0])),
            // File PDF contoh supaya alur beli -> unduh bisa langsung dicoba
            'file_path' => $this->fakeProductFile(Str::slug($p[0]), $p[0]),
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

        // Link referral Gibran untuk semua produk
        $links = $products->mapWithKeys(fn (Product $product) => [
            $product->id => $affiliate->referralLinks()->create([
                'product_id' => $product->id,
                'code' => 'GIBRAN'.$product->id,
            ]),
        ]);

        // Klik contoh di link Gibran. Jumlahnya selalu di atas jumlah penjualan link itu,
        // supaya rasio konversi yang tampil masuk akal (tidak lebih dari 100%).
        foreach ($links->values() as $i => $link) {
            $this->seedClicks($link, [40, 25, 12, 8, 22, 18, 14, 16, 11][$i] ?? 10);
        }

        // Afiliator kedua: satu link produk
        $fadhil = User::factory()->affiliate()->create([
            'name' => 'Fadhil',
            'email' => 'fadhil@afiliasi.test',
            'phone' => '085261337840',
        ]);
        $fadhil->affiliateProfile()->create(['bank_name' => 'MANDIRI', 'account_number' => '1370045812', 'account_holder' => 'Fadhil']);
        $this->seedClicks($fadhil->referralLinks()->create(['product_id' => $products[1]->id, 'code' => 'FADHIL2']), 30);

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

        // Riwayat penjualan 45 hari terakhir, supaya laporan & grafik punya data
        $this->seedSalesHistory($products, [$links, $fadhil->referralLinks()->get()->keyBy('product_id')], $customer, $qris, $bca);

        // Pengajuan pencairan contoh: tiap pengajuan minimal Rp 50.000, total 185.000 (masih di bawah komisi Gibran)
        $bank = $affiliate->affiliateProfile->only(['bank_name', 'account_number', 'account_holder']);

        $paid = $affiliate->withdrawals()->create($bank + [
            'amount' => 60000,
            'status' => WithdrawalStatus::Paid,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'note' => 'Ref. transfer 889120',
        ]);
        $paid->update(['transfer_proof' => $this->fakeReceipt("withdrawal-proofs/WD-{$paid->id}.png", [
            'Ke      : BCA 7712045318',
            'a.n     : Gibran',
            'Nominal : Rp 60.000',
            'Berita  : Pencairan komisi',
        ])]);

        $affiliate->withdrawals()->create($bank + [
            'amount' => 50000,
            'status' => WithdrawalStatus::Approved,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $affiliate->withdrawals()->create($bank + [
            'amount' => 75000,
            'status' => WithdrawalStatus::Pending,
        ]);
    }

    /**
     * Klik contoh pada satu link referral, tersebar di beberapa channel dan jam.
     */
    private function seedClicks(ReferralLink $link, int $count): void
    {
        $channels = ['tiktok', 'instagram', 'wa', 'facebook', null];

        foreach (range(1, $count) as $n) {
            $link->clicks()->create([
                'ip_address' => '10.0.0.'.$n,
                'channel' => $channels[$n % count($channels)],
                'clicked_at' => now()->subHours($n * 3),
            ]);
        }
    }

    /**
     * Order berstatus paid yang tersebar di 45 hari terakhir (data contoh laporan).
     *
     * @param  array<int, Collection>  $linkSets  link referral per afiliator, key = product_id
     */
    private function seedSalesHistory($products, array $linkSets, User $customer, PaymentAccount $qris, PaymentAccount $bca): void
    {
        mt_srand(45);
        $commissions = app(CommissionService::class);

        foreach (range(1, 30) as $n) {
            $product = $products[mt_rand(0, $products->count() - 1)];
            $paidAt = now()->subDays(mt_rand(1, 45))->setTime(mt_rand(8, 21), mt_rand(0, 59));
            // Kurang lebih 60% penjualan datang dari afiliator
            $link = mt_rand(1, 10) <= 6 ? $linkSets[mt_rand(0, count($linkSets) - 1)]->get($product->id) : null;
            $account = mt_rand(0, 1) ? $qris : $bca;

            $order = Order::create([
                'invoice_number' => 'INV-'.$paidAt->format('Ymd').'-H'.str_pad($n, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'referral_link_id' => $link?->id,
                'amount' => $product->price,
                'payment_method' => $account->isQris() ? PaymentMethod::Qris : PaymentMethod::BankTransfer,
                'payment_account_id' => $account->id,
                'status' => OrderStatus::Paid,
                'verified_at' => $paidAt,
            ]);
            $order->forceFill(['created_at' => $paidAt->copy()->subHours(2), 'updated_at' => $paidAt])->saveQuietly();

            $commissions->createForOrder($order)?->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->saveQuietly();
        }
    }

    /**
     * migrate:fresh mengosongkan database tetapi tidak menyentuh folder storage. Hapus file unggahan
     * yang tidak lagi dirujuk database (sisa seed / unggahan sebelumnya) supaya tidak menumpuk.
     */
    private function removeUnusedUploads(): void
    {
        $used = collect([
            ...DB::table('orders')->pluck('payment_proof'),
            ...DB::table('withdrawals')->pluck('transfer_proof'),
            ...DB::table('products')->pluck('file_path'),
            ...DB::table('products')->pluck('thumbnail'),
            ...DB::table('payment_accounts')->pluck('qris_image'),
            ...DB::table('promo_materials')->pluck('file_path'),
            ...DB::table('users')->pluck('avatar'),
        ])->filter()->flip();

        $folders = [
            'local' => ['payment-proofs', 'withdrawal-proofs', 'products/files'],
            'public' => ['qris', 'products/thumbnails', 'promo-materials', 'avatars'],
        ];

        foreach ($folders as $disk => $dirs) {
            foreach ($dirs as $dir) {
                foreach (Storage::disk($disk)->allFiles($dir) as $file) {
                    if (! $used->has($file)) {
                        Storage::disk($disk)->delete($file);
                    }
                }
            }
        }
    }

    /**
     * Gambar sampul contoh. Taruh gambarnya di folder database/seeders/sampul dengan nama = slug produk,
     * misalnya mastering-tiktok-organic-affiliate-2025.webp. Gambar diproses seperti unggahan admin
     * (dipotong 16:10, lebar maksimal 1600 px). Produk tanpa file di folder itu tidak diberi gambar.
     */
    private function sampleThumbnail(string $slug): ?string
    {
        foreach (self::THUMBNAIL_EXTENSIONS as $extension) {
            $path = database_path("seeders/sampul/{$slug}.{$extension}");

            if (is_file($path)) {
                return app(ProductThumbnail::class)->store(new File($path));
            }
        }

        return null;
    }

    /**
     * PDF satu halaman berisi nama produk, sebagai file produk digital contoh (disk local/privat).
     */
    private function fakeProductFile(string $slug, string $name): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], 'File contoh: '.$name);
        $stream = "BT /F1 16 Tf 56 770 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $path = "products/files/{$slug}.pdf";
        Storage::disk('local')->put($path, $pdf);

        return $path;
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
