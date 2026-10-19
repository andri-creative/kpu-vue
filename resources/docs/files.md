---
title: Storage File
group: Fitur
order: 5
---

Halaman **Settings → Storage** menampilkan semua berkas di folder
`public/uploads` — gambar, PDF, dokumen, Excel, arsip, dan lainnya — dalam satu
galeri. Hanya **admin** yang dapat membuka halaman ini.

Akses: `/settings/files` (middleware `auth` + `role:admin`).

## Sumber Berkas

Semua berkas dibaca hanya dari `public/uploads` (rekursif). Subfolder di
dalamnya otomatis menjadi "sumber" (filter):

| Subfolder | Keterangan |
|---|---|
| `public/uploads/candidates` | Foto kandidat yang diunggah lewat aplikasi |
| `public/uploads/imports` | File Excel yang diunggah saat import data siswa |
| `public/uploads/files` | Berkas umum (bila ada) |

Berkas yang berada langsung di dalam `public/uploads` masuk ke sumber
**Lainnya**.

## Ringkasan

Empat kartu di bagian atas menampilkan total berkas, total ukuran, jumlah
gambar, serta jumlah PDF & dokumen.

## Menjelajah Berkas

- **Pencarian** berdasarkan nama berkas (otomatis, jeda 400 ms).
- **Filter Sumber** untuk membatasi ke satu subfolder.
- **Filter Tipe**: Gambar, PDF, Dokumen, Arsip, atau Lainnya.
- Kartu berkas menampilkan thumbnail (khusus gambar), nama, ukuran, tipe,
  sumber, dan waktu diubah.

## Aksi

| Aksi | Keterangan |
|---|---|
| Preview | Membuka dialog pratinjau untuk **semua jenis berkas** |
| Salin link | Menyalin URL berkas ke clipboard |
| Buka di Tab Baru | Membuka pratinjau pada tab baru |
| Hapus | Menghapus berkas permanen setelah konfirmasi |

Halaman ini **tidak menyediakan unggah berkas**; hanya menampilkan dan
menghapus berkas yang sudah tersimpan.

### Pratinjau Semua Jenis

Dialog pratinjau selalu menampilkan isi berkas tanpa memaksa unduh:

- **Gambar & PDF** — ditampilkan langsung.
- **Video & Audio** — diputar dengan pemutar bawaan browser.
- **Spreadsheet** (`.xlsx`, `.xls`, `.csv`, `.xlsm`, `.ods`) — dirender menjadi
  tabel HTML (dibatasi 200 baris × 40 kolom).
- **Word `.docx`** — teks dokumen diekstrak dan ditampilkan.
- **Teks** (`.txt`, `.md`, `.json`, `.log`, `.xml`, `.yml`, dll) — ditampilkan
  apa adanya.
- **`.doc`, `.rtf`, `.ppt`, `.pptx`, `.odt`** — ditampilkan sebagai teks
  (best-effort).
- Jenis lain — ditampilkan pesan + tautan buka/unduh.

## Catatan Teknis

- `GET /settings/files` — halaman `settings/Files` (`collection`, `search`,
  `type`)
- `GET /settings/files/preview` — pratinjau satu berkas (`path`), inline
  (`App\Support\FilePreview`)
- `DELETE /settings/files` — menghapus berkas (`collection`, `path`)
- Penghapusan hanya diizinkan untuk berkas yang berada di dalam `public/uploads`
  (dan cocok dengan sumbernya); path di luar itu ditolak.
- Preview hanya melayani berkas di dalam `public/uploads`; path di luar itu
  mengembalikan 404.
- File Excel import disimpan otomatis ke `public/uploads/imports` melalui
  `App\Support\UploadStore` saat import berhasil.
- Belum ada tabel metadata berkas — daftar dibaca langsung dari filesystem
  (`App\Support\FileScanner`).
