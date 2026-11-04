# Panduan Kolaborasi

## Branch
- `main`    : kode stabil / siap demo. **Jangan push langsung.**
- `develop` : tempat menggabungkan fitur.
- `feature/<nama-fitur>` : satu branch per fitur, dibuat dari `develop`.
- `fix/<nama-bug>` : perbaikan bug.

## Alur Kerja Harian
```bash
git checkout develop
git pull origin develop               # ambil update terbaru
git checkout -b feature/login         # buat branch fitur
# ... coding ...
git add .
git commit -m "feat: tambah halaman login"
git push -u origin feature/login
```
Lalu buka **Pull Request** di GitHub: `feature/login` -> `develop`, minta 1 teman review, baru merge.

## Format Commit
- `feat:` fitur baru
- `fix:` perbaikan bug
- `docs:` dokumentasi
- `style:` tampilan/format
- `refactor:` rapikan kode tanpa ubah fungsi

## Aturan
1. Selalu `git pull` sebelum mulai kerja.
2. Jangan commit file `.env`, password, atau `node_modules/` / `vendor/`.
3. Jika conflict, selesaikan di branch sendiri, lalu push ulang.
