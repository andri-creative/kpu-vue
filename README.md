# KPU — Kotak Pick-Up Election

Aplikasi pemilihan umum sekolah (KPU) dengan tiga role dalam satu aplikasi:

- **Admin** — mengelola user, kandidat, melihat hasil voting, dan mereset voting
- **Pemilih** — memberikan satu kali suara pada kandidat pilihan
- **Kandidat** — melihat profil, visi, dan misi miliknya

Dibangun dengan **Laravel 13**, **Inertia.js v3**, **Vue 3**, **TypeScript**, **Tailwind CSS**, dan **shadcn-vue**.

## Dokumentasi

Dokumentasi **web** (yang tampil di halaman `/documentation`) berada di folder
[`resources/docs/`](resources/docs) dan dibaca langsung dari sana oleh aplikasi.
Panduan deploy sengaja diletakkan di root project agar mudah dibaca di GitHub
(lihat bagian [Deployment](#deployment)).

| Halaman              | Grup            | File                                                          |
| -------------------- | --------------- | ------------------------------------------------------------- |
| Overview KPU         | Getting Started | [`readme.md`](resources/docs/readme.md)                       |
| Autentikasi dan Role | Getting Started | [`authentication.md`](resources/docs/authentication.md)       |
| Manajemen User       | Fitur           | [`users-management.md`](resources/docs/users-management.md)   |
| Alur Voting          | Fitur           | [`voting-flow.md`](resources/docs/voting-flow.md)             |
| Pengaturan Database  | Fitur           | [`database-settings.md`](resources/docs/database-settings.md) |
| Terminal Log         | Fitur           | [`log-viewer.md`](resources/docs/log-viewer.md)               |
| Storage File         | Fitur           | [`files.md`](resources/docs/files.md)                         |
| Branding             | Fitur           | [`branding.md`](resources/docs/branding.md)                   |
| Skema Database       | Database        | [`database-schema.md`](resources/docs/database-schema.md)     |
| Contoh Query SQL     | Database        | [`database-sql.md`](resources/docs/database-sql.md)           |
| Referensi Route      | Referensi       | [`api-routes.md`](resources/docs/api-routes.md)               |

Semua file memakai **front matter** sederhana:

```markdown
---
title: Overview KPU
group: Getting Started
order: 1
---
```

### Cara aplikasi membaca `resources/docs`

Aplikasi membaca seluruh `*.md` secara otomatis lewat `App\Support\Documentation`
(tidak ada file yang perlu dikeluarkan dari folder docs):

```php
// app/Support/Documentation.php
$files = glob(resource_path('docs').'/*.md') ?: [];
```

Halaman dokumentasi lalu tersedia di route publik:

- `GET /documentation` — daftar halaman
- `GET /documentation/{slug}` — detail halaman (contoh: `/documentation/voting-flow`)

Untuk menambah halaman baru, cukup buat file `.md` baru di `resources/docs/` dengan
front matter di atas. Halaman langsung muncul di sidebar tanpa mengubah kode.

> Catatan: Markdown tidak punya fitur _include_ bawaan. Agar isi file di
> `resources/docs/` ikut tampil di README ini tanpa dipindahkan, gunakan link
> relatif di tabel atas (didukung GitHub dan editor Markdown). Jika ingin benar-benar
> menyalin isinya ke satu file, jalankan perintah berikut saat butuh:

```bash
# Baca semua dokumentasi tanpa memindahkan filenya
for f in resources/docs/*.md; do printf '\n\n<!-- %s -->\n\n' "$f"; cat "$f"; done
```

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

## Menjalankan Aplikasi (Development)

```bash
php artisan serve --port=8085
npm run dev
```

Akses `http://localhost:8085` sesuai `APP_URL` di `.env`.

### Kode login development

| Role         | NIS                       | Kode              |
| ------------ | ------------------------- | ----------------- |
| Admin        | `0000000001`              | `000000`          |
| Kandidat 1   | `1000000001`              | `111111`          |
| Kandidat 2   | `1000000002`              | `222222`          |
| Kandidat 3   | `1000000003`              | `333333`          |
| Pemilih 1–10 | `2000000000`–`2000000009` | `100001`–`100010` |

## Deployment

Panduan deploy dipisah per topik dan berada **di root project** (untuk GitHub).
Folder `resources/docs/` sengaja hanya dipakai untuk dokumentasi yang tampil di
halaman web `/documentation` aplikasi.

| Topik                                                                                          | File                               |
| ---------------------------------------------------------------------------------------------- | ---------------------------------- |
| Ringkasan deploy Docker: prasyarat, perbandingan dev vs prod, update, cleanup, catatan penting | [`DOCKER.md`](DOCKER.md)           |
| Cara deploy development (`compose.dev.yaml`)                                                   | [`development.md`](development.md) |
| Cara deploy production (`compose.prod.yaml`) + daftar variabel yang diganti                    | [`production.md`](production.md)   |

Port default:

- Aplikasi: **http://localhost:8085** (`NGINX_PORT`)
- Vite dev: **http://localhost:5174** (`VITE_PORT`)

## Menonaktifkan Debugging

Debugging di proyek ini terdiri dari beberapa lapis. Matikan semuanya untuk produksi,
atau satu per satu sesuai kebutuhan.

### 1. Debug mode Laravel

Ini yang mengatur halaman error detail dan perilaku debug bawaan Laravel. Set di `.env`:

```env
APP_ENV=production
APP_DEBUG=false
```

Lalu bersihkan/bangun ulang cache konfigurasi:

```bash
php artisan config:clear   # saat mengubah .env dan tidak memakai config cache
php artisan config:cache   # untuk produksi (setelah semua nilai .env final)
```

Saat `APP_DEBUG=false`, halaman error detail (Ignition) tidak lagi ditampilkan ke
pengunjung. Error tetap dicatat ke `storage/logs/laravel.log`.

### 2. Laravel Debugbar

Debugbar (`barryvdh/laravel-debugbar`) adalah dependency `require-dev`, sehingga tidak
ikut terpasang di image produksi karena Dockerfile memakai
`composer install --no-dev`.

Untuk mematikannya saat development, set di `.env`:

```env
DEBUGBAR_ENABLED=false
```

Alternatif permanen: ubah `'enabled' => env('DEBUGBAR_ENABLED', null)` menjadi
`'enabled' => false` di `config/debugbar.php`, lalu jalankan `php artisan config:clear`.

### 3. Inertia DevTools

Recorder sisi server aktif otomatis hanya di environment `local`. Matikan eksplisit:

```env
INERTIA_DEVTOOLS_ENABLED=false
```

Hooks sisi client mengikuti `import.meta.env.DEV`. Untuk mematikan paksa, tambahkan
`dev: false` pada `createInertiaApp()` di [`resources/js/app.ts`](resources/js/app.ts):

```ts
void createInertiaApp({
    dev: false,
    // ...
});
```

Hapus rekaman lama bila perlu:

```bash
rm -rf storage/inertia-devtools/*
```

### 4. Xdebug (Docker)

Xdebug hanya ada di image **development** dan tidak disertakan di image produksi.
Untuk mematikannya, set di `.env` lalu build ulang:

```env
XDEBUG_ENABLED=false
XDEBUG_MODE=off
```

```bash
docker compose -f compose.dev.yaml up -d --build --force-recreate
```

### Memastikan debugging sudah mati

```bash
php artisan about
php artisan config:show app.debug
curl -I http://localhost:8085
```

`app.debug` harus bernilai `false`, dan respons error tidak boleh menampilkan stack
trace. Untuk build produksi gunakan selalu `compose.prod.yaml`.

## Pengujian

```bash
php artisan test --compact
vendor/bin/pest
```

## Format Kode

```bash
vendor/bin/pint
npm run check
npm run types:check
```
