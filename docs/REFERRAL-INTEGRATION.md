# Integrasi referral dan komisi

Backend ini bisa dipakai sebelum tampilan checkout dan dashboard afiliator selesai.

## Link referral

`GET /r/{code}?ch=tiktok` mengarahkan pengunjung ke produk dan menyimpan cookie terenkripsi `ref` selama 30 hari. Link terakhir yang valid menggantikan cookie sebelumnya. `utm_source` dipakai jika `ch` kosong. Channel dibatasi 30 karakter.

Kode tidak ditemukan, afiliator nonaktif/bukan role affiliate, atau produk nonaktif menghasilkan 404 tanpa mencatat klik atau mengganti cookie. Klik dengan link, IP, dan user agent yang sama dalam 5 menit dihitung satu kali. Ini pengurangan refresh berulang, bukan perlindungan penuh terhadap bot atau ukuran pengunjung unik.

## Checkout (Arif)

Panggil setelah customer login dan produk ditentukan:

```php
$request->validate(['referral_code' => ['nullable', 'string', 'max:32']]);
$link = $tracker->resolveForCheckout($request, $product);
// Saat membuat order:
// 'referral_link_id' => $link?->id,
```

Input kode manual yang tidak kosong lebih diutamakan daripada cookie. Kode manual tidak valid menghasilkan null, tanpa memakai cookie sebagai pengganti. Input kosong memakai cookie. Link harus cocok dengan produk aktif, dimiliki afiliator aktif dengan role affiliate, dan bukan milik pembeli sendiri. Nilai array/kode terlalu panjang ditolak dengan null. Jika ingin menampilkan pesan kesalahan kode manual, checkout dapat menambahkan validation error ketika input terisi tetapi hasilnya null.

Checkout wajib memakai user yang sudah terautentikasi agar pemeriksaan pembelian lewat link sendiri bisa dilakukan. Verifikasi komisi memeriksa ulang aturan ini dari customer_id order.

## Verifikasi admin (Hanif)

`CommissionService::createForOrder(Order $order): ?Commission` tetap sama. Panggil setelah status order disimpan menjadi paid. Service membaca ulang order dalam transaksi dengan row lock, memeriksa afiliator aktif, mencegah self-referral dan produk yang berbeda. Satu order maksimal satu komisi. Pemanggilan ulang mengembalikan komisi yang sudah ada tanpa menghitung ulang nominal atau mengubah riwayat.

Nominal adalah `round(order.amount * product.commission_rate / 100)` dalam Rupiah. Produk yang dinonaktifkan setelah pembelian tidak otomatis membatalkan hak komisi order paid.

**Masih perlu keputusan tim:** persentase masih diambil saat verifikasi, sesuai implementasi awal. Sepakati kolom snapshot persentase pada orders dan pengisiannya saat checkout bersama Arif sebelum mengubah perilaku ini. Saat ini perubahan persentase produk sebelum verifikasi memengaruhi komisi yang belum dibuat.

## Dashboard afiliator (Gibran)

Inject `App\Services\Referral\ReferralStatistics` ke controller:

```php
$stats = $statistics->summaryFor($request->user());
$links = $statistics->linksFor($request->user());
```

`summaryFor()` mengembalikan total sepanjang waktu untuk pemilik akun: clicks, orders (semua status), paid_orders, conversion_rate (persen), revenue (nilai order paid), commission (approved), dan balance (saldo siap cair dari CommissionService).

`linksFor()` mengembalikan collection link milik akun, relasi product, clicks_count, orders_count, paid_orders_count, dan conversion_rate. Rasio konversi adalah paid_orders / clicks × 100, dibulatkan dua desimal; nol jika belum ada klik. Ini rasio order per klik tercatat sehingga bisa melebihi 100% jika data diimpor/order berulang. Controller wajib memakai user login, bukan ID afiliator dari input bebas.

## Pemeriksaan

```bash
php artisan test --filter=ReferralTrackingTest
php artisan test
```

Test memakai SQLite in-memory sesuai phpunit.xml, tanpa menghapus database lokal.
