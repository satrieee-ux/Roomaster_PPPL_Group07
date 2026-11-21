-- =====================================================================
--  ROOMASTER - 08_tambahan.sql
--  Tambahan opsional: indeks pendukung, event terjadwal (NONAKTIF
--  secara bawaan), dan pemeriksaan pengaturan server.
--  Jalankan SETELAH 01..04 (sebaiknya setelah 06/07), sebagai root/admin:
--      mysql -u root -p roomaster < 08_tambahan.sql
--  Aman dijalankan ulang.
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

-- ---------------------------------------------------------------------
-- 1. INDEKS PENDUKUNG
--    Dibuat hanya jika belum ada. Catatan: uq_dokumen_jenis akan GAGAL
--    bila sudah ada peminjaman dengan dua dokumen berjenis sama; hapus
--    duplikatnya dulu.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_tmp_indeks;

DELIMITER $$

CREATE PROCEDURE sp_tmp_indeks(IN p_tabel VARCHAR(64), IN p_nama VARCHAR(64), IN p_definisi VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = p_tabel AND index_name = p_nama
    ) THEN
        SET @sql_indeks = CONCAT('ALTER TABLE `', p_tabel, '` ADD ', p_definisi);
        PREPARE stmt FROM @sql_indeks;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Satu dokumen per jenis (ktm_ktp, surat_kegiatan) untuk satu peminjaman; sesuai dua slot di screen.
CALL sp_tmp_indeks('dokumen_peminjaman', 'uq_dokumen_jenis',
    'UNIQUE INDEX uq_dokumen_jenis (id_peminjaman, jenis_dokumen)');

-- Daftar "Your Bookings" per mahasiswa, urut tanggal.
CALL sp_tmp_indeks('peminjaman', 'idx_peminjaman_mahasiswa',
    'INDEX idx_peminjaman_mahasiswa (id_mahasiswa, tanggal_pakai, waktu_mulai)');

-- Badge dan daftar notifikasi per kategori.
CALL sp_tmp_indeks('notifikasi', 'idx_notifikasi_dibaca',
    'INDEX idx_notifikasi_dibaca (id_mahasiswa, sudah_dibaca, kategori)');

-- Hitung pesan belum dibaca per percakapan.
CALL sp_tmp_indeks('pesan', 'idx_pesan_belum_dibaca',
    'INDEX idx_pesan_belum_dibaca (id_percakapan, sudah_dibaca, id_pengirim)');

-- Riwayat laporan per mahasiswa dan status.
CALL sp_tmp_indeks('laporan_kerusakan', 'idx_laporan_mahasiswa',
    'INDEX idx_laporan_mahasiswa (id_mahasiswa, status, tanggal_lapor)');

-- Filter kategori + kapasitas di pencarian ruangan.
CALL sp_tmp_indeks('data_ruangan', 'idx_data_ruangan_kategori',
    'INDEX idx_data_ruangan_kategori (kategori, kapasitas)');

DROP PROCEDURE sp_tmp_indeks;

-- ---------------------------------------------------------------------
-- 2. EVENT TERJADWAL  (NONAKTIF: ini KEBIJAKAN, belum ada di spesifikasi)
--    Aktifkan hanya bila tim sepakat. Syaratnya event scheduler menyala:
--        SET GLOBAL event_scheduler = ON;
--    lalu:  ALTER EVENT ev_batalkan_draft_usang ENABLE;
-- ---------------------------------------------------------------------
DROP EVENT IF EXISTS ev_batalkan_draft_usang;
DROP EVENT IF EXISTS ev_batalkan_tidak_hadir;

-- Draft yang dibiarkan lebih dari 14 hari dibatalkan.
CREATE EVENT ev_batalkan_draft_usang
    ON SCHEDULE EVERY 1 DAY STARTS CURRENT_TIMESTAMP + INTERVAL 1 DAY
    DISABLE
    COMMENT 'Batalkan draft peminjaman yang usang (> 14 hari). Opsional.'
    DO
    UPDATE peminjaman SET status = 'dibatalkan'
     WHERE status = 'draft' AND created_at < NOW() - INTERVAL 14 DAY;

-- Booking disetujui yang tidak pernah check-in sampai waktu selesai dibatalkan,
-- supaya tidak mengotori daftar "Active". (Slot sebenarnya sudah bebas sendiri
-- karena kolom waktu, event ini hanya merapikan status.)
CREATE EVENT ev_batalkan_tidak_hadir
    ON SCHEDULE EVERY 1 HOUR STARTS CURRENT_TIMESTAMP + INTERVAL 1 HOUR
    DISABLE
    COMMENT 'Batalkan booking disetujui yang tidak check-in sampai waktu selesai. Opsional.'
    DO
    UPDATE peminjaman SET status = 'dibatalkan'
     WHERE status = 'disetujui' AND NOW() > TIMESTAMP(tanggal_pakai, waktu_selesai);

-- ---------------------------------------------------------------------
-- 2b. SIMULATOR TU & SARPRAS (AKTIF; hanya untuk demo)
--     Di aplikasi ini tidak ada user TU/Sarpras, jadi pengajuan BARU dari
--     mahasiswa akan berhenti di "pending" selamanya. Simulator ini
--     mewakili mereka: setiap kali dipanggil ia memproses
--       - pengajuan pending yang sudah lebih dari p_menit menit:
--           dokumen kurang dari 2  -> revisi (dengan catatan)
--           slot sudah terpakai    -> ditolak (dengan catatan)
--           lainnya                -> disetujui (dokumen diterima)
--       - laporan pending > p_menit menit  -> diproses
--       - laporan diproses > 2*p_menit     -> selesai
--     Panggil manual:  CALL sp_demo_simulasi_tu_sarpras(2);
--     Otomatis tiap menit lewat event di bawah (sudah AKTIF).
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_demo_simulasi_tu_sarpras;
DROP EVENT IF EXISTS ev_demo_simulasi_tu_sarpras;

DELIMITER $$

CREATE PROCEDURE sp_demo_simulasi_tu_sarpras(IN p_menit INT)
BEGIN
    DECLARE v_habis   INT DEFAULT 0;
    DECLARE v_id      BIGINT UNSIGNED;
    DECLARE v_ruangan BIGINT UNSIGNED;
    DECLARE v_tgl     DATE;
    DECLARE v_mulai   TIME;
    DECLARE v_akhir   TIME;
    DECLARE v_dok     INT;
    DECLARE cur CURSOR FOR
        SELECT id, id_ruangan, tanggal_pakai, waktu_mulai, waktu_selesai
          FROM peminjaman
         WHERE status = 'pending'
           AND tanggal_pengajuan <= NOW() - INTERVAL p_menit MINUTE
         ORDER BY tanggal_pengajuan, id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_habis = 1;

    OPEN cur;
    baca: LOOP
        FETCH cur INTO v_id, v_ruangan, v_tgl, v_mulai, v_akhir;
        IF v_habis = 1 THEN
            LEAVE baca;
        END IF;

        BEGIN
            -- Satu pengajuan yang gagal diproses tidak boleh menghentikan yang lain.
            DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET @riwayat_keterangan = NULL;

            SET v_dok = (SELECT COUNT(*) FROM dokumen_peminjaman WHERE id_peminjaman = v_id);

            IF v_dok < 2 THEN
                SET @riwayat_keterangan = 'Mohon lengkapi KTM/KTP dan surat kegiatan.';
                UPDATE peminjaman SET status = 'revisi' WHERE id = v_id;
            ELSEIF fn_slot_tersedia(v_ruangan, v_tgl, v_mulai, v_akhir, v_id) = 0 THEN
                SET @riwayat_keterangan = 'Jadwal bentrok dengan peminjaman lain.';
                UPDATE peminjaman SET status = 'ditolak' WHERE id = v_id;
            ELSE
                UPDATE peminjaman SET status = 'disetujui' WHERE id = v_id;
                UPDATE dokumen_peminjaman SET status = 'diterima' WHERE id_peminjaman = v_id;
            END IF;
        END;
    END LOOP baca;
    CLOSE cur;

    -- Sarpras: selesai dulu, baru diproses (supaya tidak melompat dua tahap sekaligus).
    UPDATE laporan_kerusakan SET status = 'selesai'
     WHERE status = 'diproses' AND updated_at <= NOW() - INTERVAL (p_menit * 2) MINUTE;
    UPDATE laporan_kerusakan SET status = 'diproses'
     WHERE status = 'pending'  AND updated_at <= NOW() - INTERVAL p_menit MINUTE;
END$$

DELIMITER ;

-- Penjadwal event harus menyala agar simulator jalan (butuh hak SUPER, mis. root).
-- Agar tetap menyala setelah restart, tambahkan di my.ini bagian [mysqld]:  event_scheduler = ON
SET GLOBAL event_scheduler = ON;

-- AKTIF secara bawaan: pengajuan mahasiswa diproses otomatis supaya alur demo bisa selesai.
-- Matikan:  ALTER EVENT ev_demo_simulasi_tu_sarpras DISABLE;
CREATE EVENT ev_demo_simulasi_tu_sarpras
    ON SCHEDULE EVERY 1 MINUTE STARTS CURRENT_TIMESTAMP + INTERVAL 1 MINUTE
    ENABLE
    COMMENT 'Demo: mewakili TU/Sarpras (setujui pengajuan, proses laporan). Matikan di produksi.'
    DO CALL sp_demo_simulasi_tu_sarpras(1);

-- ---------------------------------------------------------------------
-- 3. PEMERIKSAAN PENGATURAN SERVER (hanya menampilkan, tidak mengubah)
--    Semua view memakai NOW(): zona waktu server harus Asia/Jakarta, mis.
--        SET GLOBAL time_zone = '+07:00';
--    (tambahkan juga ke my.cnf agar tidak hilang saat restart)
-- ---------------------------------------------------------------------
SELECT @@global.time_zone       AS zona_waktu_global,
       @@session.time_zone      AS zona_waktu_sesi,
       NOW()                    AS waktu_server,
       @@version                AS versi_mysql,
       @@event_scheduler        AS event_scheduler,
       @@log_bin_trust_function_creators AS trust_function_creators;

-- Daftar objek yang dibuat file-file ini (untuk pemeriksaan cepat)
SELECT routine_type, COUNT(*) AS jumlah
  FROM information_schema.routines WHERE routine_schema = 'roomaster' GROUP BY routine_type;
SELECT COUNT(*) AS jumlah_trigger FROM information_schema.triggers WHERE trigger_schema = 'roomaster';
SELECT COUNT(*) AS jumlah_view    FROM information_schema.views    WHERE table_schema   = 'roomaster';
