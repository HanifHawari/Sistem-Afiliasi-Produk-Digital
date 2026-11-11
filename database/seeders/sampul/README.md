# Gambar sampul produk contoh

Gambar di folder ini otomatis dipasang ke produk contoh setiap kali `php artisan db:seed` atau
`php artisan migrate:fresh --seed` dijalankan. Karena ikut tersimpan di Git, gambarnya juga muncul
di laptop anggota tim lain dan di server.

## Cara menambah atau mengganti gambar

1. Beri nama file sesuai tabel di bawah. Format yang diterima: `.webp`, `.jpg`, `.jpeg`, `.png`.
2. Taruh filenya di folder ini.
3. Jalankan `php artisan migrate:fresh --seed`.

Gambar diproses sama seperti unggahan lewat halaman admin: dipotong dari tengah ke rasio 16:10 dan
diperkecil ke lebar maksimal 1600 px. Ukuran ideal 1600×1000 px. Usahakan tiap file di bawah 500 KB
supaya repository tetap ringan.

Produk yang filenya belum ada di sini tetap dibuat, hanya tanpa gambar.

## Nama file per produk

| Produk | Nama file (tanpa ekstensi) |
|---|---|
| Mastering TikTok Organic & Affiliate 2025 | `mastering-tiktok-organic-affiliate-2025` |
| Template Notion All-in-One Freelancer OS | `template-notion-all-in-one-freelancer-os` |
| Formula 100 Juta Pertama Bisnis Digital | `formula-100-juta-pertama-bisnis-digital` |
| Preset Lightroom & CapCut Cinematic Pack | `preset-lightroom-capcut-cinematic-pack` |
| Kelas Copywriting Jualan Online dari Nol | `kelas-copywriting-jualan-online-dari-nol` |
| Template Canva 150+ Konten Instagram UMKM | `template-canva-150-konten-instagram-umkm` |
| Panduan Shopee Affiliate untuk Pemula | `panduan-shopee-affiliate-untuk-pemula` |
| Lisensi Plugin Auto Caption Video Pendek | `lisensi-plugin-auto-caption-video-pendek` |
| Bundle 500+ Sound Effect & Musik Bebas Royalti | `bundle-500-sound-effect-musik-bebas-royalti` |

Nama file adalah *slug* produk: nama produk dalam huruf kecil, spasi dan tanda baca diganti `-`.
Kalau nama produk di `DatabaseSeeder` diubah, nama filenya ikut berubah.
