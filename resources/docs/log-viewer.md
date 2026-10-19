---
title: Terminal Log
group: Fitur
order: 4
---

Halaman **Settings → Logs** menampilkan isi file log aplikasi secara
real-time seperti perintah `tail -f` di terminal. Halaman ini **read-only**
(hanya untuk membaca) dan hanya bisa dibuka oleh **admin**.

Akses: `/settings/logs` (middleware `auth` + `role:admin`).

## Sumber Log

Setiap sumber dipetakan ke satu file log pada `config/log_viewer.php`:

| Sumber | File |
|---|---|
| Laravel | `storage/logs/laravel.log` |
| Browser | `storage/logs/browser.log` |

Ganti sumber dengan menekan tombol tab di atas terminal. Label `(kosong)`
menandakan file belum ada.

## Panel Terminal

- Menampilkan nomor baris di sisi kiri.
- Pewarnaan baris berdasarkan level log:
  - Merah — `ERROR`, `CRITICAL`, `ALERT`, `EMERGENCY`
  - Kuning — `WARNING`
  - Biru — `INFO`
  - Abu — `DEBUG`
  - Putih — lainnya
- **Auto-scroll** mengikuti baris terbaru. Menggulir ke atas otomatis
  mematikan auto-scroll sampai kembali ke dasar.
- Bilah atas menampilkan path file, jumlah baris, ukuran file, dan status
  koneksi (`tersambung` / `terputus`).

## Kontrol

| Tombol | Fungsi |
|---|---|
| Mulai / Jeda | Memulai atau menghentikan streaming |
| Bersihkan | Mengosongkan tampilan terminal (file tidak diubah) |
| Reset Log | Mengosongkan isi file log secara permanen |

**Reset Log** wajib dikonfirmasi melalui dialog, karena file log akan
dikosongkan permanen dan tidak bisa dibatalkan.

## Cara Kerja

Streaming memakai **polling request pendek**, bukan SSE (Server-Sent Events).
Alasannya: `php artisan serve` berjalan dengan satu worker, sehingga koneksi
yang ditahan lama (SSE) akan memblokir request lain dan membuat situs lambat.

- Frontend memanggil endpoint `tail` setiap `refresh_interval_ms`.
- Cursor disimpan di sisi klien; server hanya mengirim baris yang baru.
- Saat awal muat, file direset, atau dirotasi, server mengirim ulang backlog
  sejumlah `tail_lines` baris terakhir.
- Frontend menyimpan maksimal **1000 baris** di memori untuk menjaga performa.

## Konfigurasi

Nilai dapat diubah lewat `.env`:

```bash
LOG_VIEWER_TAIL=300      # jumlah baris backlog saat awal muat
LOG_VIEWER_REFRESH=1500  # interval polling dalam milidetik
```

Atau langsung pada `config/log_viewer.php`.

## Pembersihan Storage

Di bawah terminal terdapat kartu **Pembersihan Storage** untuk menghapus file
log dan cache yang menumpuk agar web tetap ringan dan storage tidak penuh:

| Kategori | Isi |
|---|---|
| Log Aplikasi | `storage/logs/*.log` |
| Cache Pail | `storage/pail/*.pail` |
| Debugbar | `storage/debugbar/*.json` |
| Inertia Devtools | `storage/inertia-devtools/*.json` |
| Cache Framework | `storage/framework/views` dan `storage/framework/cache` |

- Setiap kategori menampilkan ukuran dan jumlah file, lengkap dengan tombol
  **Bersihkan** sendiri-sendiri.
- Tombol **Bersihkan Semua** menghapus seluruh kategori sekaligus.
- Upload kandidat di `storage/app` **tidak pernah** disentuh.
- Cache framework dibuat ulang otomatis sehingga aman dibersihkan.

## Catatan Teknis

- `GET /settings/logs` — halaman `settings/Logs`
- `GET /settings/logs/tail` — endpoint JSON (parameter `source`, `cursor`)
- `POST /settings/logs/reset` — mengosongkan file log (`source`)
- `POST /settings/storage/clean` — membersihkan satu kategori (`category`)
- `POST /settings/storage/clean-all` — membersihkan seluruh kategori
- Path file selalu diambil dari konfigurasi, tidak pernah dari input pengguna,
  sehingga admin tidak dapat membaca file sembarangan.

Respons endpoint `tail` berisi: `exists`, `reset`, `lines`, `cursor`, `size`.
