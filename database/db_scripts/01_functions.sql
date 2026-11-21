-- =====================================================================
--  ROOMASTER - 01_functions.sql
--  Fungsi bantu. Jalankan SETELAH roomaster.sql (DDL), sebagai root/admin.
--  Target: MySQL 8.0.16+.
--
--  Cara menjalankan (CLI, karena memakai DELIMITER):
--      mysql -u root -p roomaster < 01_functions.sql
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

DROP FUNCTION IF EXISTS fn_me;
DROP FUNCTION IF EXISTS fn_me_wajib;
DROP FUNCTION IF EXISTS fn_nomor_6digit;
DROP FUNCTION IF EXISTS fn_slot_tersedia;
DROP FUNCTION IF EXISTS fn_rata_rating;
DROP FUNCTION IF EXISTS fn_peserta_percakapan;
DROP FUNCTION IF EXISTS fn_status_boleh;
DROP FUNCTION IF EXISTS fn_status_laporan_boleh;

DELIMITER $$

-- ---------------------------------------------------------------------
-- fn_me(): id mahasiswa yang sedang login di sesi database ini.
--   Laravel mengisinya sekali per request:  SET @app_mahasiswa_id = <id>;
--   (atau CALL sp_set_sesi(<id>)). NULL = belum login -> view RLS kosong.
--   Dideklarasikan DETERMINISTIC supaya optimizer boleh memakai indeks
--   pada "WHERE id_mahasiswa = fn_me()". Nilainya tidak berubah di tengah
--   satu statement, jadi aman.
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_me() RETURNS BIGINT UNSIGNED
    DETERMINISTIC NO SQL
BEGIN
    RETURN @app_mahasiswa_id;
END$$

-- fn_me_wajib(): sama seperti fn_me(), tetapi melempar error bila sesi belum diatur.
CREATE FUNCTION fn_me_wajib() RETURNS BIGINT UNSIGNED
    DETERMINISTIC READS SQL DATA
BEGIN
    IF @app_mahasiswa_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sesi belum diatur: panggil sp_set_sesi(id_mahasiswa) setelah login.';
    END IF;
    RETURN @app_mahasiswa_id;
END$$

-- ---------------------------------------------------------------------
-- fn_nomor_6digit(id): tampilan nomor booking / laporan, contoh 000042.
--   (DDL: "Tampilan 6 digit dibuat di aplikasi"). Id >= 1.000.000 tidak dipotong.
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_nomor_6digit(p_id BIGINT UNSIGNED) RETURNS VARCHAR(20)
    DETERMINISTIC NO SQL
BEGIN
    RETURN IF(p_id >= 1000000, CAST(p_id AS CHAR), LPAD(p_id, 6, '0'));
END$$

-- ---------------------------------------------------------------------
-- fn_slot_tersedia(ruangan, tanggal, mulai, selesai, kecuali_id)
--   1 = tidak ada peminjaman 'disetujui'/'berlangsung' yang bertabrakan.
--   draft dan pending TIDAK menahan slot (aturan DDL).
--   kecuali_id = id peminjaman yang diabaikan (dirinya sendiri), boleh NULL.
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_slot_tersedia(
    p_id_ruangan BIGINT UNSIGNED,
    p_tanggal    DATE,
    p_mulai      TIME,
    p_selesai    TIME,
    p_kecuali_id BIGINT UNSIGNED
) RETURNS TINYINT
    READS SQL DATA
BEGIN
    RETURN IF(EXISTS (
        SELECT 1
        FROM peminjaman p
        WHERE p.id_ruangan   = p_id_ruangan
          AND p.tanggal_pakai = p_tanggal
          AND p.status IN ('disetujui','berlangsung')
          AND p.waktu_mulai  < p_selesai
          AND p.waktu_selesai > p_mulai
          AND (p_kecuali_id IS NULL OR p.id <> p_kecuali_id)
    ), 0, 1);
END$$

-- fn_rata_rating(ruangan): rata-rata rating 1 desimal, 0 bila belum ada ulasan.
CREATE FUNCTION fn_rata_rating(p_id_ruangan BIGINT UNSIGNED) RETURNS DECIMAL(2,1)
    READS SQL DATA
BEGIN
    RETURN COALESCE((
        SELECT ROUND(AVG(u.rating), 1)
        FROM v_ulasan_ruangan u
        WHERE u.id_ruangan = p_id_ruangan
    ), 0);
END$$

-- fn_peserta_percakapan(percakapan, mahasiswa): 1 bila mahasiswa adalah salah satu peserta.
CREATE FUNCTION fn_peserta_percakapan(p_id_percakapan BIGINT UNSIGNED, p_id_mahasiswa BIGINT UNSIGNED)
    RETURNS TINYINT
    READS SQL DATA
BEGIN
    RETURN IF(EXISTS (
        SELECT 1 FROM percakapan c
        WHERE c.id = p_id_percakapan
          AND (c.id_mahasiswa_1 = p_id_mahasiswa OR c.id_mahasiswa_2 = p_id_mahasiswa)
    ), 1, 0);
END$$

-- ---------------------------------------------------------------------
-- fn_status_boleh(lama, baru): matriks perpindahan status peminjaman.
--   draft       -> pending, dibatalkan
--   pending     -> revisi, disetujui, ditolak, dibatalkan
--   revisi      -> pending, dibatalkan
--   disetujui   -> berlangsung (check-in), dibatalkan
--   berlangsung -> selesai (check-out)
--   ditolak, selesai, dibatalkan = status akhir
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_status_boleh(p_lama VARCHAR(20), p_baru VARCHAR(20)) RETURNS TINYINT
    DETERMINISTIC NO SQL
BEGIN
    RETURN CASE p_lama
        WHEN 'draft'       THEN p_baru IN ('pending','dibatalkan')
        WHEN 'pending'     THEN p_baru IN ('revisi','disetujui','ditolak','dibatalkan')
        WHEN 'revisi'      THEN p_baru IN ('pending','dibatalkan')
        WHEN 'disetujui'   THEN p_baru IN ('berlangsung','dibatalkan')
        WHEN 'berlangsung' THEN p_baru IN ('selesai')
        ELSE 0
    END;
END$$

-- fn_status_laporan_boleh(lama, baru): draft -> pending -> diproses -> selesai (pending boleh langsung selesai).
CREATE FUNCTION fn_status_laporan_boleh(p_lama VARCHAR(20), p_baru VARCHAR(20)) RETURNS TINYINT
    DETERMINISTIC NO SQL
BEGIN
    RETURN CASE p_lama
        WHEN 'draft'    THEN p_baru IN ('pending')
        WHEN 'pending'  THEN p_baru IN ('diproses','selesai')
        WHEN 'diproses' THEN p_baru IN ('selesai')
        ELSE 0
    END;
END$$

DELIMITER ;
