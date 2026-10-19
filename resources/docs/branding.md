---
title: Branding
group: Fitur
order: 6
---

Halaman **Settings → Branding** (khusus **admin**) untuk menyesuaikan identitas
aplikasi: nama, tagline, logo, dan favicon.

Akses: `/settings/branding` (middleware `auth` + `role:admin`).

## Yang Dapat Disesuaikan

| Field | Dipakai di |
|---|---|
| Nama aplikasi | Sidebar, judul tab, halaman login, ekspor Excel/PDF, kartu siswa |
| Tagline | Halaman login |
| Logo | Sidebar, header, halaman login |
| Logo kedua (mis. OSIS) | Halaman login, di samping logo utama |
| Favicon | Ikon tab browser |
| Warna utama & gradasi | Gradasi halaman login |

Logo dan favicon bisa diunggah (file) **atau** ditempel sebagai URL.

## Alur

1. Admin membuka **Settings → Branding**.
2. Isi nama, tagline, serta logo/favicon (unggah **atau** URL).
3. Klik **Simpan** — berkas diunggah ke `public/uploads/branding/`, nilai
   disimpan pada tabel `settings`, dan cache dibersihkan.
4. Provider (`BrandingServiceProvider`) menerapkan nilai ke
   `config('app.name')` sehingga sidebar, judul tab, ekspor, kartu siswa, dan
   prop `branding` (Inertia) otomatis ikut berubah.
5. Tombol **Reset ke Default** menghapus semua pengaturan branding.

## Penyimpanan & Format

- Pengaturan: tabel `settings` (key/value), dibaca via `App\Support\Branding`
  dan di-cache (`rememberForever`).
- Berkas: `public/uploads/branding/` (juga muncul di halaman **Storage**).
- Logo: PNG/JPG/SVG/WebP, maksimal 2 MB.
- Logo kedua (opsional, mis. logo OSIS): PNG/JPG/SVG/WebP, maksimal 2 MB.
  Ditampilkan berpasangan dengan logo utama di halaman login.
- Favicon: PNG/JPG/SVG/ICO/WebP, maksimal 1 MB.
- Mengunggah berkas akan menggantikan URL (dan menghapus berkas lama).
- Warna: dua nilai heksadesimal (`brand_color` dan `brand_color_alt`) dalam
  format `#rrggbb` atau `#rgb`. Tersedia preset cepat dan pratinjau gradasi.
  Bila kosong, dipakai default `#4f46e5` → `#9333ea`. Warna hanya dipakai pada
  gradasi halaman login.

## Catatan Teknis

- `GET /settings/branding` — halaman `settings/Branding`
- `POST /settings/branding` — simpan branding (`app_name`, `tagline`, `logo`,
  `logo_url`, `secondary_logo`, `secondary_logo_url`, `favicon`, `favicon_url`,
  `brand_color`, `brand_color_alt`)
- `POST /settings/branding/reset` — kembalikan ke default
- Nilai kosong pada `app_name` tidak diizinkan; `tagline` boleh dikosongkan.
