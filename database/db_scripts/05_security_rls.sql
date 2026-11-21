-- =====================================================================
--  ROOMASTER - 05_security_rls.sql
--  Akses dan keamanan: role, user, hak akses (GRANT), dan padanan RLS.
--  Jalankan SETELAH 01..04, sebagai root/admin:
--      mysql -u root -p roomaster < 05_security_rls.sql
--
--  CATATAN PENTING: MySQL TIDAK punya Row Level Security bawaan
--  (CREATE POLICY / ENABLE ROW LEVEL SECURITY itu fitur PostgreSQL/Supabase).
--  Padanannya di MySQL, dan yang dikerjakan di file ini:
--    1. Role + GRANT per peran (admin, app, TU, Sarpras, viewer)  -> siapa boleh apa
--    2. View keamanan "rls_*" yang memfilter baris dengan fn_me()  -> mahasiswa
--       hanya melihat baris miliknya. View memakai SQL SECURITY DEFINER, jadi
--       user aplikasi cukup diberi hak SELECT pada view, TANPA hak pada tabel.
--    3. Semua perubahan data lewat stored procedure (04_procedures_rpc.sql)
--       yang memakai fn_me_wajib() sebagai pelaku -> tidak bisa bertindak
--       atas nama mahasiswa lain.
--
--  BATAS YANG JUJUR
--   * Identitas pengguna dibawa user variable @app_mahasiswa_id yang diisi
--     aplikasi. Kalau aplikasi kena SQL injection, penyerang bisa mengganti
--     nilainya. Jadi ini lapisan tambahan (defense in depth) + prinsip hak
--     akses minimum, BUKAN pengganti otorisasi di Laravel (Policy/Gate).
--   * Hanya user "roomaster_app_rls" yang benar-benar terkunci. User
--     "roomaster_app" (mode transisi) masih boleh baca/tulis tabel langsung
--     karena controller saat ini memang memakai DB::table() ke tabel dasar.
--     Pindah ke mode ketat setelah controller membaca dari rls_* dan menulis
--     lewat CALL sp_* (lihat README.md).
--
--  GANTI SEMUA PASSWORD 'GANTI_...' SEBELUM DIPAKAI. Jangan commit password asli.
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

-- =====================================================================
--  BAGIAN A. VIEW KEAMANAN (padanan RLS)
--  Aman dijalankan ulang. Tanpa @app_mahasiswa_id, fn_me() = NULL sehingga
--  semua view di bawah mengembalikan 0 baris (gagal tertutup).
-- =====================================================================

-- Identitas sendiri
CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_mahasiswa AS
SELECT m.id, m.nrp, m.nama, m.email_its, m.foto_profil
FROM mahasiswa_its m
WHERE m.id = fn_me();

-- Peminjaman milik sendiri (tabel dasar dan versi view lengkap dengan nama ruangan, is_upcoming)
CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_peminjaman AS
SELECT p.id, p.id_mahasiswa, p.id_ruangan, p.nama_kegiatan, p.tujuan, p.departemen_penyelenggara,
       p.tanggal_pakai, p.waktu_mulai, p.waktu_selesai, p.status, p.tanggal_pengajuan,
       p.waktu_checkin, p.waktu_checkout, p.created_at, p.updated_at
FROM peminjaman p
WHERE p.id_mahasiswa = fn_me();

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_peminjaman_tampil AS
SELECT v.*
FROM v_peminjaman_tampil v
WHERE v.id_mahasiswa = fn_me();

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_booking_terdekat AS
SELECT b.*
FROM v_booking_terdekat b
WHERE b.id_mahasiswa = fn_me();

-- Dokumen dan riwayat dari peminjaman milik sendiri
CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_dokumen_peminjaman AS
SELECT d.id, d.id_peminjaman, d.jenis_dokumen, d.nama_berkas, d.path_berkas,
       d.ukuran_berkas, d.status, d.waktu_unggah
FROM dokumen_peminjaman d
JOIN peminjaman p ON p.id = d.id_peminjaman
WHERE p.id_mahasiswa = fn_me();

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_riwayat_peminjaman AS
SELECT r.id, r.id_peminjaman, r.tahapan, r.keterangan, r.waktu
FROM riwayat_peminjaman r
JOIN peminjaman p ON p.id = r.id_peminjaman
WHERE p.id_mahasiswa = fn_me();

-- Ulasan yang saya tulis (ulasan semua orang per ruangan tetap publik lewat v_ulasan_ruangan)
CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_ulasan_saya AS
SELECT u.id, u.id_peminjaman, p.id_ruangan, u.rating, u.komentar, u.tanggal_ulasan
FROM ulasan_peminjaman u
JOIN peminjaman p ON p.id = u.id_peminjaman
WHERE p.id_mahasiswa = fn_me();

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_ruangan_favorit AS
SELECT f.id_mahasiswa, f.id_ruangan, f.created_at
FROM ruangan_favorit f
WHERE f.id_mahasiswa = fn_me();

-- Percakapan: hanya yang saya ikuti
CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_percakapan AS
SELECT s.*
FROM v_percakapan_ringkas s
WHERE s.id_mahasiswa = fn_me();

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_pesan AS
SELECT p.id, p.id_percakapan, p.id_pengirim,
       IF(p.id_pengirim = fn_me(), 'out', 'in') AS sisi,
       p.isi_pesan, p.waktu_kirim, p.sudah_dibaca
FROM pesan p
JOIN percakapan c ON c.id = p.id_percakapan
WHERE fn_me() IN (c.id_mahasiswa_1, c.id_mahasiswa_2);

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_notifikasi AS
SELECT n.id, n.id_mahasiswa, n.judul, n.isi, n.kategori, n.waktu, n.sudah_dibaca
FROM notifikasi n
WHERE n.id_mahasiswa = fn_me();

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_badge AS
SELECT b.*
FROM v_badge_mahasiswa b
WHERE b.id_mahasiswa = fn_me();

-- Laporan kerusakan sendiri
CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_laporan AS
SELECT l.*
FROM v_laporan_ringkas l
WHERE l.id_mahasiswa = fn_me();

CREATE OR REPLACE SQL SECURITY DEFINER VIEW rls_bukti_laporan AS
SELECT b.id, b.id_laporan, b.jenis_media, b.nama_berkas, b.path_berkas,
       b.ukuran_berkas, b.waktu_unggah
FROM bukti_laporan b
JOIN laporan_kerusakan l ON l.id = b.id_laporan
WHERE l.id_mahasiswa = fn_me();


-- =====================================================================
--  BAGIAN B. ROLE
-- =====================================================================
CREATE ROLE IF NOT EXISTS
    'r_roomaster_admin',
    'r_roomaster_app',        -- Laravel, mode TRANSISI (akses tabel seperti sekarang)
    'r_roomaster_app_rls',    -- Laravel, mode KETAT (hanya view rls_* + CALL sp_*)
    'r_roomaster_viewer';

-- Admin / DBA: penuh di database roomaster (tanpa hak mengelola user lain).
GRANT ALL PRIVILEGES ON roomaster.* TO 'r_roomaster_admin';

-- Aplikasi, mode transisi: DML + EXECUTE saja. TANPA DDL (CREATE/DROP/ALTER/INDEX/TRIGGER).
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON roomaster.* TO 'r_roomaster_app';

-- ---------------------------------------------------------------------
-- Aplikasi, mode ketat.
-- 1) Data referensi yang memang publik bagi pengguna login
-- ---------------------------------------------------------------------
GRANT SELECT ON roomaster.data_ruangan       TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.fasilitas          TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.fasilitas_ruangan  TO 'r_roomaster_app_rls';
GRANT SELECT (id, nama, foto_profil, rata_respon_menit)
                                             ON roomaster.informasi_tu TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.v_ruangan_kartu         TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.v_ketersediaan_ruangan  TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.v_ulasan_ruangan        TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.v_ulasan_top            TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.v_rating_ruangan        TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.v_jam_operasional       TO 'r_roomaster_app_rls';

-- 2) View per-pengguna (rls_*)
GRANT SELECT ON roomaster.rls_mahasiswa            TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_peminjaman           TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_peminjaman_tampil    TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_booking_terdekat     TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_dokumen_peminjaman   TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_riwayat_peminjaman   TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_ulasan_saya          TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_ruangan_favorit      TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_percakapan           TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_pesan                TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_notifikasi           TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_badge                TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_laporan              TO 'r_roomaster_app_rls';
GRANT SELECT ON roomaster.rls_bukti_laporan        TO 'r_roomaster_app_rls';

-- 3) Login testing (Auth::attempt) harus membaca profil_pengguna SEBELUM sesi ada.
--    Kolom dibatasi; tidak ada INSERT/DELETE (pendaftaran lewat sp_daftar_mahasiswa).
GRANT SELECT (id, id_mahasiswa, username, password, remember_token)
    ON roomaster.profil_pengguna TO 'r_roomaster_app_rls';
GRANT UPDATE (remember_token, terakhir_login, updated_at)
    ON roomaster.profil_pengguna TO 'r_roomaster_app_rls';

-- 4) Semua penulisan data lewat procedure (kecuali yang khusus TU/Sarpras).
GRANT EXECUTE ON PROCEDURE roomaster.sp_set_sesi                TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_akhiri_sesi             TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_daftar_mahasiswa        TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_cari_ruangan            TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_toggle_favorit          TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_jam_terpakai            TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_simpan_draft_peminjaman TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_upload_dokumen          TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_hapus_dokumen           TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_kirim_pengajuan         TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_batalkan_peminjaman     TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_check_in                TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_check_out               TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_beri_ulasan             TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_mulai_percakapan        TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_kirim_pesan             TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_buka_percakapan         TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_daftar_percakapan       TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_daftar_notifikasi       TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_tandai_notifikasi_dibaca TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_ambil_ruangan_by_qr     TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_simpan_laporan          TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_tambah_bukti            TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_ringkasan_laporan       TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_bukti_laporan           TO 'r_roomaster_app_rls';
GRANT EXECUTE ON PROCEDURE roomaster.sp_riwayat_laporan         TO 'r_roomaster_app_rls';
-- sp_keputusan_peminjaman dan sp_update_status_laporan SENGAJA tidak diberikan ke app mahasiswa
-- (hanya admin yang memilikinya lewat GRANT ALL).

-- TU dan Sarpras TIDAK punya user/role: di aplikasi ini penggunanya hanya mahasiswa.
-- Peran mereka (menyetujui pengajuan, memproses laporan) diwakili data awal di 07_seed_demo.sql
-- dan, opsional, simulator di 08_tambahan.sql (sp_demo_simulasi_tu_sarpras).
-- sp_keputusan_peminjaman / sp_update_status_laporan tetap ada, hanya untuk admin/DBA.

-- Viewer (dosen penilai / pengamat): baca saja, tanpa password dan data pribadi.
GRANT SELECT ON roomaster.data_ruangan           TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.fasilitas              TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.fasilitas_ruangan      TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.peminjaman             TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.riwayat_peminjaman     TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.ulasan_peminjaman      TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.laporan_kerusakan      TO 'r_roomaster_viewer';
GRANT SELECT (id, nama)                          ON roomaster.mahasiswa_its TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.v_ruangan_kartu        TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.v_statistik_ruangan    TO 'r_roomaster_viewer';
GRANT SELECT ON roomaster.v_laporan_ringkas      TO 'r_roomaster_viewer';


-- =====================================================================
--  BAGIAN C. USER  (GANTI PASSWORD! host 'localhost' = server yang sama;
--  ubah ke host aplikasi jika database berada di mesin lain)
-- =====================================================================
CREATE USER IF NOT EXISTS 'roomaster_admin'@'localhost'   IDENTIFIED BY 'GANTI_admin_Rm#2026';
CREATE USER IF NOT EXISTS 'roomaster_app'@'localhost'     IDENTIFIED BY 'GANTI_app_Rm#2026';
CREATE USER IF NOT EXISTS 'roomaster_app_rls'@'localhost' IDENTIFIED BY 'GANTI_apprls_Rm#2026';
CREATE USER IF NOT EXISTS 'roomaster_viewer'@'localhost'  IDENTIFIED BY 'GANTI_viewer_Rm#2026';

GRANT 'r_roomaster_admin'   TO 'roomaster_admin'@'localhost';
GRANT 'r_roomaster_app'     TO 'roomaster_app'@'localhost';
GRANT 'r_roomaster_app_rls' TO 'roomaster_app_rls'@'localhost';
GRANT 'r_roomaster_viewer'  TO 'roomaster_viewer'@'localhost';

-- Role harus aktif otomatis saat login.
-- Sintaks MySQL 8: SET DEFAULT ROLE 'role' TO 'user'@'host';
-- (MariaDB memakai:  SET DEFAULT ROLE `role` FOR 'user'@'host';)
SET DEFAULT ROLE 'r_roomaster_admin' TO 'roomaster_admin'@'localhost';
SET DEFAULT ROLE 'r_roomaster_app' TO 'roomaster_app'@'localhost';
SET DEFAULT ROLE 'r_roomaster_app_rls' TO 'roomaster_app_rls'@'localhost';
SET DEFAULT ROLE 'r_roomaster_viewer' TO 'roomaster_viewer'@'localhost';

-- =====================================================================
--  BAGIAN D. UJI MANUAL RLS (jalankan sebagai roomaster_app_rls, bukan root)
--
--    SELECT COUNT(*) FROM rls_peminjaman;              -- 0  (sesi belum diatur)
--    CALL sp_set_sesi(1);
--    SELECT COUNT(*) FROM rls_peminjaman;              -- hanya milik mahasiswa 1
--    CALL sp_set_sesi(2);
--    SELECT COUNT(*) FROM rls_peminjaman;              -- hanya milik mahasiswa 2
--    SELECT * FROM peminjaman;                          -- ERROR 1142: tidak punya hak
--    CALL sp_kirim_pengajuan(<id milik mahasiswa 1>);   -- ERROR: Peminjaman tidak ditemukan.
--    CALL sp_keputusan_peminjaman(1,'disetujui',NULL);  -- ERROR 1370: tidak punya hak EXECUTE
--    CALL sp_akhiri_sesi();
--
--  Periksa hak akses:
--    SHOW GRANTS FOR 'roomaster_app_rls'@'localhost' USING 'r_roomaster_app_rls';
-- =====================================================================
