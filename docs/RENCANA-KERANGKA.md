# Rencana Kerangka Project

Branch: `feature/setup-project` (PIC: Muhammad Hanif Hawari)
Status: **kerangka selesai dibuat** (Laravel 13, Breeze, migration, model, role, layout, seeder). Cara instalasi ada di README.
Setelah di-merge ke `develop`, semua anggota menjalankan `git merge origin/develop` di branch fitur masing-masing, lalu langkah instalasi di README.

> **Pembaruan November 2026:** tema tampilan, komponen bersama, logo, halaman login, animasi, dan akun contoh sudah berubah. Lihat [bagian 8](#8-pembaruan-tampilan--data-contoh-november-2026).

## 1. Tech Stack

| Bagian | Pilihan |
|---|---|
| Framework | Laravel 13 + Blade |
| Tampilan | Tailwind CSS (via Vite), tema biru/slate (lihat bagian 8). Struktur token tetap dari `design/stitch/semua_design-system/DESIGN.md` |
| Font & ikon | Plus Jakarta Sans, Material Symbols Outlined |
| Database | MySQL (bawaan Laragon) |
| Auth | Laravel Breeze (Blade) + kolom `role` di tabel `users` |
| Akun customer | Wajib register/login untuk membeli |
| Pengiriman produk | Tombol download aman (signed URL) di halaman pesanan customer |

## 2. Persiapan Laptop (semua anggota)

1. **Laragon** (PHP 8.3, Composer, Node.js, dan MySQL sudah termasuk).
2. Klik **Start All** di Laragon supaya MySQL berjalan.
3. Buat database `afiliasi_db`: Laragon > tombol **Database** (HeidiSQL) > klik kanan koneksi > Create new > Database, atau jalankan `mysql -u root -e "CREATE DATABASE afiliasi_db"`.
4. Koneksi default Laragon: host `127.0.0.1`, port `3306`, user `root`, password kosong.

## 3. Langkah Pembuatan Kerangka

1. Install Laravel di root repo (file README, CONTRIBUTING, design/, docs/ tetap dipertahankan).
2. Install Breeze (stack Blade) untuk login, register, lupa password, profil.
3. Konfigurasi `.env` ke MySQL Laragon, buat `.env.example` tanpa data rahasia.
4. Pindahkan token desain Stitch (warna, font, radius, spacing) ke konfigurasi Tailwind.
5. Buat migration, model, dan relasi untuk semua tabel (bagian 4).
6. Buat middleware `role` dan pemisahan route per area (bagian 5).
7. Buat 3 layout dasar: `admin`, `affiliate`, `shop` (sidebar/navbar mengikuti Stitch).
8. Buat halaman kosong (placeholder) untuk setiap menu, supaya tiap anggota tinggal mengisi.
9. Buat seeder: akun admin, afiliator, customer contoh, produk contoh, rekening & QRIS contoh (akun terbaru di bagian 8).
10. Siapkan penyimpanan file: disk `public` untuk thumbnail & materi promosi, disk `local` (privat) untuk file produk & bukti bayar.
11. Perbarui README dengan cara instalasi project untuk anggota tim.
12. Commit, push, buat Pull Request ke `develop`.

## 4. Struktur Database

Semua nominal uang disimpan dalam Rupiah sebagai bilangan bulat (`bigint`), tanpa desimal.

| Tabel | Kolom penting |
|---|---|
| `users` | name, email, password, phone, `role` (admin / affiliate / customer), `is_active` |
| `affiliate_profiles` | user_id, bank_name, account_number, account_holder |
| `products` | name, slug, category, description, price, `commission_rate` (persen, mis. 40.00), thumbnail, file_path, is_active |
| `promo_materials` | product_id, title, type (image / video / text), file_path, caption |
| `payment_accounts` | type (qris / bank), bank_name, account_number, account_holder, qris_image, is_active |
| `referral_links` | affiliate_id, product_id, `code` (unik) |
| `referral_clicks` | referral_link_id, ip_address, user_agent, channel (tiktok / instagram / wa / dll.), clicked_at |
| `orders` | invoice_number, customer_id, product_id, referral_link_id (nullable), amount, payment_method (qris / bank_transfer), payment_account_id, payment_proof, `status`, verified_by, verified_at, rejection_note |
| `commissions` | order_id, affiliate_id, rate, amount, `status` (pending / approved / cancelled) |
| `withdrawals` | affiliate_id, amount, bank_name, account_number, account_holder, `status`, processed_by, processed_at, transfer_proof, note |

Status `orders`: `pending` (belum bayar) → `waiting_verification` (bukti diunggah) → `paid` (diverifikasi admin) / `rejected`.
Status `withdrawals`: `pending` → `approved` → `paid` / `rejected`.

Saldo afiliator = total komisi `approved` − total pencairan yang tidak `rejected`.

## 5. Struktur Folder & Pembagian Area

```
app/Http/Controllers/Admin/       -> Hanif   (feature/admin-dashboard)
app/Http/Controllers/Affiliate/   -> Gibran  (feature/affiliate-dashboard)
app/Http/Controllers/Shop/        -> Arif    (feature/storefront-checkout)
app/Services/Referral/            -> Fadhil  (feature/referral-tracking)
app/Services/Commission/          -> Fadhil

resources/views/admin/       -> Hanif
resources/views/affiliate/   -> Gibran
resources/views/shop/        -> Arif

routes/admin.php      prefix /admin,     middleware role:admin
routes/affiliate.php  prefix /affiliate, middleware role:affiliate
routes/shop.php       halaman publik + /orders (middleware role:customer)
routes/web.php        landing, redirect /r/{code} (referral)
```

File bersama (model, migration, layout, komponen Blade, `resources/css/app.css`, `tailwind.config.js`) dibuat di kerangka ini. Jika perlu mengubahnya, kabari tim dulu supaya tidak conflict.

## 6. Alur Antar Fitur (kontrak antar anggota)

1. **Referral (Fadhil)**: `/r/{code}` mencatat klik ke `referral_clicks`, menyimpan cookie `ref` selama 30 hari, lalu redirect ke halaman produk.
2. **Checkout (Arif)**: saat membuat order, ambil kode dari cookie `ref` atau input "kode referral", lalu isi `orders.referral_link_id`.
3. **Verifikasi (Hanif)**: admin menyetujui pembayaran → status order `paid` → memanggil `CommissionService::createForOrder($order)`.
4. **Komisi (Fadhil)**: `CommissionService` menghitung `amount = price × commission_rate / 100` dan menyimpan ke `commissions`.
5. **Download (Arif)**: customer dengan order `paid` bisa mengunduh file produk lewat signed URL.
6. **Pencairan (Gibran → Hanif)**: afiliator mengajukan `withdrawals`, admin memproses.

## 7. Acuan Desain (Stitch)

| Layar Stitch | Dipakai untuk |
|---|---|
| `design/stitch/gibran_affiliate-dashboard` | Gibran: layout & dashboard afiliator |
| `design/stitch/arif_storefront-checkout` | Arif: halaman checkout & pembayaran |
| `design/stitch/hanif_admin-dashboard_verifikasi-pembayaran` | Hanif: layout admin & verifikasi pembayaran |
| `design/stitch/hanif_admin-dashboard_produk-komisi` | Hanif: kelola produk & komisi |
| `design/stitch/semua_design-system/DESIGN.md` | Semua: warna, font, badge status, tabel, tombol |

Layar yang belum ada di Stitch (login, laporan, pencairan, katalog customer, dll.) dibuat mengikuti `DESIGN.md`.

Catatan: layout & susunan halaman tetap mengacu ke Stitch, tetapi **warna mengikuti tema biru di bagian 8**.
Panel admin (`resources/views/admin/`) bisa dijadikan contoh jadi pemakaian komponen & class di bawah.

## 8. Pembaruan Tampilan & Data Contoh (November 2026)

### Tema warna
Nama token Tailwind **tidak berubah** (`primary`, `surface`, `on-surface`, dst.), hanya nilainya diganti ke palet biru/slate.
Jadi kode lama tetap jalan, tapi warnanya ikut berubah.

| Token | Warna | Dipakai untuk |
|---|---|---|
| `primary` | `#2563eb` (biru) | tombol utama, link, kartu statistik |
| `secondary` | `#059669` (hijau) | nominal komisi, status berhasil |
| `tertiary` | `#d97706` (amber) | status menunggu |
| `error` | `#dc2626` (merah) | status ditolak, tombol hapus |
| `surface` / `on-surface` | `#f8fafc` / `#0f172a` | latar halaman / teks utama |

Selain token, boleh langsung memakai warna bawaan Tailwind seperti `bg-blue-600`, `text-slate-500`.

### Komponen Blade bersama
| Komponen | Fungsi |
|---|---|
| `<x-page-header title="..." description="...">` | Judul halaman. Tombol di kanan lewat `<x-slot name="actions">`; di HP tombol otomatis berdampingan & ringkas |
| `<x-stat-grid>` + `<x-stat-card label value caption>` | Kartu statistik biru. Di HP jadi carousel geser. `cols="3"` untuk 3 kartu |
| `<x-filter-menu :items="$items">` | Tab filter status. Di HP jadi satu tombol menu. Item: `label`, `href`, `active`, `count` |
| `<x-pager :paginator="$data" noun="transaksi">` | Kaki tabel "1-5 dari 34 transaksi" + tombol Sebelumnya/Berikutnya |
| `<x-badge tone="success">` | Label status. `tone`: success / warning / danger / info / neutral (cocok dengan `->tone()` di enum) |
| `<x-application-logo class="h-10 w-10">` | Logo aplikasi (ikon kotak produk). Ganti logo cukup di file ini |

### Class CSS siap pakai (`resources/css/app.css`)
| Class | Fungsi |
|---|---|
| `.card`, `.card-header`, `.card-title`, `.card-subtitle`, `.card-body`, `.card-link` | Kartu konten putih |
| `.btn-primary`, `.btn-outline`, `.btn-success`, `.btn-danger`, `.btn-soft`, `.btn-sm` | Tombol |
| `.input` | Input, select, textarea |
| `.data-table` + `.ledger` | Tabel data: kepala biru; di HP seluruh tabel bisa digeser ke samping (bungkus dengan `div.relative.overflow-x-auto`) |
| `.pill-blue`, `.pill-green`, `.pill-amber`, `.pill-red`, `.pill-gray` | Label kecil berwarna |
| `.toolbar`, `.search` | Baris filter + kolom cari |
| `.panel` | Kotak abu-abu untuk ringkasan angka |

### Logo & favicon
Logo aplikasi sekarang ikon kotak produk (Material Symbols `package_2`) di kotak biru, dipakai di header dashboard,
halaman login/daftar, dan navbar toko. Favicon: `public/favicon.svg`.
Di atas latar biru pakai versi putih: `<x-application-logo inverse class="h-14 w-14" />`.

### Halaman login & daftar
Layout: `resources/views/layouts/guest.blade.php` (dipakai login, daftar, lupa password, reset, konfirmasi, verifikasi email).

| Layar | Tampilan |
|---|---|
| Desktop | Kiri: panel biru bergambar samar (`partials/auth-backdrop`) berisi logo & nama sistem. Kanan: form dalam kartu putih |
| HP | Seluruh layar biru bergambar samar, form dalam kartu putih di tengah |

Judul & keterangan kartu diisi lewat atribut: `<x-guest-layout title="Masuk" subtitle="...">`.

### Animasi & transisi
| Kapan | Desktop | HP |
|---|---|---|
| Tombol Masuk/Daftar ditekan | Tombol jadi "Memproses...", lalu halaman login **terbelah di garis biru\|putih** (kiri ke kiri, kanan ke kanan) | Tombol jadi "Memproses...", kartu form bergeser ke kanan, logo & nama ke kiri, lalu latar biru naik ke atas memperlihatkan dashboard |
| Halaman pertama setelah login | Menu samping muncul dari kiri satu per satu, **setelah itu** judul & kartu muncul dari bawah satu per satu | Setelah latar biru naik, judul & kartu muncul dari bawah (cepat) |
| Pindah ke halaman lain (klik menu) | Hanya judul, kartu, & baris filter muncul dari bawah. Menu samping diam | Sama, lebih cepat |
| Ganti filter / paginasi / setelah tombol aksi (halaman sama), tombol Back | Tanpa animasi | Tanpa animasi |

Yang perlu diketahui saat membuat halaman di **layout dashboard** (admin & afiliator):
- Elemen yang otomatis ikut animasi: `<x-page-header>`, `<x-stat-card>`, elemen ber-class `.card` dan `.toolbar` di dalam `<main>`.
  Elemen lain bisa ikut dengan atribut `data-enter="up"`.
- Form login/daftar memakai atribut `data-intro` (pemicu animasi setelah login). Jangan dipakai di form lain.
- Layout toko (`<x-layouts.shop>`) **tidak** terpengaruh, kecuali transisi terbelah/memudar setelah customer login.
- Pengguna yang mematikan animasi di perangkatnya (pengaturan aksesibilitas) tidak mendapat animasi sama sekali.
- Transisi terbelah memakai View Transitions API (Chrome, Edge, Safari terbaru). Di Firefox halaman langsung berpindah,
  animasi menu & kartu tetap jalan.
- Kode: `resources/css/app.css` (bagian "Halaman login / daftar"), `resources/js/app.js`, `resources/views/partials/head.blade.php`.

### Foto profil
Semua pengguna (admin, afiliator, customer) bisa mengganti foto di halaman **Profil** (`/profile`).
- Kolom baru `users.avatar`, file disimpan di `storage/app/public/avatars` (JPG/PNG/WEBP, maks. 2 MB). Foto lama otomatis dihapus saat diganti.
- Tampilkan di mana saja dengan `<x-avatar :user="$user" class="h-10 w-10" />` (tanpa foto = inisial nama).
- Setelah pull: jalankan `php artisan migrate` (atau `migrate:fresh --seed`) dan pastikan `php artisan storage:link` sudah pernah dijalankan.

### Gambar sampul produk
Admin mengunggah gambar sampul di **Produk → Edit → Gambar sampul** (bukan di kolom *File produk*, yang berisi file unduhan pembeli).
- Gambar otomatis dipotong dari tengah ke rasio **16:10** dan diperkecil ke lebar maksimal **1600 px**, lalu disimpan sebagai WebP di `storage/app/public/products/thumbnails`. Ukuran ideal: 1600×1000 px.
- Batas unggah: JPG/PNG/WebP, maks. 5 MB, minimal 640×400 px, maksimal 4096 px per sisi.
- Kode: `App\Services\Media\ProductThumbnail`. Tampilkan gambarnya dengan `asset('storage/'.$product->thumbnail)` di dalam kotak `aspect-[16/10]` + `object-cover`.
- Gambar produk contoh disimpan di `database/seeders/sampul/` dengan nama file = slug produk, dan otomatis dipasang saat `migrate:fresh --seed`. Daftar nama filenya ada di README folder itu.
- Ekstensi PHP **GD** bersifat opsional. Kalau aktif (cek: `php -m`), gambar dipotong dan diperkecil. Kalau tidak, gambar disimpan apa adanya dan katalog tetap menampilkannya 16:10 lewat CSS, hanya filenya lebih berat.

### Keamanan & halaman error (hasil audit November 2026)
Daftar pengaman dan checklist deploy ada di README, bagian **Keamanan** dan **Deploy ke server**. Yang perlu diingat saat coding:
- Data ke JavaScript atau Alpine selalu lewat `@js(...)`. Pola `'{{ $nilai }}'` di dalam `x-data` bisa disusupi tanda petik.
- Di elemen HTML biasa, `:disabled="$variabel"` dibaca Alpine sebagai JavaScript, bukan PHP. Pakai `@disabled($variabel)`.
- Beberapa route sekarang dibatasi lajunya (`throttle:`). Kalau melewati batas, pengguna melihat halaman 429.
- Halaman error (403, 404, 419, 429, 500, 503) ada di `resources/views/errors/`. Halamannya berdiri sendiri dan tidak memakai layout aplikasi.
- Komponen `<x-placeholder>` dan view `shop/home` sudah dihapus karena tidak dipakai lagi.

### Akun contoh (password: `password`)
| Nama | Peran | Email |
|---|---|---|
| Hanif | Admin | atmind@afiliasi.test |
| Gibran | Afiliator utama (punya penjualan, komisi, pencairan) | gibran@afiliasi.test |
| Fadhil | Afiliator kedua | fadhil@afiliasi.test |
| Bagus | Customer (pemilik semua pesanan contoh) | bagus@afiliasi.test |

Akun lama (`hanif@`, `admin@`, `afiliator@`, `customer@afiliasi.test`) **sudah tidak ada**.

### Yang perlu dijalankan setiap anggota setelah pembaruan ini di-merge
```
git checkout develop
git pull
git checkout <branch-fitur-kamu>
git merge develop
php artisan migrate:fresh --seed
npm run build
```
