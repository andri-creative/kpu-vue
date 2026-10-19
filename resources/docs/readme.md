---
title: Overview KPU
group: Getting Started
order: 1
---

## Tentang Aplikasi KPU

Kotak Pick-Up Election (KPU) adalah aplikasi pemilihan umum sekolah yang
menggabungkan tiga role dalam satu aplikasi:

- **Admin** — mengelola user, kandidat, melihat hasil voting, dan mereset voting
- **Pemilih** — memberikan satu kali suara pada kandidat pilihan
- **Kandidat** — melihat profil, visi, dan misi miliknya

Aplikasi ini dibangun dengan **Laravel 13**, **Inertia.js v3**, **Vue 3**,
**TypeScript**, dan **shadcn-vue**.

## Fitur Utama

| Fitur | Keterangan |
|---|---|
| Login kode 6 digit | Tanpa password, memakai kode unik per user |
| Manajemen user | Tambah, ubah role, hapus, import dari Excel |
| Manajemen kandidat | CRUD kandidat, upload foto, visi misi, urutan nomor |
| Voting | Satu pemilih hanya bisa satu suara |
| Hasil voting | Grafik per kandidat dan rekap hasil |
| Arsip dan reset | Data voting diarsipkan per tahun sebelum dihapus |
| Pengaturan database | Statistik kapasitas, optimasi VACUUM, dan reset data per tabel |
| Terminal log | Pantau log real-time + pembersihan storage (log, pail, debugbar, cache) |
| Storage file | Galeri berkas `public/uploads` (gambar, PDF, dokumen, Excel) dengan preview semua jenis & hapus |
| Branding | Atur nama, tagline, logo, dan favicon aplikasi |

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat file database SQLite:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

## Menjalankan Aplikasi

```bash
php artisan serve
npm run dev
```

Untuk production:

```bash
npm run build
php artisan serve
```

## Kode Login Development

Seeder membuat user contoh dengan kode yang bisa dipakai langsung:

| Role | NIS | Kode |
|---|---|---|
| Admin | `0000000001` | `000000` |
| Kandidat 1 | `1000000001` | `111111` |
| Kandidat 2 | `1000000002` | `222222` |
| Kandidat 3 | `1000000003` | `333333` |
| Pemilih 1–10 | `2000000000`–`2000000009` | `100001`–`100010` |

Seeder bersifat idempotent, jadi `php artisan db:seed` bisa dijalankan berkali-kali
tanpa menimpa perubahan yang sudah dilakukan lewat UI.

## Menjalankan Uji

```bash
php artisan test
vendor/bin/pest
```