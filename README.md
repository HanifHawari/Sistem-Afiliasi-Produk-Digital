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
| atmind@afiliasi.test | Admin |
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

## Keamanan
Pengaman yang sudah terpasang, supaya tidak perlu dibuat ulang di tiap fitur:

| Pengaman | Letak |
|---|---|
| Header keamanan di semua respons (anti-clickjacking, `nosniff`, CSP dasar, HSTS di HTTPS) | `app/Http/Middleware/AddSecurityHeaders.php` |
| Akun yang dinonaktifkan admin langsung keluar dan tidak bisa login | `EnsureAccountIsActive`, `LoginRequest` |
| Ganti atau reset password memutus sesi di perangkat lain | `authenticateSessions()` di `bootstrap/app.php` |
| Pembatasan laju: login, daftar, lupa password, checkout, unggah bukti, link referral, pencairan | middleware `throttle:` di `routes/` |
| Hanya host milik `APP_URL` yang dilayani di production | `trustHosts()` di `bootstrap/app.php` |
| File yang diunggah ke folder publik dibatasi ke gambar atau video | validasi `mimes:` di controller terkait |
| Bukti bayar, bukti transfer, dan file produk disimpan privat | disk `local` (`storage/app/private`) |
| Keputusan admin (setujui, tolak, bayar) tidak bisa diproses dua kali | `lockForUpdate()` di `Admin/OrderController` dan `Admin/WithdrawalController` |
| Ekspor CSV aman dari rumus Excel | `ReportController::csvText()` |

Aturan saat menambah fitur:
- Tampilkan data dengan `{{ }}`, jangan `{!! !!}`. Data yang masuk ke JavaScript atau Alpine wajib lewat `@js(...)`, bukan `'{{ ... }}'`.
- Setiap form selain GET wajib `@csrf`, dan setiap halaman data milik pengguna wajib memeriksa pemiliknya.
- File yang disimpan ke disk `public` wajib divalidasi jenisnya dengan `mimes:`.

## Deploy ke server
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
npm ci && npm run build        # atau unggah folder public/build dari laptop
php artisan optimize
```

Isi `.env` di server:

| Variabel | Nilai | Akibat jika salah |
|---|---|---|
| `APP_ENV` | `production` | Pengaman host tidak aktif |
| `APP_DEBUG` | `false` | Detail error dan isi `.env` bisa terlihat pengunjung |
| `APP_URL` | alamat asli, mis. `https://domainmu.my.id` | **Semua halaman menjadi 400 Bad Request**, karena hanya host ini yang dilayani |
| `TRUSTED_PROXIES` | `*` atau IP proxy, **hanya** jika di belakang Nginx proxy, Cloudflare Tunnel, atau load balancer | Tampilan rusak (CSS diblokir) dan link unduhan ditolak |
| `MAIL_*` | SMTP dari hosting | Email reset password hanya tercatat di log |

Catatan lain:
- Arahkan *document root* ke folder `public/`, bukan ke folder utama project.
- Jangan jalankan `php artisan db:seed` di server. Akun contoh memakai password `password`. Kalau terlanjur, ganti semua password lewat halaman Profil.
- Folder `storage/` dan `bootstrap/cache/` harus bisa ditulis web server.
- Naikkan `upload_max_filesize` dan `post_max_size` PHP ke 64M supaya unggah file produk 50 MB tidak gagal.
- Dengan `APP_URL` berawalan `https://`, cookie sesi otomatis hanya dikirim lewat HTTPS.
- Ekstensi PHP GD bersifat opsional (dipakai untuk memperkecil gambar sampul).
- Cadangkan database dan folder `storage/app` secara rutin.

Rencana lengkap & struktur database: [docs/RENCANA-KERANGKA.md](docs/RENCANA-KERANGKA.md).
Acuan desain: [design/stitch/](design/stitch/).
Panduan alur kerja tim: [CONTRIBUTING.md](CONTRIBUTING.md).
Integrasi backend referral, checkout, dan statistik afiliator: [docs/REFERRAL-INTEGRATION.md](docs/REFERRAL-INTEGRATION.md).
