---
title: Alur Voting
group: Fitur
order: 2
---

## Tahapan

```text
Pemilih login dengan kode
        ↓
Halaman /pemilih menampilkan kandidat urut nomor
        ↓
Pemilih memilih satu kandidat
        ↓
Suara tersimpan, status user jadi sudah_voting
        ↓
Halaman /pemilih menampilkan konfirmasi, voting ditutup
```

## Menampilkan Kandidat

Halaman `/pemilih` menampilkan kandidat dari tabel `candidates`, diurutkan
`nomor_urut` menaik. Role kandidat menampilkan profilnya sendiri, bukan ballot.

## Menyimpan Suara

Penyimpanan suara dilakukan pada `POST /pemilih/vote`:

1. Validasi `candidate_id` wajib ada di tabel `candidates`
2. Kalau user sudah `sudah_voting`, kembalikan ke halaman voting tanpa menyimpan
3. Baris `votes` dibuat dengan `voted_at` sekarang
4. Status user diubah menjadi `sudah_voting`
5. Baris `vote_logs` dibuat dengan `action` `vote_cast` beserta IP address

Pemeriksaan status di langkah 2 terjadi di server, jadi menekan tombol submit
dua kali tidak menghasilkan dua suara.

## Batas Satu Suara

Aturan ini dijaga dua lapis:

| Lapis | Mekanisme |
|---|---|
| Database | Unique constraint pada `votes.user_id` |
| Aplikasi | Pengecekan status `sudah_voting` sebelum menyimpan |

## Halaman Hasil

Halaman `/hasil` menampilkan rekap suara per kandidat dalam bentuk diagram.
Data diambil dari agregasi `votes` yang di-group berdasarkan `candidate_id`.

## Reset Voting

Halaman `/reset-pemilih` menampilkan statistik data saat ini dan ringkasan arsip
per tahun. Reset memerlukan konfirmasi mengetik `RESET`.

Saat reset dijalankan, semua data dipindahkan ke tabel arsip dalam satu
transaksi database:

```text
votes            → votes_archived
vote_logs        → vote_logs_archived
users non-admin  → users_archived
```

Setelah itu tabel `votes`, `vote_logs`, `candidates`, dan `users` non-admin
dikosongkan. User admin tetap ada. Tahun arsip diambil dari tanggal saat reset
dijalankan.

## Arsip

Tabel arsip tidak punya foreign key, jadi tetap utuh setelah data utama
dihapus. Laporan tahun sebelumnya bisa dibuat ulang dari tabel arsip karena
semua data penting sudah tersalin ke sana.