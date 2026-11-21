-- =====================================================================
--  ROOMASTER - 99_drop_objek_tambahan.sql
--  Menghapus SEMUA objek yang dibuat file 01..08 (function, view tambahan,
--  trigger, procedure, event, indeks tambahan). TIDAK menyentuh tabel dan
--  data, dan TIDAK menyentuh 3 view bawaan DDL.
--  Berguna untuk mengulang dari awal atau kembali ke skema DDL murni.
--      mysql -u root -p roomaster < 99_drop_objek_tambahan.sql
--
--  Role dan user (05_security_rls.sql) TIDAK dihapus di sini; blok
--  opsional ada di paling bawah (dikomentari).
--  Data demo (07) tidak ikut terhapus.
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

-- Event
DROP EVENT IF EXISTS ev_batalkan_draft_usang;
DROP EVENT IF EXISTS ev_batalkan_tidak_hadir;
DROP EVENT IF EXISTS ev_demo_simulasi_tu_sarpras;

-- Trigger
DROP TRIGGER IF EXISTS trg_peminjaman_bi;
DROP TRIGGER IF EXISTS trg_peminjaman_ai;
DROP TRIGGER IF EXISTS trg_peminjaman_bu;
DROP TRIGGER IF EXISTS trg_peminjaman_au;
DROP TRIGGER IF EXISTS trg_dokumen_bi;
DROP TRIGGER IF EXISTS trg_dokumen_bd;
DROP TRIGGER IF EXISTS trg_ulasan_bi;
DROP TRIGGER IF EXISTS trg_percakapan_bi;
DROP TRIGGER IF EXISTS trg_percakapan_bu;
DROP TRIGGER IF EXISTS trg_pesan_bi;
DROP TRIGGER IF EXISTS trg_pesan_ai;
DROP TRIGGER IF EXISTS trg_laporan_bi;
DROP TRIGGER IF EXISTS trg_laporan_ai;
DROP TRIGGER IF EXISTS trg_laporan_bu;
DROP TRIGGER IF EXISTS trg_laporan_au;
DROP TRIGGER IF EXISTS trg_bukti_bi;

-- Procedure
DROP PROCEDURE IF EXISTS sp_set_sesi;
DROP PROCEDURE IF EXISTS sp_akhiri_sesi;
DROP PROCEDURE IF EXISTS sp_daftar_mahasiswa;
DROP PROCEDURE IF EXISTS sp_cari_ruangan;
DROP PROCEDURE IF EXISTS sp_toggle_favorit;
DROP PROCEDURE IF EXISTS sp_jam_terpakai;
DROP PROCEDURE IF EXISTS sp_simpan_draft_peminjaman;
DROP PROCEDURE IF EXISTS sp_upload_dokumen;
DROP PROCEDURE IF EXISTS sp_hapus_dokumen;
DROP PROCEDURE IF EXISTS sp_kirim_pengajuan;
DROP PROCEDURE IF EXISTS sp_batalkan_peminjaman;
DROP PROCEDURE IF EXISTS sp_check_in;
DROP PROCEDURE IF EXISTS sp_check_out;
DROP PROCEDURE IF EXISTS sp_beri_ulasan;
DROP PROCEDURE IF EXISTS sp_mulai_percakapan;
DROP PROCEDURE IF EXISTS sp_kirim_pesan;
DROP PROCEDURE IF EXISTS sp_buka_percakapan;
DROP PROCEDURE IF EXISTS sp_daftar_percakapan;
DROP PROCEDURE IF EXISTS sp_daftar_notifikasi;
DROP PROCEDURE IF EXISTS sp_tandai_notifikasi_dibaca;
DROP PROCEDURE IF EXISTS sp_ambil_ruangan_by_qr;
DROP PROCEDURE IF EXISTS sp_simpan_laporan;
DROP PROCEDURE IF EXISTS sp_tambah_bukti;
DROP PROCEDURE IF EXISTS sp_ringkasan_laporan;
DROP PROCEDURE IF EXISTS sp_bukti_laporan;
DROP PROCEDURE IF EXISTS sp_riwayat_laporan;
DROP PROCEDURE IF EXISTS sp_keputusan_peminjaman;
DROP PROCEDURE IF EXISTS sp_update_status_laporan;
DROP PROCEDURE IF EXISTS sp_seed_booking;
DROP PROCEDURE IF EXISTS sp_seed_demo;
DROP PROCEDURE IF EXISTS sp_tmp_indeks;
DROP PROCEDURE IF EXISTS sp_demo_simulasi_tu_sarpras;

-- View keamanan
DROP VIEW IF EXISTS rls_mahasiswa;
DROP VIEW IF EXISTS rls_peminjaman;
DROP VIEW IF EXISTS rls_peminjaman_tampil;
DROP VIEW IF EXISTS rls_booking_terdekat;
DROP VIEW IF EXISTS rls_dokumen_peminjaman;
DROP VIEW IF EXISTS rls_riwayat_peminjaman;
DROP VIEW IF EXISTS rls_ulasan_saya;
DROP VIEW IF EXISTS rls_ruangan_favorit;
DROP VIEW IF EXISTS rls_percakapan;
DROP VIEW IF EXISTS rls_pesan;
DROP VIEW IF EXISTS rls_notifikasi;
DROP VIEW IF EXISTS rls_badge;
DROP VIEW IF EXISTS rls_laporan;
DROP VIEW IF EXISTS rls_bukti_laporan;

-- View tambahan (bukan view bawaan DDL)
DROP VIEW IF EXISTS v_statistik_ruangan;
DROP VIEW IF EXISTS v_badge_mahasiswa;
DROP VIEW IF EXISTS v_antrean_pengajuan;
DROP VIEW IF EXISTS v_laporan_ringkas;
DROP VIEW IF EXISTS v_percakapan_ringkas;
DROP VIEW IF EXISTS v_booking_terdekat;
DROP VIEW IF EXISTS v_ulasan_top;
DROP VIEW IF EXISTS v_ruangan_kartu;
DROP VIEW IF EXISTS v_rating_ruangan;
DROP VIEW IF EXISTS v_jam_operasional;

-- Function
DROP FUNCTION IF EXISTS fn_me;
DROP FUNCTION IF EXISTS fn_me_wajib;
DROP FUNCTION IF EXISTS fn_nomor_6digit;
DROP FUNCTION IF EXISTS fn_slot_tersedia;
DROP FUNCTION IF EXISTS fn_rata_rating;
DROP FUNCTION IF EXISTS fn_peserta_percakapan;
DROP FUNCTION IF EXISTS fn_status_boleh;
DROP FUNCTION IF EXISTS fn_status_laporan_boleh;

-- Indeks tambahan (08_tambahan.sql). Abaikan error bila indeksnya tidak ada.
-- ALTER TABLE dokumen_peminjaman DROP INDEX uq_dokumen_jenis;
-- ALTER TABLE peminjaman         DROP INDEX idx_peminjaman_mahasiswa;
-- ALTER TABLE notifikasi         DROP INDEX idx_notifikasi_dibaca;
-- ALTER TABLE pesan              DROP INDEX idx_pesan_belum_dibaca;
-- ALTER TABLE laporan_kerusakan  DROP INDEX idx_laporan_mahasiswa;
-- ALTER TABLE data_ruangan       DROP INDEX idx_data_ruangan_kategori;

-- Role dan user (05_security_rls.sql), buka komentar bila ingin menghapus juga:
-- DROP USER IF EXISTS 'roomaster_admin'@'localhost', 'roomaster_app'@'localhost',
--     'roomaster_app_rls'@'localhost', 'roomaster_viewer'@'localhost';
-- DROP ROLE IF EXISTS 'r_roomaster_admin', 'r_roomaster_app', 'r_roomaster_app_rls',
--     'r_roomaster_viewer';
