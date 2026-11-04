# Rencana Kerangka Project

Branch: `feature/setup-project` (PIC: Muhammad Hanif Hawari)
Setelah selesai, branch ini di-merge ke `develop`, lalu semua anggota menjalankan `git merge origin/develop` di branch fitur masing-masing.

## 1. Tech Stack

| Bagian | Pilihan |
|---|---|
| Framework | Laravel (versi stabil terbaru) + Blade |
| Tampilan | Tailwind CSS (via Vite), mengikuti design system di `design/stitch/semua_design-system/DESIGN.md` |
| Font & ikon | Plus Jakarta Sans, Material Symbols Outlined |
| Database | PostgreSQL |
| Auth | Laravel Breeze (Blade) + kolom `role` di tabel `users` |
| Akun customer | Wajib register/login untuk membeli |
| Pengiriman produk | Tombol download aman (signed URL) di halaman pesanan customer |

## 2. Persiapan Laptop (semua anggota)

1. **Laragon** (PHP 8.3, Composer, Node.js sudah termasuk).
2. **PostgreSQL**: install dari https://www.postgresql.org/download/windows/ atau `winget install PostgreSQL.PostgreSQL.17`. Catat password user `postgres`.
3. **Aktifkan ekstensi PHP PostgreSQL**: Laragon > Menu > PHP > Extensions > centang `pdo_pgsql` dan `pgsql`, lalu restart Laragon.
4. Buat database: `createdb -U postgres afiliasi_db` (atau lewat pgAdmin).

## 3. Langkah Pembuatan Kerangka

1. Install Laravel di root repo (file README, CONTRIBUTING, design/, docs/ tetap dipertahankan).
2. Install Breeze (stack Blade) untuk login, register, lupa password, profil.
3. Konfigurasi `.env` ke PostgreSQL, buat `.env.example` tanpa data rahasia.
4. Pindahkan token desain Stitch (warna, font, radius, spacing) ke konfigurasi Tailwind.
5. Buat migration, model, dan relasi untuk semua tabel (bagian 4).
6. Buat middleware `role` dan pemisahan route per area (bagian 5).
7. Buat 3 layout dasar: `admin`, `affiliate`, `shop` (sidebar/navbar mengikuti Stitch).
8. Buat halaman kosong (placeholder) untuk setiap menu, supaya tiap anggota tinggal mengisi.
9. Buat seeder: akun admin, afiliator, customer contoh, produk contoh, rekening & QRIS contoh.
10. Siapkan penyimpanan file: disk `public` untuk thumbnail & materi promosi, disk `local` (privat) untuk file produk & bukti bayar.
11. Perbarui README dengan cara instalasi project untuk anggota tim.
12. Commit, push, buat Pull Request ke `develop`.

## 4. Struktur Database

Semua nominal uang disimpan dalam Rupiah sebagai bilangan bulat (`bigint`), tanpa desimal.

| Tabel | Kolom penting |
|---|---|
| `users` | name, email, password, phone, `role` (admin / affiliate / customer), `is_active` |
| `affiliate_profiles` | user_id, bank_name, account_number, account_holder |
| `products` | name, slug, description, price, `commission_rate` (persen, mis. 40.00), thumbnail, file_path, is_active |
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

File bersama (model, migration, layout, komponen Blade) dibuat di kerangka ini. Jika perlu mengubahnya, kabari tim dulu supaya tidak conflict.

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
