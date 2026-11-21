# ROOMASTER – Script SQL Tambahan (MySQL 8)

Dibuat oleh: Muhammad Satrio Adjie (5026241065) — PPPL Group 07.
Basis: `database/roomaster.sql` (InnoDB, utf8mb4). Target: **MariaDB 10.5+** (dipakai tim) dan MySQL 8.0.16+. Beda sintaks yang diketahui: `SET DEFAULT ROLE ... FOR` (MariaDB) vs `... TO` (MySQL) di `05_security_rls.sql`.

> **PENTING – status pengujian.** Script ini sudah dicek statis (keseimbangan
> `BEGIN/END`, `DELIMITER`, kurung, panjang `MESSAGE_TEXT`, nama kolom terhadap DDL,
> objek yang di-GRANT benar-benar ada), tetapi **belum pernah dijalankan di server
> MySQL sungguhan** (sandbox tidak punya MySQL). Jalankan dulu di database lokal/staging,
> bukan langsung di data penting. Bila ada error, kirim pesan error + nama file/baris.

## Catatan terjemahan: MySQL tidak punya RLS/RPC bawaan

| Istilah | Padanan di MySQL yang dipakai di sini |
|---|---|
| RPC | Stored procedure `sp_*` (dipanggil `CALL sp_x(...)`) |
| RLS | View `rls_*` yang memfilter `id_mahasiswa = fn_me()`, role + `GRANT` per kolom/objek, dan procedure yang mengambil identitas dari `@app_mahasiswa_id` |

Keterbatasan: variabel sesi bisa diubah bila ada SQL injection, jadi ini lapisan
tambahan (defense-in-depth), bukan pengganti validasi di Laravel.

## Urutan menjalankan

Semua lewat **mysql CLI** (karena memakai `DELIMITER $$`; jangan lewat `DB::unprepared`):

```powershell
mysql -u root -p roomaster < 01_functions.sql
mysql -u root -p roomaster < 02_views.sql
mysql -u root -p roomaster < 03_triggers.sql
mysql -u root -p roomaster < 04_procedures_rpc.sql
mysql -u root -p roomaster < 05_security_rls.sql     # ganti password placeholder dulu!
mysql -u root -p roomaster < 06_seed_master.sql      # ruangan + fasilitas (WAJIB agar ruangan tampil)
mysql -u root -p roomaster < 07_seed_demo.sql        # opsional: data demo + akun testing
mysql -u root -p roomaster < 08_tambahan.sql         # opsional: indeks, event (nonaktif)
```

Jika `CREATE FUNCTION` ditolak karena binlog: `SET GLOBAL log_bin_trust_function_creators = 1;`

| File | Isi |
|---|---|
| 01_functions.sql | `fn_me`, `fn_me_wajib`, `fn_nomor_6digit`, `fn_slot_tersedia`, `fn_rata_rating`, `fn_peserta_percakapan`, `fn_status_boleh`, `fn_status_laporan_boleh` |
| 02_views.sql | `v_ruangan_kartu`, `v_rating_ruangan`, `v_ulasan_top`, `v_booking_terdekat`, `v_percakapan_ringkas`, `v_laporan_ringkas`, `v_antrean_pengajuan`, `v_badge_mahasiswa`, `v_statistik_ruangan`, `v_jam_operasional` |
| 03_triggers.sql | Aturan bisnis: matriks transisi status, bentrok slot (hanya `disetujui`/`berlangsung`), data beku setelah diajukan, riwayat + notifikasi otomatis, ulasan hanya setelah selesai, dokumen hanya saat draft/revisi, validasi peserta pesan, alur laporan |
| 04_procedures_rpc.sql | 28 procedure (draft, dokumen, pengajuan, batal, check-in/out, ulasan, chat, notifikasi, laporan, keputusan TU/Sarpras, dll.) |
| 05_security_rls.sql | View `rls_*`, role, user `roomaster_*` (password placeholder), blok uji manual RLS. Tanpa user TU/Sarpras (penggunanya hanya mahasiswa) |
| 06_seed_master.sql | 11 fasilitas, 12 ruangan, relasi fasilitas, info TU dummy (idempoten) |
| 07_seed_demo.sql | 7 mahasiswa + akun testing, peminjaman di semua status, ulasan, favorit, chat, laporan, notifikasi |
| 08_tambahan.sql | Indeks pendukung (termasuk unik `(id_peminjaman, jenis_dokumen)`), 3 event (2 nonaktif, simulator TU/Sarpras **aktif**), simulator TU/Sarpras `sp_demo_simulasi_tu_sarpras`, cek zona waktu |
| 99_drop_objek_tambahan.sql | Menghapus semua objek tambahan (tabel/data tidak disentuh) |

## TU & Sarpras tidak punya user

Penggunanya hanya mahasiswa. Peran TU/Sarpras (setujui pengajuan, proses laporan) diwakili:
- **Data awal** di `07_seed_demo.sql`: peminjaman sudah ada di status pending/disetujui/revisi/ditolak/selesai lengkap dengan riwayat dan catatan, laporan di pending/diproses/selesai, serta percakapan antar-mahasiswa.
- **Simulator opsional** (`08_tambahan.sql`): tanpa ini, pengajuan baru dari mahasiswa berhenti di `pending`. Event `ev_demo_simulasi_tu_sarpras` **aktif** dan jalan tiap menit: pengajuan yang menunggu >1 menit disetujui/direvisi/ditolak otomatis, laporan menjadi diproses lalu selesai. Penjadwal event dinyalakan oleh script (`SET GLOBAL event_scheduler = ON`; tambahkan `event_scheduler = ON` di `my.ini` agar tetap menyala setelah restart). Hanya untuk demo; matikan di produksi: `ALTER EVENT ev_demo_simulasi_tu_sarpras DISABLE;`.
- `sp_keputusan_peminjaman` dan `sp_update_status_laporan` tetap ada, tetapi hanya untuk admin/DBA.

## Akun testing (hanya setelah 07)

Username: `satrio`, `thoriq`, `satya`, `tasia`, `thalia`, `dummya`, `dummyb` — password semua: `roomaster123`.
Hapus sebelum produksi.

## Pakai dari Laravel

Middleware (sekali per request, setelah login):

```php
DB::statement('SET @app_mahasiswa_id = ?', [auth()->user()->id_mahasiswa]);
// atau: DB::statement('CALL sp_set_sesi(?)', [$idMahasiswa]);
```

Contoh pemanggilan procedure:

```php
$rows = DB::select('CALL sp_cari_ruangan(?, ?, ?, ?)', [$q, $kategori, $kapasitas, $tanggal]);
DB::statement('CALL sp_kirim_pengajuan(?)', [$idPeminjaman]);
```

Pesan error dari `SIGNAL SQLSTATE '45000'` muncul sebagai `QueryException`; ambil
`$e->errorInfo[2]` untuk ditampilkan ke pengguna.

## Dampak ke Controller (penting)

Karena trigger sekarang mengisi `riwayat_peminjaman` dan `notifikasi` sendiri:

1. **Hapus insert manual** ke dua tabel itu di controller, kalau tidak data akan ganda.
2. Perubahan status harus mengikuti matriks transisi (`fn_status_boleh`), kalau tidak ditolak.
3. Peminjaman yang sudah diajukan tidak bisa diubah field kegiatannya (data beku).
4. Ulasan hanya bisa dibuat setelah jadwal peminjaman selesai.
5. Keterangan riwayat khusus (mis. alasan penolakan): `SET @riwayat_keterangan = '...'` sebelum `UPDATE`.

## Keamanan

- Ganti semua password `GANTI_..._Rm#2026` di `05_security_rls.sql`; **jangan commit password asli**.
- `roomaster_app_rls` hanya boleh baca lewat `rls_*` dan menulis lewat `CALL sp_*`.
- Semua view memakai `NOW()`: set zona waktu server ke `+07:00`.
