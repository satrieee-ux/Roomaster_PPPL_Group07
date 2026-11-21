-- =====================================================================
--  ROOMASTER - 07_seed_demo.sql
--  DATA DEMO (opsional): mahasiswa + akun login testing, peminjaman di
--  semua status, ulasan (supaya "Recommended" dan rating terisi), favorit,
--  percakapan + pesan, laporan kerusakan + bukti, dan notifikasi.
--  Jalankan SETELAH 01..06:
--      mysql -u root -p roomaster < 07_seed_demo.sql
--
--  CARA KERJA
--   * Peminjaman dibuat sebagai draft lalu dijalankan melalui alur status
--     yang sah (pending -> disetujui -> berlangsung -> selesai, dst.), jadi
--     riwayat_peminjaman dan notifikasi terisi OLEH TRIGGER, sama seperti
--     di aplikasi. Waktu riwayat lalu disesuaikan agar timeline masuk akal.
--   * Tanggal dibuat relatif terhadap hari ini (CURDATE()), jadi aman
--     dijalankan kapan saja. Booking "Upcoming" memakai jam sekarang + 30 menit.
--   * Dijalankan ulang tidak menggandakan data: script berhenti bila
--     mahasiswa dengan NRP 5026241065 sudah ada.
--   * File dokumen/bukti yang dicatat HANYA baris database; filenya tidak
--     ada di storage, jadi tautan unduh akan 404. Untuk demo layar saja.
--
--  AKUN TESTING (profil_pengguna), password semua akun: roomaster123
--   satrio, thoriq, satya, tasia, thalia, dummya, dummyb
--   (HAPUS data demo dan akun ini sebelum produksi.)
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

DROP PROCEDURE IF EXISTS sp_seed_booking;
DROP PROCEDURE IF EXISTS sp_seed_demo;

DELIMITER $$

-- ---------------------------------------------------------------------
-- Pembantu sementara: buat satu peminjaman lengkap sampai status tujuan.
-- p_jml_dokumen: 0, 1, atau 2 (ktm_ktp lalu surat_kegiatan).
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_seed_booking(
    IN  p_nrp         VARCHAR(20),
    IN  p_kode        VARCHAR(20),
    IN  p_kegiatan    VARCHAR(150),
    IN  p_tujuan      TEXT,
    IN  p_dept        VARCHAR(100),
    IN  p_tanggal     DATE,
    IN  p_mulai       TIME,
    IN  p_selesai     TIME,
    IN  p_status      VARCHAR(20),
    IN  p_jml_dokumen INT,
    OUT p_id          BIGINT UNSIGNED
)
BEGIN
    DECLARE v_mhs     BIGINT UNSIGNED;
    DECLARE v_ruangan BIGINT UNSIGNED;
    DECLARE v_dasar   DATETIME;

    SET v_mhs     = (SELECT id FROM mahasiswa_its WHERE nrp = p_nrp);
    SET v_ruangan = (SELECT id FROM data_ruangan  WHERE kode_ruangan = p_kode);

    INSERT INTO peminjaman (id_mahasiswa, id_ruangan, nama_kegiatan, tujuan, departemen_penyelenggara,
                            tanggal_pakai, waktu_mulai, waktu_selesai, status)
    VALUES (v_mhs, v_ruangan, p_kegiatan, p_tujuan, p_dept, p_tanggal, p_mulai, p_selesai, 'draft');
    SET p_id = LAST_INSERT_ID();

    -- Dokumen diunggah selagi masih draft (trigger hanya mengizinkan draft/revisi).
    IF p_jml_dokumen >= 1 THEN
        INSERT INTO dokumen_peminjaman (id_peminjaman, jenis_dokumen, nama_berkas, path_berkas, ukuran_berkas)
        VALUES (p_id, 'ktm_ktp', 'KTM.pdf', CONCAT('dokumen/demo-', p_id, '-ktm.pdf'), 350000);
    END IF;
    IF p_jml_dokumen >= 2 THEN
        INSERT INTO dokumen_peminjaman (id_peminjaman, jenis_dokumen, nama_berkas, path_berkas, ukuran_berkas)
        VALUES (p_id, 'surat_kegiatan', 'Surat Peminjaman.pdf', CONCAT('dokumen/demo-', p_id, '-surat.pdf'), 1250000);
    END IF;

    -- Jalankan alur status yang sah sampai status tujuan.
    IF p_status <> 'draft' THEN
        UPDATE peminjaman SET status = 'pending' WHERE id = p_id;

        IF p_status IN ('revisi','ditolak','dibatalkan') THEN
            UPDATE peminjaman SET status = p_status WHERE id = p_id;
        ELSEIF p_status IN ('disetujui','berlangsung','selesai') THEN
            UPDATE peminjaman SET status = 'disetujui' WHERE id = p_id;
            IF p_status IN ('berlangsung','selesai') THEN
                UPDATE peminjaman SET status = 'berlangsung' WHERE id = p_id;
            END IF;
            IF p_status = 'selesai' THEN
                UPDATE peminjaman SET status = 'selesai' WHERE id = p_id;
            END IF;
        END IF;

        UPDATE dokumen_peminjaman
           SET status = CASE
                   WHEN p_status IN ('disetujui','berlangsung','selesai') THEN 'diterima'
                   WHEN p_status = 'revisi'  THEN 'revisi'
                   WHEN p_status = 'ditolak' THEN 'ditolak'
                   ELSE 'draft' END
         WHERE id_peminjaman = p_id;
    END IF;

    -- Rapikan waktu supaya timeline tidak semuanya "baru saja".
    --   Booking yang sudah lewat: acuan = jam mulai kegiatan. Selain itu: acuan = sekarang.
    IF p_tanggal < CURDATE() THEN
        SET v_dasar = TIMESTAMP(p_tanggal, p_mulai);
    ELSE
        SET v_dasar = NOW();
    END IF;

    UPDATE riwayat_peminjaman
       SET waktu = CASE tahapan
               WHEN 'draft'       THEN v_dasar - INTERVAL 72 HOUR
               WHEN 'pending'     THEN v_dasar - INTERVAL 71 HOUR
               WHEN 'revisi'      THEN v_dasar - INTERVAL 60 HOUR
               WHEN 'ditolak'     THEN v_dasar - INTERVAL 48 HOUR
               WHEN 'dibatalkan'  THEN v_dasar - INTERVAL 40 HOUR
               WHEN 'disetujui'   THEN v_dasar - INTERVAL 48 HOUR
               WHEN 'berlangsung' THEN TIMESTAMP(p_tanggal, p_mulai)
               WHEN 'selesai'     THEN TIMESTAMP(p_tanggal, p_selesai)
               ELSE waktu END
     WHERE id_peminjaman = p_id;

    UPDATE peminjaman
       SET tanggal_pengajuan = IF(p_status = 'draft', NULL, v_dasar - INTERVAL 71 HOUR),
           waktu_checkin     = IF(p_status IN ('berlangsung','selesai'), TIMESTAMP(p_tanggal, p_mulai), NULL),
           waktu_checkout    = IF(p_status = 'selesai', TIMESTAMP(p_tanggal, p_selesai), NULL),
           created_at        = v_dasar - INTERVAL 72 HOUR
     WHERE id = p_id;
END$$

-- ---------------------------------------------------------------------
-- Seluruh data demo
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_seed_demo()
BEGIN
    DECLARE v_hash   VARCHAR(255);
    DECLARE v_now    DATETIME;
    DECLARE v_satrio BIGINT UNSIGNED;
    DECLARE v_thoriq BIGINT UNSIGNED;
    DECLARE v_satya  BIGINT UNSIGNED;
    DECLARE v_tasia  BIGINT UNSIGNED;
    DECLARE v_thalia BIGINT UNSIGNED;
    DECLARE v_da     BIGINT UNSIGNED;
    DECLARE v_db     BIGINT UNSIGNED;
    DECLARE v_id     BIGINT UNSIGNED;
    DECLARE v_b1 BIGINT UNSIGNED; DECLARE v_b2 BIGINT UNSIGNED; DECLARE v_b3 BIGINT UNSIGNED;
    DECLARE v_b4 BIGINT UNSIGNED; DECLARE v_b5 BIGINT UNSIGNED; DECLARE v_b6 BIGINT UNSIGNED;
    DECLARE v_b7 BIGINT UNSIGNED; DECLARE v_b8 BIGINT UNSIGNED;
    DECLARE v_up_tgl     DATE;
    DECLARE v_up_mulai   TIME;
    DECLARE v_up_selesai TIME;
    DECLARE v_c1 BIGINT UNSIGNED; DECLARE v_c2 BIGINT UNSIGNED; DECLARE v_c3 BIGINT UNSIGNED;
    DECLARE v_l  BIGINT UNSIGNED;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF EXISTS (SELECT 1 FROM mahasiswa_its WHERE nrp = '5026241065') THEN
        SELECT 'Seed demo dilewati: data demo sudah ada (NRP 5026241065 ditemukan).' AS info;
    ELSE
        START TRANSACTION;

        SET v_now  = NOW();
        -- bcrypt dari "roomaster123" (cost 10), hanya untuk testing
        SET v_hash = '$2y$10$0MjWRQf9Lx54b1sd1zOYoOCuShPSjbFP22PTQo.xMM8a27tvj6ypG';

        -- ---------------- 1. mahasiswa + akun testing ----------------
        INSERT INTO mahasiswa_its (nrp, nama, email_its, foto_profil) VALUES
            ('5026241065', 'Muhammad Satrio Adjie',    '5026241065@student.its.ac.id', 'images/avatar.jpg'),
            ('5026241124', 'Muhammad Trinanda Thoriq', '5026241124@student.its.ac.id', 'images/avatar.jpg'),
            ('5026241199', 'Satya Azka Aqilla',        '5026241199@student.its.ac.id', 'images/avatar.jpg'),
            ('5026241036', 'Anastasia Azzahra Rafif',  '5026241036@student.its.ac.id', 'images/avatar.jpg'),
            ('5026241201', 'Thalia Harnum Fathina',    '5026241201@student.its.ac.id', 'images/avatar.jpg'),
            ('5026240001', 'Dummy Mahasiswa A',        '5026240001@student.its.ac.id', 'images/avatar.jpg'),
            ('5026240002', 'Dummy Mahasiswa B',        '5026240002@student.its.ac.id', 'images/avatar.jpg');

        SET v_satrio = (SELECT id FROM mahasiswa_its WHERE nrp = '5026241065');
        SET v_thoriq = (SELECT id FROM mahasiswa_its WHERE nrp = '5026241124');
        SET v_satya  = (SELECT id FROM mahasiswa_its WHERE nrp = '5026241199');
        SET v_tasia  = (SELECT id FROM mahasiswa_its WHERE nrp = '5026241036');
        SET v_thalia = (SELECT id FROM mahasiswa_its WHERE nrp = '5026241201');
        SET v_da     = (SELECT id FROM mahasiswa_its WHERE nrp = '5026240001');
        SET v_db     = (SELECT id FROM mahasiswa_its WHERE nrp = '5026240002');

        INSERT INTO profil_pengguna (id_mahasiswa, username, password) VALUES
            (v_satrio, 'satrio', v_hash), (v_thoriq, 'thoriq', v_hash), (v_satya,  'satya',  v_hash),
            (v_tasia,  'tasia',  v_hash), (v_thalia, 'thalia', v_hash), (v_da,     'dummya', v_hash),
            (v_db,     'dummyb', v_hash);

        -- ---------------- 2. peminjaman yang sudah selesai (bahan ulasan) ----------------
        CALL sp_seed_booking('5026241065','SI-4202',  'Rapat Koordinasi PPPL',
             'Rapat koordinasi pembagian tugas proyek PPPL.', 'Departemen Sistem Informasi',
             CURDATE() - INTERVAL 10 DAY, '09:00:00', '11:00:00', 'selesai', 2, v_b1);
        CALL sp_seed_booking('5026241124','SI-4202',  'Belajar Bersama Basis Data',
             'Belajar bersama menjelang ujian basis data.', 'Departemen Sistem Informasi',
             CURDATE() - INTERVAL 7 DAY, '13:00:00', '15:00:00', 'selesai', 2, v_b2);
        CALL sp_seed_booking('5026241201','SKPB-AULA','Seminar Mahasiswa Sistem Informasi',
             'Seminar hasil penelitian mahasiswa.', 'Departemen Sistem Informasi',
             CURDATE() - INTERVAL 14 DAY, '08:00:00', '12:00:00', 'selesai', 2, v_b3);
        CALL sp_seed_booking('5026241036','SI-LAB1',  'Praktikum Tambahan Pemrograman',
             'Praktikum tambahan untuk mahasiswa yang tertinggal.', 'Departemen Sistem Informasi',
             CURDATE() - INTERVAL 5 DAY, '10:00:00', '12:00:00', 'selesai', 2, v_b4);
        CALL sp_seed_booking('5026241199','SKPB-A101','Diskusi Kelompok Proyek',
             'Diskusi kelompok proyek mata kuliah.', 'Departemen Sistem Informasi',
             CURDATE() - INTERVAL 3 DAY, '14:00:00', '16:00:00', 'selesai', 2, v_b5);
        CALL sp_seed_booking('5026240001','SI-4202',  'Latihan Presentasi Tugas Akhir',
             'Latihan presentasi sebelum sidang.', 'Departemen Sistem Informasi',
             CURDATE() - INTERVAL 20 DAY, '15:00:00', '17:00:00', 'selesai', 2, v_b6);
        CALL sp_seed_booking('5026240002','SI-4203',  'Kelas Pengganti Rekayasa Perangkat Lunak',
             'Kelas pengganti yang dijadwalkan ulang.', 'Departemen Sistem Informasi',
             CURDATE() - INTERVAL 9 DAY, '10:00:00', '12:00:00', 'selesai', 2, v_b7);
        CALL sp_seed_booking('5026240001','SKPB-A201','Workshop Desain Antarmuka',
             'Workshop desain antarmuka untuk himpunan.', 'Himpunan Mahasiswa',
             CURDATE() - INTERVAL 12 DAY, '09:00:00', '12:00:00', 'selesai', 2, v_b8);

        -- ---------------- 3. ulasan (rating bulat 1-5) ----------------
        INSERT INTO ulasan_peminjaman (id_peminjaman, rating, komentar, tanggal_ulasan)
        SELECT p.id, x.rating, x.komentar, TIMESTAMP(p.tanggal_pakai, p.waktu_selesai) + INTERVAL 2 HOUR
        FROM (
            SELECT v_b1 AS id, 5 AS rating, 'Ruangan bersih, AC dingin, proyektor langsung menyala.' AS komentar
            UNION ALL SELECT v_b2, 4, 'Nyaman untuk belajar bersama. Wi-Fi sempat lambat di jam siang.'
            UNION ALL SELECT v_b3, 5, 'Aula luas, sound system jernih, panggung tertata rapi.'
            UNION ALL SELECT v_b4, 3, 'Beberapa komputer lambat, selebihnya baik.'
            UNION ALL SELECT v_b5, 4, 'Cukup untuk diskusi kelompok, kursi nyaman.'
            UNION ALL SELECT v_b6, 5, 'Pas untuk latihan presentasi, whiteboard besar.'
            UNION ALL SELECT v_b7, 4, 'Ruangan rapi dan tenang.'
            UNION ALL SELECT v_b8, 5, 'Muat banyak peserta dan proyektor jelas.'
        ) x
        JOIN peminjaman p ON p.id = x.id;

        -- ---------------- 4. peminjaman aktif / mendatang / status lain ----------------
        -- Upcoming (is_upcoming = 1): mulai 30 menit lagi. Setelah jam 22.00 dipindah ke besok pagi.
        IF HOUR(v_now) <= 21 THEN
            SET v_up_tgl     = DATE(v_now + INTERVAL 30 MINUTE);
            SET v_up_mulai   = TIME(v_now + INTERVAL 30 MINUTE);
            SET v_up_selesai = TIME(v_now + INTERVAL 90 MINUTE);
        ELSE
            SET v_up_tgl     = CURDATE() + INTERVAL 1 DAY;
            SET v_up_mulai   = '09:00:00';
            SET v_up_selesai = '11:00:00';
        END IF;
        CALL sp_seed_booking('5026241065','SI-4203',  'Latihan Tim PPPL',
             'Latihan demo aplikasi Roomaster.', 'Departemen Sistem Informasi',
             v_up_tgl, v_up_mulai, v_up_selesai, 'disetujui', 2, v_id);

        CALL sp_seed_booking('5026241065','SKPB-A101','Rapat Himpunan',
             'Rapat bulanan himpunan mahasiswa.', 'Himpunan Mahasiswa',
             CURDATE() + INTERVAL 3 DAY, '13:00:00', '15:00:00', 'disetujui', 2, v_id);

        CALL sp_seed_booking('5026241065','SI-LAB2',  'Pelatihan Laravel',
             'Pelatihan dasar Laravel untuk anggota tim.', 'Departemen Sistem Informasi',
             CURDATE() + INTERVAL 5 DAY, '09:00:00', '12:00:00', 'pending', 2, v_id);

        CALL sp_seed_booking('5026241201','SI-RPT',   'Rapat Internal Tim',
             'Rapat internal pembagian tugas.', 'Departemen Sistem Informasi',
             CURDATE() + INTERVAL 4 DAY, '10:00:00', '11:00:00', 'pending', 2, v_id);

        CALL sp_seed_booking('5026241124','SKPB-DISK','Diskusi Proposal',
             'Diskusi proposal kegiatan.', 'Himpunan Mahasiswa',
             CURDATE() + INTERVAL 6 DAY, '14:00:00', '16:00:00', 'revisi', 1, v_id);
        UPDATE riwayat_peminjaman SET keterangan = 'Mohon lampirkan surat kegiatan yang sudah ditandatangani.'
         WHERE id_peminjaman = v_id AND tahapan = 'revisi';

        CALL sp_seed_booking('5026241036','SKPB-B101','Acara Komunitas',
             'Acara komunitas terbuka untuk umum.', 'Himpunan Mahasiswa',
             CURDATE() + INTERVAL 2 DAY, '08:00:00', '12:00:00', 'ditolak', 2, v_id);
        UPDATE riwayat_peminjaman SET keterangan = 'Jadwal bentrok dengan kegiatan fakultas.'
         WHERE id_peminjaman = v_id AND tahapan = 'ditolak';

        CALL sp_seed_booking('5026241199','IF-1101',  'Kelas Tambahan',
             'Kelas tambahan yang akhirnya dibatalkan.', 'Departemen Teknik Informatika',
             CURDATE() + INTERVAL 8 DAY, '13:00:00', '15:00:00', 'dibatalkan', 2, v_id);

        CALL sp_seed_booking('5026241065','SKPB-LAB', 'Workshop Multimedia',
             'Workshop penyuntingan video.', 'Departemen Sistem Informasi',
             CURDATE() + INTERVAL 10 DAY, '09:00:00', '12:00:00', 'draft', 1, v_id);

        -- Sedang berlangsung (untuk demo Check-Out): mulai 30 menit lalu, selesai 60 menit lagi.
        IF HOUR(v_now) BETWEEN 1 AND 21 THEN
            CALL sp_seed_booking('5026241201','SI-RPT', 'Rapat Pembagian Tugas',
                 'Rapat yang sedang berlangsung.', 'Departemen Sistem Informasi',
                 DATE(v_now), TIME(v_now - INTERVAL 30 MINUTE), TIME(v_now + INTERVAL 60 MINUTE),
                 'berlangsung', 2, v_id);
        END IF;

        -- ---------------- 5. favorit ----------------
        INSERT INTO ruangan_favorit (id_mahasiswa, id_ruangan)
        SELECT m.id, r.id
        FROM (
            SELECT '5026241065' AS nrp, 'SI-4202'   AS kode
            UNION ALL SELECT '5026241065', 'SKPB-AULA'
            UNION ALL SELECT '5026241065', 'SI-LAB1'
            UNION ALL SELECT '5026241201', 'SKPB-A101'
            UNION ALL SELECT '5026241124', 'SI-4202'
        ) x
        JOIN mahasiswa_its m ON m.nrp = x.nrp
        JOIN data_ruangan  r ON r.kode_ruangan = x.kode;

        -- ---------------- 6. percakapan dan pesan (id kecil di kolom 1) ----------------
        INSERT INTO percakapan (id_mahasiswa_1, id_mahasiswa_2, dibuat_pada)
        VALUES (LEAST(v_satrio, v_thalia), GREATEST(v_satrio, v_thalia), v_now - INTERVAL 2 DAY);
        SET v_c1 = LAST_INSERT_ID();
        INSERT INTO percakapan (id_mahasiswa_1, id_mahasiswa_2, dibuat_pada)
        VALUES (LEAST(v_satrio, v_thoriq), GREATEST(v_satrio, v_thoriq), v_now - INTERVAL 1 DAY);
        SET v_c2 = LAST_INSERT_ID();
        INSERT INTO percakapan (id_mahasiswa_1, id_mahasiswa_2, dibuat_pada)
        VALUES (LEAST(v_satrio, v_tasia), GREATEST(v_satrio, v_tasia), v_now - INTERVAL 3 HOUR);
        SET v_c3 = LAST_INSERT_ID();

        INSERT INTO pesan (id_percakapan, id_pengirim, isi_pesan, waktu_kirim, sudah_dibaca) VALUES
            (v_c1, v_satrio, 'Thal, booking rapat hari Kamis sudah diajukan ya.',      v_now - INTERVAL 2 DAY,    1),
            (v_c1, v_thalia, 'Siap, nanti aku cek status pengajuannya.',               v_now - INTERVAL 47 HOUR,  1),
            (v_c1, v_thalia, 'Dokumen suratnya sudah kamu unggah?',                    v_now - INTERVAL 50 MINUTE, 0),
            (v_c2, v_thoriq, 'Jam berapa kita mulai belajar bareng?',                  v_now - INTERVAL 1 DAY,    1),
            (v_c2, v_satrio, 'Jam 1 siang di SI-4202, ya.',                            v_now - INTERVAL 23 HOUR,  1),
            (v_c3, v_tasia,  'Jangan lupa laporan AC di SI-4202 dilampirkan fotonya.', v_now - INTERVAL 20 MINUTE, 0);

        -- ---------------- 7. laporan kerusakan + bukti ----------------
        INSERT INTO laporan_kerusakan (id_ruangan, id_mahasiswa, judul, kategori_kerusakan,
                                       deskripsi_masalah, tanggal_lapor, status)
        VALUES ((SELECT id FROM data_ruangan WHERE kode_ruangan = 'SI-4202'), v_satrio, 'AC tidak dingin', 'ac',
                'AC di sisi kiri ruangan hanya mengeluarkan angin tanpa dingin.', v_now - INTERVAL 1 DAY, 'pending');
        SET v_l = LAST_INSERT_ID();
        INSERT INTO bukti_laporan (id_laporan, jenis_media, nama_berkas, path_berkas, ukuran_berkas)
        VALUES (v_l, 'foto', 'ac.jpg', CONCAT('bukti/demo-', v_l, '-ac.jpg'), 420000);

        INSERT INTO laporan_kerusakan (id_ruangan, id_mahasiswa, judul, kategori_kerusakan,
                                       deskripsi_masalah, tanggal_lapor, status)
        VALUES ((SELECT id FROM data_ruangan WHERE kode_ruangan = 'SI-LAB1'), v_satrio, 'Proyektor tidak menyala', 'proyektor_av',
                'Proyektor tidak menyala meski kabel daya sudah terpasang.', v_now - INTERVAL 3 DAY, 'pending');
        SET v_l = LAST_INSERT_ID();
        INSERT INTO bukti_laporan (id_laporan, jenis_media, nama_berkas, path_berkas, ukuran_berkas)
        VALUES (v_l, 'foto', 'proyektor.jpg', CONCAT('bukti/demo-', v_l, '-proyektor.jpg'), 380000);
        UPDATE laporan_kerusakan SET status = 'diproses' WHERE id = v_l;

        INSERT INTO laporan_kerusakan (id_ruangan, id_mahasiswa, judul, kategori_kerusakan,
                                       deskripsi_masalah, tanggal_lapor, status)
        VALUES ((SELECT id FROM data_ruangan WHERE kode_ruangan = 'SKPB-A101'), v_satrio, 'Lampu berkedip', 'listrik',
                'Dua lampu di bagian belakang berkedip terus.', v_now - INTERVAL 9 DAY, 'pending');
        SET v_l = LAST_INSERT_ID();
        UPDATE laporan_kerusakan SET status = 'diproses' WHERE id = v_l;
        UPDATE laporan_kerusakan SET status = 'selesai'  WHERE id = v_l;

        INSERT INTO laporan_kerusakan (id_ruangan, id_mahasiswa, judul, kategori_kerusakan,
                                       deskripsi_masalah, tanggal_lapor, status)
        VALUES ((SELECT id FROM data_ruangan WHERE kode_ruangan = 'SI-4203'), v_satrio, 'Kursi patah', 'furnitur',
                'Satu kursi di baris depan patah sandarannya.', v_now - INTERVAL 2 HOUR, 'draft');

        INSERT INTO laporan_kerusakan (id_ruangan, id_mahasiswa, judul, kategori_kerusakan,
                                       deskripsi_masalah, tanggal_lapor, status)
        VALUES ((SELECT id FROM data_ruangan WHERE kode_ruangan = 'SKPB-AULA'), v_thalia, 'Wi-Fi sering putus', 'jaringan',
                'Koneksi Wi-Fi putus-putus saat seminar.', v_now - INTERVAL 5 DAY, 'pending');

        -- ---------------- 8. notifikasi sistem ----------------
        INSERT INTO notifikasi (id_mahasiswa, judul, isi, kategori, waktu, sudah_dibaca)
        SELECT m.id, 'Selamat datang di Roomaster',
               'Akun testing Anda sudah aktif. Selamat mencoba meminjam ruangan!', 'sistem',
               v_now - INTERVAL 5 DAY, 1
        FROM mahasiswa_its m
        WHERE m.nrp IN ('5026241065','5026241124','5026241199','5026241036','5026241201','5026240001','5026240002');

        COMMIT;

        SELECT 'Seed demo selesai.' AS info;
    END IF;
END$$

CALL sp_seed_demo()$$

DROP PROCEDURE sp_seed_demo$$
DROP PROCEDURE sp_seed_booking$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- Pemeriksaan hasil
-- ---------------------------------------------------------------------
SELECT status, COUNT(*) AS jumlah FROM peminjaman GROUP BY status ORDER BY jumlah DESC;
SELECT kode_ruangan, jumlah_ulasan, rata_rating, tersedia_sekarang FROM v_ruangan_kartu ORDER BY rata_rating DESC, kode_ruangan;
SELECT id_mahasiswa, notifikasi_belum_dibaca, pesan_belum_dibaca FROM v_badge_mahasiswa;
