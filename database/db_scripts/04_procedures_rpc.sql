-- =====================================================================
--  ROOMASTER - 04_procedures_rpc.sql
--  "RPC" di MySQL = stored procedure. Dipanggil dari Laravel dengan
--      DB::select('CALL sp_cari_ruangan(?,?,?,?,?,?,?)', [...]);
--  Jalankan SETELAH 01..03, sebagai root/admin:
--      mysql -u root -p roomaster < 04_procedures_rpc.sql
--
--  PRINSIP
--   * Pengguna yang bertindak = fn_me_wajib() (isi @app_mahasiswa_id), BUKAN
--     parameter. Jadi pemanggil tidak bisa bertindak atas nama mahasiswa lain.
--   * Setiap procedure memeriksa kepemilikan data, lalu mengubah data lewat
--     tabel dasar; trigger (03_triggers.sql) menjaga aturan status, riwayat
--     dan notifikasi.
--   * Hasil dikembalikan sebagai satu result set (DB::select membacanya).
--   * Kesalahan aturan = SIGNAL SQLSTATE '45000' dengan pesan Indonesia.
--
--  DAFTAR (UC)
--   Sesi      : sp_set_sesi, sp_akhiri_sesi
--   UC-01     : sp_daftar_mahasiswa
--   UC-02/03  : sp_cari_ruangan, sp_toggle_favorit
--   UC-08     : sp_jam_terpakai, sp_simpan_draft_peminjaman
--   UC-09/10  : sp_upload_dokumen, sp_hapus_dokumen
--   UC-11     : sp_kirim_pengajuan, sp_batalkan_peminjaman
--   UC-12/13  : sp_check_in, sp_check_out
--   UC-14     : sp_beri_ulasan
--   UC-15/16  : sp_mulai_percakapan, sp_kirim_pesan, sp_buka_percakapan, sp_daftar_percakapan
--   UC-17     : sp_daftar_notifikasi, sp_tandai_notifikasi_dibaca
--   UC-18     : sp_ambil_ruangan_by_qr
--   UC-19/20  : sp_simpan_laporan, sp_tambah_bukti, sp_ringkasan_laporan,
--               sp_bukti_laporan, sp_riwayat_laporan
--   TU/Sarpras: sp_keputusan_peminjaman, sp_update_status_laporan
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

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

DELIMITER $$

-- =====================================================================
--  SESI
-- =====================================================================

-- Dipanggil Laravel sekali per request setelah pengguna terautentikasi.
CREATE PROCEDURE sp_set_sesi(IN p_id_mahasiswa BIGINT UNSIGNED)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM mahasiswa_its WHERE id = p_id_mahasiswa) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mahasiswa tidak ditemukan.';
    END IF;
    SET @app_mahasiswa_id = p_id_mahasiswa;
END$$

-- Dipanggil saat logout / akhir request (penting bila koneksi dipakai ulang).
CREATE PROCEDURE sp_akhiri_sesi()
BEGIN
    SET @app_mahasiswa_id = NULL;
    SET @riwayat_keterangan = NULL;
END$$

-- =====================================================================
--  UC-01  Sign Up: mahasiswa_its + profil_pengguna dalam satu transaksi.
--  p_password_hash = Hash::make($password) dari Laravel (bcrypt), BUKAN teks asli.
--  Tidak butuh sesi (pengguna belum punya akun).
-- =====================================================================
CREATE PROCEDURE sp_daftar_mahasiswa(
    IN p_nrp           VARCHAR(20),
    IN p_nama          VARCHAR(100),
    IN p_email_its     VARCHAR(100),
    IN p_username      VARCHAR(50),
    IN p_password_hash VARCHAR(255)
)
BEGIN
    DECLARE v_id_mhs    BIGINT UNSIGNED;
    DECLARE v_id_profil BIGINT UNSIGNED;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_password_hash IS NULL OR p_password_hash NOT LIKE '$2%' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Password harus dikirim sebagai hash bcrypt.';
    END IF;

    START TRANSACTION;
        INSERT INTO mahasiswa_its (nrp, nama, email_its)
        VALUES (TRIM(p_nrp), TRIM(p_nama), LOWER(TRIM(p_email_its)));
        SET v_id_mhs = LAST_INSERT_ID();

        INSERT INTO profil_pengguna (id_mahasiswa, username, password)
        VALUES (v_id_mhs, TRIM(p_username), p_password_hash);
        SET v_id_profil = LAST_INSERT_ID();
    COMMIT;

    SELECT v_id_mhs AS id_mahasiswa, v_id_profil AS id_profil;
END$$

-- =====================================================================
--  UC-02 / UC-03  Cari ruangan (Explore Rooms)
--  Semua parameter boleh NULL. p_fasilitas = id fasilitas dipisah koma,
--  contoh '1,3' (ruangan harus punya SEMUA). p_tanggal membuang ruangan yang
--  seluruh slot 08-19 sudah terpakai. Satu rentang kapasitas (min-maks).
-- =====================================================================
CREATE PROCEDURE sp_cari_ruangan(
    IN p_q             VARCHAR(100),
    IN p_kap_min       INT UNSIGNED,
    IN p_kap_maks      INT UNSIGNED,
    IN p_kategori      VARCHAR(20),
    IN p_tanggal       DATE,
    IN p_fasilitas     VARCHAR(100),
    IN p_hanya_favorit TINYINT
)
BEGIN
    DECLARE v_fas     VARCHAR(100);
    DECLARE v_jml_fas INT;

    SET v_fas     = REPLACE(COALESCE(p_fasilitas, ''), ' ', '');
    SET v_jml_fas = IF(v_fas = '', 0, CHAR_LENGTH(v_fas) - CHAR_LENGTH(REPLACE(v_fas, ',', '')) + 1);

    SELECT k.*,
           IF(EXISTS (SELECT 1 FROM ruangan_favorit f
                       WHERE f.id_ruangan = k.id AND f.id_mahasiswa = fn_me()), 1, 0) AS favorit
    FROM v_ruangan_kartu k
    WHERE (p_q IS NULL OR p_q = ''
           OR k.nama_ruangan LIKE CONCAT('%', p_q, '%')
           OR k.kode_ruangan LIKE CONCAT('%', p_q, '%'))
      AND (p_kap_min  IS NULL OR k.kapasitas >= p_kap_min)
      AND (p_kap_maks IS NULL OR k.kapasitas <= p_kap_maks)
      AND (p_kategori IS NULL OR p_kategori = '' OR k.kategori = p_kategori)
      AND (v_jml_fas = 0 OR (
              SELECT COUNT(DISTINCT fr.id_fasilitas)
                FROM fasilitas_ruangan fr
               WHERE fr.id_ruangan = k.id
                 AND FIND_IN_SET(fr.id_fasilitas, v_fas) > 0
          ) = v_jml_fas)
      AND (p_tanggal IS NULL OR EXISTS (
              -- masih ada minimal satu jam kosong pada tanggal itu
              SELECT 1 FROM v_jam_operasional j
               WHERE NOT EXISTS (
                     SELECT 1 FROM peminjaman p
                      WHERE p.id_ruangan    = k.id
                        AND p.tanggal_pakai = p_tanggal
                        AND p.status IN ('disetujui','berlangsung')
                        AND p.waktu_mulai   < MAKETIME(j.jam + 1, 0, 0)
                        AND p.waktu_selesai > MAKETIME(j.jam, 0, 0))
          ))
      AND (COALESCE(p_hanya_favorit, 0) = 0 OR EXISTS (
              SELECT 1 FROM ruangan_favorit f2
               WHERE f2.id_ruangan = k.id AND f2.id_mahasiswa = fn_me()))
    ORDER BY k.kode_ruangan;
END$$

-- Tombol hati: simpan bila belum favorit, hapus bila sudah. Hasil: favorit 1/0.
CREATE PROCEDURE sp_toggle_favorit(IN p_id_ruangan BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    IF NOT EXISTS (SELECT 1 FROM data_ruangan WHERE id = p_id_ruangan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ruangan tidak ditemukan.';
    END IF;

    IF EXISTS (SELECT 1 FROM ruangan_favorit WHERE id_mahasiswa = v_me AND id_ruangan = p_id_ruangan) THEN
        DELETE FROM ruangan_favorit WHERE id_mahasiswa = v_me AND id_ruangan = p_id_ruangan;
        SELECT 0 AS favorit;
    ELSE
        INSERT INTO ruangan_favorit (id_mahasiswa, id_ruangan) VALUES (v_me, p_id_ruangan);
        SELECT 1 AS favorit;
    END IF;
END$$

-- =====================================================================
--  UC-08  Pilih jam: daftar jam 08-18 beserta tanda terpakai (1) / kosong (0).
--  Dipakai popup "Choose Time" (prop booked).
-- =====================================================================
CREATE PROCEDURE sp_jam_terpakai(IN p_id_ruangan BIGINT UNSIGNED, IN p_tanggal DATE)
BEGIN
    SELECT j.jam,
           IF(EXISTS (
               SELECT 1 FROM peminjaman p
                WHERE p.id_ruangan    = p_id_ruangan
                  AND p.tanggal_pakai = p_tanggal
                  AND p.status IN ('disetujui','berlangsung')
                  AND p.waktu_mulai   < MAKETIME(j.jam + 1, 0, 0)
                  AND p.waktu_selesai > MAKETIME(j.jam, 0, 0)), 1, 0) AS terpakai
    FROM v_jam_operasional j
    ORDER BY j.jam;
END$$

-- Simpan / perbarui draft formulir peminjaman. p_id_peminjaman NULL = buat baru.
CREATE PROCEDURE sp_simpan_draft_peminjaman(
    IN p_id_peminjaman BIGINT UNSIGNED,
    IN p_id_ruangan    BIGINT UNSIGNED,
    IN p_nama_kegiatan VARCHAR(150),
    IN p_tujuan        TEXT,
    IN p_departemen    VARCHAR(100),
    IN p_tanggal       DATE,
    IN p_mulai         TIME,
    IN p_selesai       TIME
)
BEGIN
    DECLARE v_me      BIGINT UNSIGNED;
    DECLARE v_id      BIGINT UNSIGNED;
    DECLARE v_pemilik BIGINT UNSIGNED;
    DECLARE v_status  VARCHAR(20);

    SET v_me = fn_me_wajib();

    IF p_selesai <= p_mulai THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jam selesai harus setelah jam mulai.';
    END IF;
    IF p_tanggal < CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal tidak boleh sebelum hari ini.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM data_ruangan WHERE id = p_id_ruangan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ruangan tidak ditemukan.';
    END IF;

    IF p_id_peminjaman IS NULL THEN
        INSERT INTO peminjaman (id_mahasiswa, id_ruangan, nama_kegiatan, tujuan, departemen_penyelenggara,
                                tanggal_pakai, waktu_mulai, waktu_selesai, status)
        VALUES (v_me, p_id_ruangan, p_nama_kegiatan, p_tujuan, p_departemen,
                p_tanggal, p_mulai, p_selesai, 'draft');
        SET v_id = LAST_INSERT_ID();
    ELSE
        SELECT id_mahasiswa, status INTO v_pemilik, v_status
          FROM peminjaman WHERE id = p_id_peminjaman;

        IF v_pemilik IS NULL OR v_pemilik <> v_me THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak ditemukan.';
        END IF;
        IF v_status NOT IN ('draft','revisi') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya draft atau revisi yang dapat diubah.';
        END IF;

        UPDATE peminjaman
           SET id_ruangan = p_id_ruangan, nama_kegiatan = p_nama_kegiatan, tujuan = p_tujuan,
               departemen_penyelenggara = p_departemen, tanggal_pakai = p_tanggal,
               waktu_mulai = p_mulai, waktu_selesai = p_selesai
         WHERE id = p_id_peminjaman;
        SET v_id = p_id_peminjaman;
    END IF;

    SELECT v_id AS id_peminjaman;
END$$

-- =====================================================================
--  UC-09 / UC-10  Dokumen pendukung
--  File fisik disimpan Laravel di storage; di sini hanya path-nya.
--  Mengunggah jenis yang sama = mengganti; path lama dikembalikan agar
--  Laravel bisa menghapus file lamanya.
-- =====================================================================
CREATE PROCEDURE sp_upload_dokumen(
    IN p_id_peminjaman BIGINT UNSIGNED,
    IN p_jenis         VARCHAR(20),
    IN p_nama_berkas   VARCHAR(255),
    IN p_path_berkas   VARCHAR(255),
    IN p_ukuran_berkas INT UNSIGNED
)
BEGIN
    DECLARE v_me        BIGINT UNSIGNED;
    DECLARE v_pemilik   BIGINT UNSIGNED;
    DECLARE v_status    VARCHAR(20);
    DECLARE v_lama      BIGINT UNSIGNED;
    DECLARE v_path_lama VARCHAR(255);
    DECLARE v_id        BIGINT UNSIGNED;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SET v_me = fn_me_wajib();

    IF p_jenis NOT IN ('ktm_ktp','surat_kegiatan') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jenis dokumen harus ktm_ktp atau surat_kegiatan.';
    END IF;

    SELECT id_mahasiswa, status INTO v_pemilik, v_status
      FROM peminjaman WHERE id = p_id_peminjaman;

    IF v_pemilik IS NULL OR v_pemilik <> v_me THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak ditemukan.';
    END IF;

    START TRANSACTION;
        SET v_lama = (SELECT MAX(id) FROM dokumen_peminjaman
                       WHERE id_peminjaman = p_id_peminjaman AND jenis_dokumen = p_jenis);
        IF v_lama IS NOT NULL THEN
            SET v_path_lama = (SELECT path_berkas FROM dokumen_peminjaman WHERE id = v_lama);
            DELETE FROM dokumen_peminjaman
             WHERE id_peminjaman = p_id_peminjaman AND jenis_dokumen = p_jenis;
        END IF;

        INSERT INTO dokumen_peminjaman (id_peminjaman, jenis_dokumen, nama_berkas, path_berkas, ukuran_berkas)
        VALUES (p_id_peminjaman, p_jenis, p_nama_berkas, p_path_berkas, p_ukuran_berkas);
        SET v_id = LAST_INSERT_ID();
    COMMIT;

    SELECT v_id AS id_dokumen, v_path_lama AS path_lama;
END$$

-- Hapus satu dokumen milik sendiri. Hasil: path file yang harus dihapus dari storage.
CREATE PROCEDURE sp_hapus_dokumen(IN p_id_dokumen BIGINT UNSIGNED)
BEGIN
    DECLARE v_me      BIGINT UNSIGNED;
    DECLARE v_path    VARCHAR(255);
    DECLARE v_pemilik BIGINT UNSIGNED;

    SET v_me = fn_me_wajib();

    SELECT d.path_berkas, p.id_mahasiswa INTO v_path, v_pemilik
      FROM dokumen_peminjaman d
      JOIN peminjaman p ON p.id = d.id_peminjaman
     WHERE d.id = p_id_dokumen;

    IF v_pemilik IS NULL OR v_pemilik <> v_me THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dokumen tidak ditemukan.';
    END IF;

    DELETE FROM dokumen_peminjaman WHERE id = p_id_dokumen;   -- trigger: hanya saat draft/revisi

    SELECT v_path AS path_berkas;
END$$

-- =====================================================================
--  UC-11  Kirim pengajuan (draft/revisi -> pending) dan batalkan
-- =====================================================================
CREATE PROCEDURE sp_kirim_pengajuan(IN p_id_peminjaman BIGINT UNSIGNED)
BEGIN
    DECLARE v_me      BIGINT UNSIGNED;
    DECLARE v_pemilik BIGINT UNSIGNED;
    DECLARE v_status  VARCHAR(20);
    DECLARE v_ruangan BIGINT UNSIGNED;
    DECLARE v_tanggal DATE;
    DECLARE v_mulai   TIME;
    DECLARE v_selesai TIME;

    SET v_me = fn_me_wajib();

    SELECT id_mahasiswa, status, id_ruangan, tanggal_pakai, waktu_mulai, waktu_selesai
      INTO v_pemilik, v_status, v_ruangan, v_tanggal, v_mulai, v_selesai
      FROM peminjaman WHERE id = p_id_peminjaman;

    IF v_pemilik IS NULL OR v_pemilik <> v_me THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak ditemukan.';
    END IF;
    IF v_status NOT IN ('draft','revisi') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya draft atau revisi yang dapat dikirim.';
    END IF;
    IF v_tanggal < CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal peminjaman sudah lewat.';
    END IF;
    IF (SELECT COUNT(DISTINCT jenis_dokumen) FROM dokumen_peminjaman
         WHERE id_peminjaman = p_id_peminjaman) < 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lengkapi dokumen KTM/KTP dan Surat Peminjaman terlebih dulu.';
    END IF;
    IF fn_slot_tersedia(v_ruangan, v_tanggal, v_mulai, v_selesai, p_id_peminjaman) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Slot ruangan sudah terpakai pada jam tersebut.';
    END IF;

    -- Dokumen yang tadinya diminta revisi dianggap sudah diperbaiki.
    UPDATE dokumen_peminjaman SET status = 'draft'
     WHERE id_peminjaman = p_id_peminjaman AND status IN ('revisi','ditolak');

    UPDATE peminjaman SET status = 'pending' WHERE id = p_id_peminjaman;

    SELECT t.id, fn_nomor_6digit(t.id) AS nomor_booking, t.status, t.tanggal_pengajuan,
           t.kode_ruangan, t.tanggal_pakai, t.waktu_mulai, t.waktu_selesai
      FROM v_peminjaman_tampil t WHERE t.id = p_id_peminjaman;
END$$

CREATE PROCEDURE sp_batalkan_peminjaman(IN p_id_peminjaman BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    DECLARE v_n  INT;

    SET v_me = fn_me_wajib();

    UPDATE peminjaman SET status = 'dibatalkan'
     WHERE id = p_id_peminjaman AND id_mahasiswa = v_me
       AND status IN ('draft','pending','revisi','disetujui');
    SET v_n = ROW_COUNT();

    IF v_n = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak ditemukan atau sudah tidak dapat dibatalkan.';
    END IF;

    SELECT t.id, fn_nomor_6digit(t.id) AS nomor_booking, t.status
      FROM v_peminjaman_tampil t WHERE t.id = p_id_peminjaman;
END$$

-- =====================================================================
--  UC-12 / UC-13  Check-in dan check-out
--  Satu UPDATE atomik: syarat ada di WHERE, jadi tidak ada celah balapan
--  antara "cek" dan "ubah". Foto/catatan/checklist tidak disimpan (aturan DDL).
-- =====================================================================
CREATE PROCEDURE sp_check_in(IN p_id_peminjaman BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    DECLARE v_n  INT;

    SET v_me = fn_me_wajib();

    -- Boleh mulai 1 jam sebelum waktu mulai sampai sebelum waktu selesai.
    UPDATE peminjaman
       SET status = 'berlangsung', waktu_checkin = NOW()
     WHERE id = p_id_peminjaman AND id_mahasiswa = v_me AND status = 'disetujui'
       AND NOW() >= TIMESTAMP(tanggal_pakai, waktu_mulai) - INTERVAL 1 HOUR
       AND NOW() <  TIMESTAMP(tanggal_pakai, waktu_selesai);
    SET v_n = ROW_COUNT();

    IF v_n = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Check-in belum dapat dilakukan: booking harus disetujui dan check-in dibuka 1 jam sebelum mulai.';
    END IF;

    SELECT t.* FROM v_peminjaman_tampil t WHERE t.id = p_id_peminjaman;
END$$

CREATE PROCEDURE sp_check_out(IN p_id_peminjaman BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    DECLARE v_n  INT;

    SET v_me = fn_me_wajib();

    UPDATE peminjaman
       SET status = 'selesai', waktu_checkout = NOW()
     WHERE id = p_id_peminjaman AND id_mahasiswa = v_me AND status = 'berlangsung';
    SET v_n = ROW_COUNT();

    IF v_n = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Check-out tidak dapat dilakukan: peminjaman belum check-in atau sudah selesai.';
    END IF;

    SELECT t.* FROM v_peminjaman_tampil t WHERE t.id = p_id_peminjaman;
END$$

-- =====================================================================
--  UC-14  Review dan rating (1-5). Aturan "setelah waktu booking selesai"
--  dijaga trigger trg_ulasan_bi; satu ulasan per peminjaman dijaga UNIQUE.
-- =====================================================================
CREATE PROCEDURE sp_beri_ulasan(
    IN p_id_peminjaman BIGINT UNSIGNED,
    IN p_rating        TINYINT UNSIGNED,
    IN p_komentar      TEXT
)
BEGIN
    DECLARE v_me      BIGINT UNSIGNED;
    DECLARE v_pemilik BIGINT UNSIGNED;

    SET v_me = fn_me_wajib();

    IF p_rating IS NULL OR p_rating NOT BETWEEN 1 AND 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pilih rating bintang 1 sampai 5.';
    END IF;

    SET v_pemilik = (SELECT id_mahasiswa FROM peminjaman WHERE id = p_id_peminjaman);
    IF v_pemilik IS NULL OR v_pemilik <> v_me THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak ditemukan.';
    END IF;

    IF EXISTS (SELECT 1 FROM ulasan_peminjaman WHERE id_peminjaman = p_id_peminjaman) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman ini sudah diulas.';
    END IF;

    INSERT INTO ulasan_peminjaman (id_peminjaman, rating, komentar)
    VALUES (p_id_peminjaman, p_rating, NULLIF(TRIM(p_komentar), ''));

    SELECT LAST_INSERT_ID() AS id_ulasan;
END$$

-- =====================================================================
--  UC-15 / UC-16  Percakapan dan pesan
-- =====================================================================

-- Buka (atau buat) percakapan dengan mahasiswa lain. Hasil: id percakapan.
-- Pasangan disimpan id kecil di kolom 1 (CHECK ck_percakapan_peserta).
CREATE PROCEDURE sp_mulai_percakapan(IN p_id_lawan BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    DECLARE v_a  BIGINT UNSIGNED;
    DECLARE v_b  BIGINT UNSIGNED;
    DECLARE v_id BIGINT UNSIGNED;

    SET v_me = fn_me_wajib();

    IF p_id_lawan = v_me THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tidak dapat memulai percakapan dengan diri sendiri.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM mahasiswa_its WHERE id = p_id_lawan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lawan bicara tidak ditemukan.';
    END IF;

    SET v_a = LEAST(v_me, p_id_lawan);
    SET v_b = GREATEST(v_me, p_id_lawan);

    SET v_id = (SELECT id FROM percakapan WHERE id_mahasiswa_1 = v_a AND id_mahasiswa_2 = v_b);
    IF v_id IS NULL THEN
        INSERT INTO percakapan (id_mahasiswa_1, id_mahasiswa_2) VALUES (v_a, v_b);
        SET v_id = LAST_INSERT_ID();
    END IF;

    SELECT v_id AS id_percakapan;
END$$

CREATE PROCEDURE sp_kirim_pesan(IN p_id_percakapan BIGINT UNSIGNED, IN p_isi TEXT)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    -- trigger trg_pesan_bi memeriksa peserta dan isi tidak kosong; trg_pesan_ai membuat notifikasi.
    INSERT INTO pesan (id_percakapan, id_pengirim, isi_pesan)
    VALUES (p_id_percakapan, v_me, p_isi);

    SELECT LAST_INSERT_ID() AS id_pesan;
END$$

-- Buka satu percakapan: pesan lawan ditandai sudah dibaca, lalu semua pesan dikembalikan.
CREATE PROCEDURE sp_buka_percakapan(IN p_id_percakapan BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    IF fn_peserta_percakapan(p_id_percakapan, v_me) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Percakapan tidak ditemukan.';
    END IF;

    UPDATE pesan SET sudah_dibaca = 1
     WHERE id_percakapan = p_id_percakapan AND id_pengirim <> v_me AND sudah_dibaca = 0;

    SELECT p.id, p.id_pengirim,
           IF(p.id_pengirim = v_me, 'out', 'in') AS sisi,
           p.isi_pesan, p.waktu_kirim,
           m.nama AS nama_pengirim, m.foto_profil
      FROM pesan p
      JOIN mahasiswa_its m ON m.id = p.id_pengirim
     WHERE p.id_percakapan = p_id_percakapan
     ORDER BY p.waktu_kirim, p.id;
END$$

CREATE PROCEDURE sp_daftar_percakapan()
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    SELECT * FROM v_percakapan_ringkas
     WHERE id_mahasiswa = v_me
     ORDER BY waktu_pesan_terakhir DESC;
END$$

-- =====================================================================
--  UC-17  Notifikasi. p_kategori: peminjaman | pesan | laporan | sistem | NULL (semua).
-- =====================================================================
CREATE PROCEDURE sp_daftar_notifikasi(IN p_kategori VARCHAR(20))
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    SELECT n.id, n.judul, n.isi, n.kategori, n.waktu, n.sudah_dibaca
      FROM notifikasi n
     WHERE n.id_mahasiswa = v_me
       AND (p_kategori IS NULL OR p_kategori = '' OR n.kategori = p_kategori)
     ORDER BY n.waktu DESC, n.id DESC;
END$$

-- p_id NULL = tandai semua notifikasi milik sendiri.
CREATE PROCEDURE sp_tandai_notifikasi_dibaca(IN p_id BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    DECLARE v_n  INT;
    SET v_me = fn_me_wajib();

    UPDATE notifikasi SET sudah_dibaca = 1
     WHERE id_mahasiswa = v_me AND sudah_dibaca = 0 AND (p_id IS NULL OR id = p_id);
    SET v_n = ROW_COUNT();

    SELECT v_n AS diperbarui;
END$$

-- =====================================================================
--  UC-18  Scan QR: isi QR = kode_ruangan (ambilDataRuangan(kode_qr) di Katalog).
-- =====================================================================
CREATE PROCEDURE sp_ambil_ruangan_by_qr(IN p_kode_qr VARCHAR(20))
BEGIN
    IF NOT EXISTS (SELECT 1 FROM data_ruangan WHERE kode_ruangan = TRIM(p_kode_qr)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kode QR tidak dikenali.';
    END IF;

    SELECT * FROM v_ruangan_kartu WHERE kode_ruangan = TRIM(p_kode_qr);
END$$

-- =====================================================================
--  UC-19 / UC-20  Laporan kerusakan
-- =====================================================================

-- p_aksi = 'draft' | 'kirim'. p_id_draft NULL = laporan baru, berisi = lanjutkan draft sendiri.
CREATE PROCEDURE sp_simpan_laporan(
    IN p_id_draft   BIGINT UNSIGNED,
    IN p_id_ruangan BIGINT UNSIGNED,
    IN p_judul      VARCHAR(150),
    IN p_kategori   VARCHAR(20),
    IN p_deskripsi  TEXT,
    IN p_aksi       VARCHAR(10)
)
BEGIN
    DECLARE v_me      BIGINT UNSIGNED;
    DECLARE v_id      BIGINT UNSIGNED;
    DECLARE v_pemilik BIGINT UNSIGNED;
    DECLARE v_status  VARCHAR(20);

    SET v_me = fn_me_wajib();

    IF p_aksi NOT IN ('draft','kirim') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aksi harus draft atau kirim.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM data_ruangan WHERE id = p_id_ruangan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ruangan tidak ditemukan.';
    END IF;

    SET v_status = IF(p_aksi = 'draft', 'draft', 'pending');

    IF p_id_draft IS NULL THEN
        INSERT INTO laporan_kerusakan (id_ruangan, id_mahasiswa, judul, kategori_kerusakan,
                                       deskripsi_masalah, tanggal_lapor, status)
        VALUES (p_id_ruangan, v_me, p_judul, p_kategori, p_deskripsi, NOW(), v_status);
        SET v_id = LAST_INSERT_ID();
    ELSE
        SET v_pemilik = (SELECT id_mahasiswa FROM laporan_kerusakan WHERE id = p_id_draft AND status = 'draft');
        IF v_pemilik IS NULL OR v_pemilik <> v_me THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Draft laporan tidak ditemukan.';
        END IF;

        UPDATE laporan_kerusakan
           SET id_ruangan = p_id_ruangan, judul = p_judul, kategori_kerusakan = p_kategori,
               deskripsi_masalah = p_deskripsi, tanggal_lapor = NOW(), status = v_status
         WHERE id = p_id_draft;
        SET v_id = p_id_draft;
    END IF;

    SELECT v_id AS id_laporan, fn_nomor_6digit(v_id) AS nomor_laporan, v_status AS status;
END$$

CREATE PROCEDURE sp_tambah_bukti(
    IN p_id_laporan   BIGINT UNSIGNED,
    IN p_jenis_media  VARCHAR(10),
    IN p_nama_berkas  VARCHAR(255),
    IN p_path_berkas  VARCHAR(255),
    IN p_ukuran       INT UNSIGNED
)
BEGIN
    DECLARE v_me      BIGINT UNSIGNED;
    DECLARE v_pemilik BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    IF p_jenis_media NOT IN ('foto','video') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jenis media harus foto atau video.';
    END IF;

    SET v_pemilik = (SELECT id_mahasiswa FROM laporan_kerusakan WHERE id = p_id_laporan);
    IF v_pemilik IS NULL OR v_pemilik <> v_me THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Laporan tidak ditemukan.';
    END IF;

    INSERT INTO bukti_laporan (id_laporan, jenis_media, nama_berkas, path_berkas, ukuran_berkas)
    VALUES (p_id_laporan, p_jenis_media, p_nama_berkas, p_path_berkas, p_ukuran);

    SELECT LAST_INSERT_ID() AS id_bukti;
END$$

CREATE PROCEDURE sp_ringkasan_laporan(IN p_id_laporan BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    IF NOT EXISTS (SELECT 1 FROM laporan_kerusakan WHERE id = p_id_laporan AND id_mahasiswa = v_me) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Laporan tidak ditemukan.';
    END IF;

    SELECT * FROM v_laporan_ringkas WHERE id = p_id_laporan;
END$$

CREATE PROCEDURE sp_bukti_laporan(IN p_id_laporan BIGINT UNSIGNED)
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    SELECT b.id, b.jenis_media, b.nama_berkas, b.path_berkas, b.ukuran_berkas, b.waktu_unggah
      FROM bukti_laporan b
      JOIN laporan_kerusakan l ON l.id = b.id_laporan
     WHERE b.id_laporan = p_id_laporan AND l.id_mahasiswa = v_me
     ORDER BY b.id;
END$$

-- p_status: pending | diproses | selesai | draft | NULL (semua).
CREATE PROCEDURE sp_riwayat_laporan(IN p_status VARCHAR(20))
BEGIN
    DECLARE v_me BIGINT UNSIGNED;
    SET v_me = fn_me_wajib();

    SELECT * FROM v_laporan_ringkas
     WHERE id_mahasiswa = v_me
       AND (p_status IS NULL OR p_status = '' OR status = p_status)
     ORDER BY tanggal_lapor DESC, id DESC;
END$$

-- =====================================================================
--  TU / SARPRAS  (dipanggil oleh role TU / Sarpras, bukan oleh app mahasiswa)
-- =====================================================================

-- Keputusan TU atas pengajuan pending. Catatan wajib untuk revisi dan ditolak,
-- opsional untuk disetujui; catatan masuk ke riwayat_peminjaman.keterangan.
CREATE PROCEDURE sp_keputusan_peminjaman(
    IN p_id_peminjaman BIGINT UNSIGNED,
    IN p_keputusan     VARCHAR(10),
    IN p_catatan       VARCHAR(255)
)
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET @riwayat_keterangan = NULL;
        RESIGNAL;
    END;

    IF p_keputusan NOT IN ('disetujui','revisi','ditolak') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Keputusan harus disetujui, revisi, atau ditolak.';
    END IF;
    IF p_keputusan IN ('revisi','ditolak') AND (p_catatan IS NULL OR CHAR_LENGTH(TRIM(p_catatan)) = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Catatan wajib diisi untuk revisi atau penolakan.';
    END IF;

    SET v_status = (SELECT status FROM peminjaman WHERE id = p_id_peminjaman);
    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak ditemukan.';
    END IF;
    IF v_status <> 'pending' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya pengajuan berstatus pending yang dapat diputuskan.';
    END IF;

    SET @riwayat_keterangan = NULLIF(TRIM(p_catatan), '');

    -- Trigger memeriksa slot bentrok saat disetujui, lalu menulis riwayat dan notifikasi.
    UPDATE peminjaman SET status = p_keputusan WHERE id = p_id_peminjaman;

    UPDATE dokumen_peminjaman
       SET status = CASE p_keputusan WHEN 'disetujui' THEN 'diterima' WHEN 'revisi' THEN 'revisi' ELSE 'ditolak' END
     WHERE id_peminjaman = p_id_peminjaman;

    SELECT t.id, fn_nomor_6digit(t.id) AS nomor_booking, t.status
      FROM v_peminjaman_tampil t WHERE t.id = p_id_peminjaman;
END$$

-- Sarpras memajukan status laporan: pending -> diproses -> selesai.
CREATE PROCEDURE sp_update_status_laporan(IN p_id_laporan BIGINT UNSIGNED, IN p_status VARCHAR(20))
BEGIN
    IF NOT EXISTS (SELECT 1 FROM laporan_kerusakan WHERE id = p_id_laporan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Laporan tidak ditemukan.';
    END IF;

    UPDATE laporan_kerusakan SET status = p_status WHERE id = p_id_laporan;   -- trigger memeriksa urutan status

    SELECT * FROM v_laporan_ringkas WHERE id = p_id_laporan;
END$$

DELIMITER ;
