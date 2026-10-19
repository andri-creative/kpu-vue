# Deployment Docker

Panduan deploy dipisah sesuai kebutuhan:

- [Deploy Development](development.md) — `compose.dev.yaml`
- [Deploy Production](production.md) — `compose.prod.yaml`

## Prasyarat

- Docker Engine + Docker Compose v2 (`docker compose version`)
- Port host `8085` (aplikasi) dan `5174` (Vite, khusus dev) tidak dipakai proyek lain
- File `.env` hasil salinan `.env.example`
- Untuk production: domain/IP server dan (jika ada) database eksternal

## Perbedaan Development vs Production

| Aspek                   | Development (`compose.dev.yaml`)     | Production (`compose.prod.yaml`)             |
| ----------------------- | ------------------------------------ | -------------------------------------------- |
| Nama service            | `web`, `php-fpm`, `workspace`        | `web`, `php-fpm`                             |
| Source code             | bind mount `./:/var/www` (live edit) | di-copy ke dalam image                       |
| Asset frontend          | Vite dev server (HMR)                | di-build saat image dibuat (`npm run build`) |
| Composer                | dependency dev ikut                  | `--no-dev --optimize-autoloader`             |
| Xdebug                  | aktif (bisa dimatikan)               | tidak terpasang                              |
| `APP_ENV` / `APP_DEBUG` | `local` / `true`                     | `production` / `false`                       |
| Config & route cache    | `clear` tiap start                   | `config:cache` + `route:cache`               |
| Migrasi                 | manual                               | otomatis saat container start                |
| Restart policy          | default                              | `unless-stopped`                             |
| Port aplikasi           | `8085` (`NGINX_PORT`)                | `8085` (`NGINX_PORT`)                        |
| Port Vite               | `5174` (`VITE_PORT`)                 | tidak dipakai                                |
| Storage                 | folder proyek                        | named volume `laravel-storage-production`    |

## Update / deploy ulang

**Development** (source code otomatis karena bind mount):

```bash
git pull
# jika dependency berubah:
docker compose -f compose.dev.yaml exec php-fpm composer install
npm install
# jika Dockerfile berubah:
docker compose -f compose.dev.yaml up -d --build
```

**Production** (wajib build ulang karena kode ada di dalam image):

```bash
git pull
docker compose -f compose.prod.yaml up -d --build
```

Migrasi berjalan otomatis lewat entrypoint. Cek status dengan
`docker compose -f compose.prod.yaml ps`.

## Menghentikan dan membersihkan

```bash
# Hentikan container (data di named volume tetap aman)
docker compose -f compose.prod.yaml down

# Hentikan + hapus volume (DATA STORAGE PRODUCTION IKUT TERHAPUS)
docker compose -f compose.prod.yaml down -v
```

## Catatan penting

- Perubahan `.env` tidak butuh rebuild, cukup `docker compose ... up -d`
  (entrypoint production akan meng-cache ulang konfigurasi).
- Perubahan `Dockerfile`, `compose.*.yaml`, atau kode di production butuh
  `up -d --build`.
- Build arg seperti `XDEBUG_ENABLED` hanya berlaku saat build, jadi perlu
  `up -d --build --force-recreate` setelah diubah.
- **Database:** `.env` saat ini memakai SQLite. Di production, file
  `database/database.sqlite` berada di dalam container dan bisa hilang saat
  container dibuat ulang. Gunakan database eksternal (MySQL/PostgreSQL), atau
  tambahkan volume permanen untuk direktori `database/`.
- Jangan pernah commit `.env`; file ini sudah masuk `.gitignore`.
- Ganti `NGINX_PORT` **dan** sesuaikan `APP_URL` bila port `8085` bentrok dengan
  proyek lain di Docker.
