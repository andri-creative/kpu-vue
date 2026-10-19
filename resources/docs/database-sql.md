---
title: Contoh Query SQL
group: Database
order: 2
---

Halaman ini berisi contoh query yang bisa disalin dan dijalankan sendiri pada
database aplikasi. Query ini **tidak dieksekusi** oleh server documentation.

## Total Suara per Kandidat

```sql
SELECT c.nama_lengkap, c.nomor_urut, COUNT(v.id) AS total_suara
FROM candidates c
LEFT JOIN votes v ON v.candidate_id = c.id
GROUP BY c.id, c.nama_lengkap, c.nomor_urut
ORDER BY c.nomor_urut;
```

## Tren Voting per Hari (SQLite)

SQLite tidak punya `DATE_TRUNC`, gunakan `strftime`:

```sql
SELECT strftime('%Y-%m-%d', voted_at) AS tanggal, COUNT(*) AS jumlah
FROM votes
GROUP BY tanggal
ORDER BY tanggal;
```

## Tren Voting per Hari (MySQL)

```sql
SELECT DATE(voted_at) AS tanggal, COUNT(*) AS jumlah
FROM votes
GROUP BY DATE(voted_at)
ORDER BY tanggal;
```

## Partisipasi Pemilih

```sql
SELECT
    SUM(CASE WHEN role = 'pemilih' THEN 1 ELSE 0 END) AS total_pemilih,
    SUM(CASE WHEN status = 'sudah_voting' THEN 1 ELSE 0 END) AS sudah_voting
FROM users;
```

## User yang Belum Punya Kode Login

```sql
SELECT nis, role, status
FROM users
WHERE kode IS NULL;
```

## Kandidat Beserta Jumlah Suara

```sql
SELECT
    c.nomor_urut,
    c.nama_lengkap,
    c.kelas,
    c.visi,
    COUNT(v.id) AS total_suara
FROM candidates c
LEFT JOIN votes v ON v.candidate_id = c.id
GROUP BY c.id, c.nomor_urut, c.nama_lengkap, c.kelas, c.visi
ORDER BY total_suara DESC;
```

## Riwayat Voting per Kandidat

```sql
SELECT
    vl.action,
    vl.ip_address,
    vl.created_at,
    u.nis,
    p.nama_lengkap,
    c.nomor_urut
FROM vote_logs vl
JOIN users u ON u.id = vl.user_id
LEFT JOIN profiles p ON p.user_id = u.id
JOIN candidates c ON c.id = vl.candidate_id
ORDER BY vl.created_at DESC;
```

## Rekap per Kelas

```sql
SELECT
    p.kelas,
    COUNT(*) AS total_suara
FROM votes v
JOIN profiles p ON p.user_id = v.user_id
GROUP BY p.kelas
ORDER BY total_suara DESC;
```

## Arsip per Tahun

```sql
SELECT tahun, COUNT(*) AS total_suara
FROM votes_archived
GROUP BY tahun
ORDER BY tahun DESC;
```

## Varian SQLite

Tidak semua fungsi MySQL tersedia di SQLite:

| MySQL            | SQLite                               |
| ---------------- | ------------------------------------ |
| `NOW()`          | `datetime('now')`                    |
| `DATE(voted_at)` | `date(voted_at)`                     |
| `DATE_FORMAT`    | `strftime('%Y-%m-%d', voted_at)`     |
| `IFNULL(a, b)`   | `IFNULL(a, b)` atau `COALESCE(a, b)` |
| `CONCAT(a, b)`   | `a \|\| b`                           |

## Menjalankan Query di Terminal

```bash
php artisan tinker --execute 'DB::table("votes")->count();'
```

Untuk SQLite langsung:

```bash
sqlite3 database/database.sqlite "SELECT COUNT(*) FROM votes;"
```
