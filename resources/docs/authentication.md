---
title: Autentikasi dan Role
group: Getting Started
order: 2
---

## Mekanisme Login

Aplikasi KPU **tidak memakai password** untuk login. Setiap user memiliki kode
numerik 6 digit yang bersifat unik dan tersimpan di kolom `users.kode`.

Login dilakukan di halaman `/` melalui form yang hanya berisi satu input:

1. User memasukkan kode 6 digit
2. Form divalidasi pada server dengan aturan `digits:6`
3. Server mencari user dengan `User::verifyKode()` yang memakai `hash_equals()`
   agar perbandingan tahan timing attack
4. Jika cocok, user diarahkan ke halaman sesuai role-nya

Kolom `users.password` tetap ada dan selalu terisi, tetapi **tidak dipakai
untuk autentikasi**. Isinya hanya nilai acak agar konsisten dengan schema.

## Role

Role disimpan di kolom `users.role` dengan nilai:

| Role | Akses |
|---|---|
| `admin` | Dashboard, manajemen user, kandidat, hasil voting, reset voting |
| `kandidat` | Halaman profil kandidat, termasuk mengubah visi dan misi sendiri |
| `pemilih` | Halaman voting |

Pembatasan akses memakai middleware `role` yang di-*alias* ke
`App\Http\Middleware\EnsureRole`. User yang salah role diarahkan ke halaman
milik role-nya sendiri, bukan diberi halaman kosong.

## Status Akun

Kolom `users.status` menyimpan:

| Status | Arti |
|---|---|
| `aktif` | Akun normal, bisa login dan melakukan aksi sesuai role |
| `sudah_voting` | Pemilih yang sudah memberikan suara, tidak bisa voting lagi |
| `nonaktif` | Akun dinonaktifkan, tidak bisa masuk |

## Membuat Kode Login

Kode baru dibuat otomatis oleh `App\Support\KodeGenerator` yang menghasilkan
angka acak antara `100000` sampai `999999` dan memastikan kodenya belum dipakai
user lain.

Tiga cara memperoleh kode:

1. **Seeder** — kode development sudah disiapkan, lihat halaman Overview KPU
2. **Tambah user baru** — kode dibuat otomatis oleh sistem
3. **Reset kode** — admin dapat membuat kode baru satu user atau sekaligus
   semua user yang kodenya kosong

Kode yang dibuat melalui UI ditampilkan sekali setelah aksi, lalu tidak
disimpan di halaman mana pun.

## Mengganti Role

Role bisa diubah dari halaman edit user oleh admin. Enum `RoleEnum` memiliki
method `assignable()` yang mengembalikan daftar role yang boleh dipilih, sehingga
role `admin` tidak bisa dipilih sendiri lewat form.