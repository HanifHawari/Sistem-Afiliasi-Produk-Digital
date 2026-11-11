# Sistem Afiliasi Produk Digital

Platform afiliasi untuk produk digital: affiliator membagikan link referral, sistem mencatat klik & penjualan, lalu menghitung komisi.

## Tim
- Muhammad Hanif Hawari
- Gibran Muhammad Ardan
- Muhammad Arif Bagus 
- Muhammad Fadhil AL Fikry

## Pembagian Tugas
| Branch | Fitur | PIC |
|---|---|---|
| `feature/admin-dashboard` | Kelola produk & komisi, afiliator, QRIS & rekening, verifikasi pembayaran, pencairan komisi, laporan penjualan | Muhammad Hanif Hawari |
| `feature/affiliate-dashboard` | Registrasi & login afiliator, katalog produk & komisi, link referral, materi promosi, statistik klik/penjualan/saldo, pengajuan pencairan | Gibran Muhammad Ardan |
| `feature/storefront-checkout` | Halaman produk untuk customer, checkout, pembayaran QRIS/transfer, upload bukti bayar, pengiriman produk digital | Muhammad Arif Bagus |
| `feature/referral-tracking` | Tracking link referral & kode khusus, pencatatan klik, perhitungan komisi otomatis | Muhammad Fadhil AL Fikry |

## Tech Stack
Laravel 13 + Blade, Tailwind CSS 3 (token desain dari Stitch), Alpine.js, MySQL (Laragon), Laravel Breeze untuk login.

## Instalasi (sekali di tiap laptop)

Kebutuhan: **Laragon** (sudah berisi PHP 8.3, Composer, MySQL, Node.js).

1. Buka Laragon, klik **Start All**.
2. Buat database `afiliasi_db` (Laragon > tombol **Database** > klik kanan > Create new > Database).
3. Jalankan di terminal Laragon (**Terminal**/Cmder) dari folder project:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
```

4. Jalankan aplikasi:

```bash
php artisan serve
```

Buka http://localhost:8000

> Jika perintah `php`/`composer` tidak dikenali di terminal VS Code, pakai terminal dari Laragon,
> atau tambahkan `C:\laragon\bin\php\php-8.3.x` dan `C:\laragon\bin\composer` ke PATH Windows.

### Akun contoh (password: `password`)
| Email | Role |
|---|---|
| hanif@afiliasi.test | Admin |
| gibran@afiliasi.test | Afiliator |
| fadhil@afiliasi.test | Afiliator |
| bagus@afiliasi.test | Customer |

Reset database ke data contoh: `php artisan migrate:fresh --seed`

## Saat Development
- `npm run dev` di terminal kedua, supaya perubahan Blade/CSS langsung terlihat tanpa build ulang.
- Jalankan test sebelum membuat Pull Request: `php artisan test`

### Setelah mengambil perubahan terbaru (`git pull` / `git merge develop`)
```bash
composer install            # bila composer.json berubah
npm install                 # bila package.json berubah
php artisan migrate         # bila ada migration baru
npm run build
```
Bila akun contoh atau data contoh berubah (lihat pesan PR), jalankan `php artisan migrate:fresh --seed`.
Perintah ini mengosongkan database lokal lalu mengisi ulang data contoh (file unggahan lama di storage ikut dibersihkan).
Kalau login dengan akun di tabel atas gagal padahal password benar, biasanya karena langkah ini belum dijalankan.

## Struktur Folder per Anggota
| Bagian | Hanif (Admin) | Gibran (Afiliator) | Arif (Toko) | Fadhil (Referral & Komisi) |
|---|---|---|---|---|
| Route | `routes/admin.php` | `routes/affiliate.php` | `routes/shop.php` | `/r/{code}` di `routes/web.php` |
| Controller | `app/Http/Controllers/Admin/` | `app/Http/Controllers/Affiliate/` | `app/Http/Controllers/Shop/` | `app/Http/Controllers/ReferralController.php` |
| View | `resources/views/admin/` | `resources/views/affiliate/` | `resources/views/shop/` | - |
| Service | - | - | - | `app/Services/Referral/`, `app/Services/Commission/` |
| Layout | `<x-layouts.admin>` | `<x-layouts.affiliate>` | `<x-layouts.shop>` | - |

**File bersama** (kabari tim dulu sebelum mengubah): `app/Models/`, `app/Enums/`, `database/migrations/`,
`resources/views/components/`, `tailwind.config.js`, `bootstrap/app.php`.

Komponen siap pakai: `<x-page-header>`, `<x-stat-grid>` + `<x-stat-card>` (kartu statistik biru, jadi carousel di HP),
`<x-filter-menu>` (tab status, jadi tombol menu di HP), `<x-pager>` (navigasi "1-5 dari N" di bawah tabel), `<x-badge>`,
dan class CSS `.card`, `.btn-primary`, `.btn-outline`, `.data-table`, `.pill-*` di `resources/css/app.css`.
Helper `rupiah(150000)` → `Rp 150.000`.

Rencana lengkap & struktur database: [docs/RENCANA-KERANGKA.md](docs/RENCANA-KERANGKA.md).
Acuan desain: [design/stitch/](design/stitch/).
Panduan alur kerja tim: [CONTRIBUTING.md](CONTRIBUTING.md).
Integrasi backend referral, checkout, dan statistik afiliator: [docs/REFERRAL-INTEGRATION.md](docs/REFERRAL-INTEGRATION.md).
