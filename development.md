# Deploy Development

Cocok untuk coding lokal. Perubahan source code langsung terlihat tanpa rebuild
karena folder proyek di-bind mount ke container.

## Langkah deploy

```bash
cp .env.example .env
# sesuaikan .env (lihat nilai di bawah)
php artisan key:generate

docker compose -f compose.dev.yaml up -d --build
docker compose -f compose.dev.yaml exec php-fpm php artisan migrate --seed
```

Jalankan Vite **di host** agar `public/hot` dan HMR sinkron dengan container:

```bash
npm install
npm run dev -- --port 5174
```

- Aplikasi: **http://localhost:8085** (`NGINX_PORT`)
- Vite HMR: **http://localhost:5174** (`VITE_PORT`)

## Nilai `.env` untuk development

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8085
NGINX_PORT=8085
VITE_PORT=5174
XDEBUG_ENABLED=true       # false agar lebih cepat
DEBUGBAR_ENABLED=true     # false untuk mematikan debugbar
INERTIA_DEVTOOLS_ENABLED=true
```

## Mengubah port

Jika `8085` atau `5174` sudah dipakai proyek lain:

```env
APP_URL=http://localhost:8086
NGINX_PORT=8086
VITE_PORT=5175
```

Sesuaikan juga port pada perintah `npm run dev -- --port 5175`.

## Update

```bash
git pull
# jika dependency berubah:
docker compose -f compose.dev.yaml exec php-fpm composer install
npm install
# jika Dockerfile berubah:
docker compose -f compose.dev.yaml up -d --build
```

## Mematikan Xdebug

```env
XDEBUG_ENABLED=false
XDEBUG_MODE=off
```

```bash
docker compose -f compose.dev.yaml up -d --build --force-recreate
```
