---
title: Manajemen User
group: Fitur
order: 1
---

## Halaman Manajemen User

Halaman `/users` hanya bisa diakses admin. Daftar user ditampilkan dengan
paginate 15 baris per halaman, urutan nonaktif selalu di paling akhir.

## Filter dan Pencarian

Tiga filter bekerja bersamaan:

| Filter    | Query    | Keterangan                                     |
| --------- | -------- | ---------------------------------------------- |
| Pencarian | `search` | Mencocokkan NIS atau nama lengkap              |
| Kelas     | `kelas`  | Filter berdasarkan kelas pada tabel `profiles` |
| Status    | `status` | Filter status akun                             |

Filter diteruskan ke query memakai `when()` sehingga parameter kosong tidak
menambahkan kondisi apa pun.

## Menambah User Manual

User yang dibuat lewat form selalu berperan sebagai **pemilih** dengan kode
login dibuat otomatis oleh `KodeGenerator`. Setelah berhasil, kode ditampilkan
satu kali di notifikasi.

## Import dari Excel

Alur import terdiri dari tiga langkah:

1. Unduh template di `users/import/template`
2. Unggah file, sistem menampilkan preview tanpa menyimpan apa pun
3. Simpan, data masuk ke database dan kode login dibuat untuk semua baris

```text
GET  users/import/template   → template Excel kosong
GET  users/import            → halaman import
POST users/import/preview   → preview tanpa menyimpan
POST users/import            → menyimpan data
```

Baris yang tidak valid membuat seluruh import ditolak dengan pesan error, bukan
disimpan sebagian.

## Edit dan Role

Edit user hanya mengubah **NIS** dan **role**. Kode login tidak berubah kecuali
dipilih aksi reset kode. Daftar role yang bisa dipilih berasal dari
`RoleEnum::assignable()`, sehingga role `admin` tidak bisa dipakai.

## Reset Kode

Dua aksi tersedia:

| Aksi                        | Route                           | Keterangan                                            |
| --------------------------- | ------------------------------- | ----------------------------------------------------- |
| Reset satu user             | `PATCH users/{user}/reset-kode` | Mengganti kode user tersebut                          |
| Buat kode untuk yang kosong | `PATCH users/reset-kode-kosong` | Mengisi kode semua user non-admin yang kodenya `NULL` |

Kode baru selalu angka acak 6 digit yang belum dipakai user lain. Hasilnya
ditampilkan satu kali lewat flash `generated_codes`, lalu tidak disimpan di
halaman.

## Menghapus User

Menghapus user otomatis menghapus data terkait karena semua foreign key memakai
`cascadeOnDelete`:

- `profiles` ikut terhapus
- `votes` ikut terhapus
- `vote_logs` ikut terhapus

Kandidat juga punya baris di tabel `candidates` yang ikut terhapus karena
relasinya ke `users`.
