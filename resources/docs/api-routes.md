---
title: Referensi Route
group: Referensi
order: 1
---

## Dokumentasi

Route publik, dapat diakses tanpa login.

| Method | URI | Nama | Keterangan |
|---|---|---|---|
| GET | `/` | `home` | Halaman login |
| GET | `/documentation` | `documentation.index` | Halaman pertama dokumentasi |
| GET | `/documentation/{slug}` | `documentation.show` | Halaman dokumentasi tertentu |

## Admin

Semua route di bawah memakai middleware `auth` dan `role:admin`.

| Method | URI | Nama |
|---|---|---|
| GET | `/dashboard` | `dashboard` |
| GET | `/users/import/template` | `users.import.template` |
| GET | `/users/import` | `users.import` |
| POST | `/users/import/preview` | `users.import.preview` |
| POST | `/users/import` | `users.import.store` |
| POST | `/users/cards/print` | `users.cards.print` |
| PATCH | `/users/reset-kode-kosong` | `users.reset-kode-kosong` |
| GET | `/users` | `users.index` |
| GET | `/users/create` | `users.create` |
| POST | `/users` | `users.store` |
| GET | `/users/{user}` | `users.show` |
| GET | `/users/{user}/edit` | `users.edit` |
| PUT/PATCH | `/users/{user}` | `users.update` |
| DELETE | `/users/{user}` | `users.destroy` |
| PATCH | `/users/{user}/reset-kode` | `users.reset-kode` |
| GET | `/candidates` | `candidates.index` |
| POST | `/candidates` | `candidates.store` |
| PUT | `/candidates/{candidate}` | `candidates.update` |
| DELETE | `/candidates/{candidate}` | `candidates.destroy` |
| POST | `/candidates/reorder` | `candidates.reorder` |
| GET | `/hasil` | `hasil` |
| GET | `/reset-pemilih` | `reset.index` |
| POST | `/reset-pemilih` | `reset.execute` |

Route `users/reset-kode-kosong` dan `candidates/reorder` dideklarasikan sebelum
resource route agar tidak tertangkap sebagai `users/{user}` atau
`candidates/{candidate}`.

## Kandidat

| Method | URI | Nama | Middleware |
|---|---|---|---|
| GET | `/kandidat` | `kandidat` | `auth`, `role:kandidat` |

## Pemilih

| Method | URI | Nama | Middleware |
|---|---|---|---|
| GET | `/pemilih` | `pemilih` | `auth`, `role:pemilih` |
| POST | `/pemilih/vote` | `pemilih.vote` | `auth`, `role:pemilih` |

## Autentikasi

Route di bawah datang dari **Laravel Fortify** dan Laravel framework, bukan
didefinisikan manual di `routes/`.

| Method | URI | Nama |
|---|---|---|
| POST | `/login` | `login.store` |
| POST | `/logout` | `logout` |
| POST | `/user/confirm-password` | `password.confirm.store` |
| GET | `/user/confirmed-password-status` | `password.confirmation` |
| GET | `/settings/appearance` | `appearance.edit` |
| GET | `/up` | — |

## Pengaturan Akun

Semua memakai middleware `auth`. Route `/settings/admins` hanya untuk admin.

| Method | URI | Nama |
|---|---|---|
| GET | `/settings/profile` | `profile.edit` |
| PATCH | `/settings/profile` | `profile.update` |
| DELETE | `/settings/profile` | `profile.destroy` |
| GET | `/settings/security` | `security.edit` |
| PATCH | `/settings/kode` | `user-kode.update` |
| GET | `/settings/admins` | `settings.admins.index` |
| POST | `/settings/admins` | `settings.admins.store` |
| PUT | `/settings/admins/{admin}` | `settings.admins.update` |
| DELETE | `/settings/admins/{admin}` | `settings.admins.destroy` |

## Memeriksa Route

```bash
php artisan route:list
php artisan route:list --name=documentation
php artisan route:list --except-vendor
```

## Catatan Middleware

| Alias | Class | Fungsi |
|---|---|---|
| `guest` | — | Memblokir user yang sudah login |
| `auth` | — | Memaksa login |
| `role` | `App\Http\Middleware\EnsureRole` | Membatasi akses per role |

Semua request tetap melewati middleware group `web`, termasuk
`HandleInertiaRequests` yang mengirim `auth.user` ke seluruh halaman.