---
title: Skema Database
group: Database
order: 1
---

## Driver dan Koneksi

Aplikasi KPU memakai **SQLite** sebagai driver default. Driver lain yang
didukung: MySQL, MariaDB, PostgreSQL, SQL Server.

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## Diagram Relasi

```
users 1 ─── 1 profiles
users 1 ─── 1 candidates
users 1 ─── 1 votes
candidates 1 ─── * votes
users 1 ─── * vote_logs
candidates 1 ─── * vote_logs
```

## Ringkasan Tabel

| Tabel | Deskripsi | Relasi |
|---|---|---|
| `users` | Data akun (admin, pemilih, kandidat) | — |
| `profiles` | Nama lengkap dan kelas | milik `users` |
| `candidates` | Data kandidat beserta visi misi | milik `users` |
| `votes` | Satu suara per pemilih | ke `users` dan `candidates` |
| `vote_logs` | Riwayat aktivitas voting | ke `users` dan `candidates` |
| `users_archived` | Arsip akun per tahun | — |
| `votes_archived` | Arsip suara per tahun | — |
| `vote_logs_archived` | Arsip log per tahun | — |
| `sessions` | Sesi login user | ke `users` |

## Detail Kolom

### users

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | uuid | tidak | Primary key |
| `nis` | string | tidak | Nomor induk siswa, unique |
| `kode` | char(64) | ya | Kode login 6 digit, unique |
| `password` | string | tidak | Nilai acak, tidak dipakai untuk login |
| `remember_token` | varchar | ya | Token remember me |
| `role` | string | tidak | `admin`, `pemilih`, `kandidat` |
| `status` | string | tidak | `aktif`, `sudah_voting`, `nonaktif` |
| `created_at` | timestamp | ya | — |
| `updated_at` | timestamp | ya | — |

### profiles

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | bigint | tidak | Primary key |
| `user_id` | uuid | tidak | Foreign key ke `users.id`, cascade delete, unique |
| `nama_lengkap` | string | tidak | — |
| `kelas` | string | tidak | — |
| `created_at` | timestamp | ya | — |
| `updated_at` | timestamp | ya | — |

### candidates

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | bigint | tidak | Primary key |
| `user_id` | uuid | tidak | Foreign key ke `users.id`, cascade delete, unique |
| `foto` | string | tidak | Nama file foto di folder penyimpanan |
| `nama_lengkap` | string | tidak | — |
| `nomor_urut` | integer | tidak | Urutan di ballot, unique |
| `nis` | string | tidak | NIS kandidat, unique |
| `kelas` | string | tidak | — |
| `visi` | text | tidak | Visi kandidat |
| `misi` | text | tidak | Misi kandidat |
| `created_at` | timestamp | ya | — |
| `updated_at` | timestamp | ya | — |

### votes

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | uuid | tidak | Primary key |
| `user_id` | uuid | tidak | Foreign key ke `users.id`, cascade delete, **unique** |
| `candidate_id` | bigint | tidak | Foreign key ke `candidates.id`, cascade delete |
| `voted_at` | timestamp | tidak | Waktu memberikan suara |
| `created_at` | timestamp | ya | — |
| `updated_at` | timestamp | ya | — |

Kolom `user_id` yang unique menjamin satu pemilih hanya bisa satu suara.

### vote_logs

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | bigint | tidak | Primary key |
| `user_id` | uuid | tidak | Foreign key ke `users.id`, cascade delete |
| `candidate_id` | bigint | tidak | Foreign key ke `candidates.id`, cascade delete |
| `action` | string | tidak | Jenis aktivitas, contoh `vote_cast` |
| `detail` | text | ya | Keterangan tambahan |
| `ip_address` | varchar(45) | ya | IP pelaku aktivitas |
| `created_at` | timestamp | ya | — |
| `updated_at` | timestamp | ya | — |

### Tabel Arsip

Tiga tabel arsip menyimpan data sebelum di-reset, dengan kolom `tahun` sebagai
penanda periode dan `archived_at` sebagai waktu pengarsipan:

| Tabel | Kunci |
|---|---|
| `users_archived` | `id` (uuid), index `nis` |
| `votes_archived` | `id` (uuid), index `user_nis` |
| `vote_logs_archived` | `id` (bigint), index `user_nis` |

## Constraint dan Index

- **Unique**: `users.nis`, `users.kode`, `candidates.nomor_urut`, `candidates.nis`, `votes.user_id`
- **Foreign key**: `profiles.user_id`, `candidates.user_id`, `votes.user_id`, `votes.candidate_id`, `vote_logs.user_id`, `vote_logs.candidate_id`
- **Cascade**: semua foreign key memakai `cascadeOnDelete`

## Model Eloquent

| Model | Tabel |
|---|---|
| `App\Models\User` | `users` |
| `App\Models\Profile` | `profiles` |
| `App\Models\Candidate` | `candidates` |
| `App\Models\Vote` | `votes` |
| `App\Models\VoteLog` | `vote_logs` |
| `App\Models\UsersArchived` | `users_archived` |
| `App\Models\VotesArchived` | `votes_archived` |
| `App\Models\VoteLogsArchived` | `vote_logs_archived` |