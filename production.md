# Deploy Production

Image production bersifat self-contained: source code, dependency Composer
(`--no-dev`), dan asset hasil build sudah ada di dalam image.

## Langkah deploy

```bash
# 1. Ambil kode di server
git clone <repo-url> kpu && cd kpu

# 2. Siapkan environment
cp .env.example .env
# edit .env dengan nilai produksi (tabel di bawah)

# 3. Isi APP_KEY (pilih salah satu)
php artisan key:generate                    # jika PHP + vendor ada di host
echo "base64:$(openssl rand -base64 32)"    # jika tidak, tempel hasilnya ke APP_KEY=

# 4. Build & jalankan
docker compose -f compose.prod.yaml up -d --build

# 5. Verifikasi
docker compose -f compose.prod.yaml ps
docker compose -f compose.prod.yaml logs -f php-fpm web
```

Akses aplikasi di **http://domain-atau-ip:8085** (atau `:80` jika
`NGINX_PORT=80`). Entrypoint production otomatis menjalankan migrasi,
`config:cache`, dan `route:cache` setiap container start.

## Nilai `.env` untuk production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com
NGINX_PORT=8085
LOG_LEVEL=error
DEBUGBAR_ENABLED=false
INERTIA_DEVTOOLS_ENABLED=false
```

## Yang wajib diganti dari development ke production

| Variabel | Development | Production | Keterangan |
|---|---|---|---|
| `APP_ENV` | `local` | `production` | Mengaktifkan perilaku produksi Laravel |
| `APP_DEBUG` | `true` | `false` | Wajib; jika `true`, detail error bocor ke pengunjung |
| `APP_KEY` | hasil `key:generate` | hasil `key:generate` | Jangan diubah setelah ada data terenkripsi |
| `APP_URL` | `http://localhost:8085` | `https://domain-anda.com` | Dipakai untuk URL absolut di CLI/email |
| `NGINX_PORT` | `8085` | `8085` / `80` | Port host; ganti jika bentrok |
| `VITE_PORT` | `5174` | tidak dipakai | Hanya untuk dev server |
| `LOG_LEVEL` | `debug` | `error` | Mengurangi ukuran log |
| `DB_CONNECTION` | `sqlite` | `mysql` / `pgsql` / `sqlite` | Lihat catatan database |
| `DB_HOST` dll. | opsional | sesuai server DB | Wajib jika memakai DB eksternal |
| `CACHE_STORE` | `array` | `file` / `database` / `redis` | `array` tidak persist antar request |
| `SESSION_DRIVER` | `database` | `database` / `file` | Pastikan tabel session sudah dimigrasi |
| `QUEUE_CONNECTION` | `sync` | `database` / `redis` | Ganti jika memakai queue worker |
| `FILESYSTEM_DISK` | `local` | `local` / `public` / `s3` | Sesuaikan kebutuhan upload |
| `MAIL_MAILER` | `log` | `smtp` | Agar email benar-benar terkirim |
| `XDEBUG_ENABLED` | `true` | `false` | Xdebug tidak ada di image production |
| `DEBUGBAR_ENABLED` | `true` | `false` | Debugbar hanya dependency dev |
| `INERTIA_DEVTOOLS_ENABLED` | `true` | `false` | Recorder DevTools dimatikan |

## Update / deploy ulang

```bash
git pull
docker compose -f compose.prod.yaml up -d --build
```

## Catatan penting

- Perubahan `.env` tidak butuh rebuild, cukup `docker compose ... up -d`.
- Perubahan `Dockerfile`, `compose.*.yaml`, atau kode butuh `up -d --build`.
- **Database:** `.env` saat ini memakai SQLite. File `database/database.sqlite`
  berada di dalam container dan bisa hilang saat container dibuat ulang.
  Gunakan database eksternal (MySQL/PostgreSQL), atau tambahkan volume permanen
  untuk direktori `database/`.
- Data storage production disimpan di named volume `laravel-storage-production`.
  `docker compose ... down -v` akan menghapusnya.
