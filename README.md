# ifs24018 - PABWE 2026 Praktikum 7 Studi Kasus: Aplikasi Stock Barang (Laravel 13 & Livewire)

Aplikasi inventaris stok barang dengan **Feature-First / Clean Architecture**, dibangun dengan **Laravel 13**, **Livewire 4**, **MySQL**, **PhpOffice/PhpSpreadsheet**, dan integrasi **OpenAI**. Pola mengikuti latihan Todolist (domain Todo diganti menjadi Stock).

## Fitur

| Peran | Kemampuan |
|-------|-----------|
| Guest | Login & register; `/` mengarah ke login |
| Member | Mengelola stok miliknya sendiri saja |
| Admin | Melihat daftar user + jumlah stok, dan stok tiap user secara **read-only** |

Fitur member: daftar stok (cari nama/SKU/deskripsi, filter status, pagination), CRUD via modal (name, sku, deskripsi Markdown, quantity, unit, price, min_stock, status), adjust quantity (+/−) dari list & detail dengan status otomatis (`available` / `low_stock` / `out_of_stock`), detail stok dengan cover image dan Markdown (Toast UI), import/export Excel, generate item stok dengan AI (preview → pilih → simpan), dan profil (data, password, foto).

Keamanan: middleware `role` + `owns.stock`, `StockPolicy`, rate limit `1000-per-second`, `FORCE_HTTPS`. Form login memakai `id="login-username"`, `id="login-password"`, `id="login-submit"`.

## Rute

| Method | URI | Keterangan |
|--------|-----|------------|
| GET | `/` | Guest → login; admin → users; member → stocks |
| GET | `/login`, `/register` | Guest |
| POST | `/logout` | Auth |
| GET | `/stocks` | Daftar stok (member, `owns.stock`) |
| GET | `/stocks/{stock}` | Detail stok |
| GET | `/profile` | Profil |
| GET | `/admin/users` | Admin |
| GET | `/admin/users/{user}/stocks` | Admin, read-only |

## Instalasi

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pabwe_p7_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install --ignore-scripts && npm run build
php artisan serve
```

Akun seeder: `admin` / `admin` dan `member` / `member` (member punya 3 contoh stok).

Isi `OPENAI_ENDPOINT`, `OPENAI_KEY`, `OPENAI_MODEL` di `.env` untuk fitur AI (jangan commit `.env`).

## Pengujian

```bash
composer test    # PHPUnit + coverage (butuh Xdebug/PCOV)
composer audit
```

Target: coverage `app/` 100% (Classes/Methods/Lines). Artefak uji (`coverage.*`, `junit.xml`, `phpunit_*.txt`, dll.) sudah diabaikan di `.gitignore`. `Jenkinsfile` menjalankan audit (gagal bila ada kerentanan), build, PHPUnit dengan gate coverage 100%, dan Trivy.

## Excel

Kolom: `No | Name | SKU | Quantity | Unit | Price | Min Stock | Status | Description | Created At` (import membaca kolom A–I; status kosong/tidak valid diturunkan dari quantity vs min stock).
