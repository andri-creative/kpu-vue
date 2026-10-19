---
title: Pengaturan Database
group: Fitur
order: 3
---

Halaman **Settings → Database & Log** adalah panel pemantauan dan pemeliharaan
database yang hanya bisa dibuka oleh **admin**. Dari sini admin dapat melihat
kapasitas penyimpanan, mengoptimalkan database, dan mereset data.

Akses: `/settings/database` (middleware `auth` + `role:admin`).

## Kartu Ringkasan

Di bagian atas terdapat empat kartu metrik:

| Kartu | Isi |
|---|---|
| Ukuran File Database | Ukuran file database, path, dan ruang kosong (freelist) |
| Total Log Aktivitas | Jumlah baris pada tabel `vote_logs` |
| Surat Suara Masuk | Jumlah baris pada tabel `votes` |
| Total Baris Data | Total seluruh baris + jumlah akun + total arsip |

Badge driver (mis. `SQLITE`) ditampilkan di samping judul halaman.

## Rincian Penyimpanan Tabel

Tabel memuat setiap tabel database dengan kolom: nama tabel, kategori, banyak
data, estimasi ukuran, porsi data, keterangan, dan **Aksi**.

| Kategori | Contoh Tabel |
|---|---|
| Log & Aktivitas | `vote_logs` |
| Voting | `votes`, `candidates` |
| Pengguna | `users`, `profiles` |
| Arsip | `votes_archived`, `vote_logs_archived`, `users_archived` |
| Sistem | `sessions`, `migrations` |

Estimasi ukuran per tabel dihitung proporsional terhadap jumlah barisnya.

## Kontrol Pemeliharaan

| Tombol | Fungsi |
|---|---|
| Segarkan | Memuat ulang data statistik tanpa pindah halaman |
| Auto-Update | Mengulang pemuatan otomatis setiap 8 detik (aktif/mati) |
| Optimalkan (VACUUM) | Memadatkan database |

Operasi **VACUUM** menyesuaikan driver: `VACUUM` (SQLite), `VACUUM ANALYZE`
(PostgreSQL), dan `OPTIMIZE TABLE` (MySQL/MariaDB).

## Reset Data

Setiap baris tabel memiliki tombol **Reset** (ikon kunci = tabel dilindungi).
Ada juga tombol **Reset Semua Data** di header kartu tabel.

Setiap aksi reset wajib dikonfirmasi dengan mengetik teks `RESET`.

### Perilaku Arsip

Data tidak langsung hilang. Sebelum dihapus, data disalin lebih dulu ke tabel
arsip bila tersedia:

| Tabel Aktif | Tabel Arsip |
|---|---|
| `votes` | `votes_archived` |
| `vote_logs` | `vote_logs_archived` |
| `users` | `users_archived` |

## Perlindungan Data

- Akun dengan role **admin tidak pernah dihapus** oleh reset apa pun.
- Tabel `migrations` **dilindungi** dan tidak dapat direset.
- Reset `users` hanya menghapus akun non-admin (pemilih & kandidat); profil dan
  vote/log miliknya ikut diarsipkan lebih dulu.
- Reset `profiles` hanya menghapus profil milik user non-admin.
- Reset `sessions` menghapus semua sesi login — seluruh pengguna, termasuk
  admin yang sedang aktif, akan logout.
- Reset seluruh data **mempertahankan tabel arsip** sebagai tujuan arsip.

## Tabel yang Dapat Direset

```
vote_logs, votes, users, profiles, candidates,
vote_logs_archived, votes_archived, users_archived, sessions
```

## Log Aktivitas Terbaru

Di bagian bawah halaman ditampilkan **10 log aktivitas voting terbaru**: ID,
waktu (absolut + relatif), nama pemilih, NIS, kelas, kandidat yang dipilih,
IP address, dan status.

## Catatan Teknis

- `GET /settings/database` — halaman `settings/Database`
- `POST /settings/database/vacuum` — optimasi database
- `POST /settings/database/reset` — reset satu tabel (`table`, `confirmation`)
- `POST /settings/database/reset-all` — reset seluruh data (`confirmation`)

Validasi: nilai `table` harus salah satu dari daftar tabel yang dapat direset,
dan `confirmation` harus tepat `RESET`.
