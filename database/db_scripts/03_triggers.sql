-- =====================================================================
--  ROOMASTER - 03_triggers.sql
--  Aturan bisnis yang di DDL hanya tertulis sebagai komentar
--  ("dijaga di aplikasi"). Sekarang dijaga juga oleh database.
--  Jalankan SETELAH 01_functions.sql dan 02_views.sql, sebagai root/admin.
--      mysql -u root -p roomaster < 03_triggers.sql
--
--  RINGKASAN
--   peminjaman
--     BEFORE INSERT  : status awal hanya draft/pending; slot tidak boleh bentrok
--     AFTER  INSERT  : tulis riwayat_peminjaman (UC-06)
--     BEFORE UPDATE  : perpindahan status sesuai fn_status_boleh(); slot tidak
--                      boleh bentrok saat disetujui; data dibekukan setelah
--                      dikirim; isi tanggal_pengajuan / waktu_checkin / waktu_checkout
--     AFTER  UPDATE  : tulis riwayat + notifikasi tiap status berubah
--   dokumen_peminjaman : tambah/hapus hanya saat peminjaman draft/revisi
--   ulasan_peminjaman  : hanya setelah waktu booking selesai (UC-14)
--   pesan              : pengirim harus peserta percakapan; notifikasi penerima
--   laporan_kerusakan  : perpindahan status, pembekuan isi, notifikasi
--   bukti_laporan      : hanya saat laporan draft/pending
--
--  PENTING untuk controller Laravel
--   * JANGAN lagi INSERT manual ke riwayat_peminjaman atau notifikasi untuk
--     peristiwa di atas. Trigger sudah menulisnya (kalau dobel, riwayat tampil 2x).
--   * Pelanggaran aturan memunculkan error SQLSTATE 45000 dengan pesan
--     berbahasa Indonesia (di Laravel: QueryException, ambil getMessage()).
--   * Keterangan riwayat bisa diisi lewat  SET @riwayat_keterangan = '...';
--     sebelum UPDATE status (dipakai sp_keputusan_peminjaman untuk catatan TU).
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

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

DELIMITER $$

-- =====================================================================
--  PEMINJAMAN
-- =====================================================================

CREATE TRIGGER trg_peminjaman_bi BEFORE INSERT ON peminjaman
FOR EACH ROW
BEGIN
    -- Peminjaman baru hanya boleh lahir sebagai draft (alur normal) atau pending.
    IF NEW.status NOT IN ('draft','pending') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Peminjaman baru harus berstatus draft atau pending.';
    END IF;

    IF NEW.status = 'pending' AND NEW.tanggal_pengajuan IS NULL THEN
        SET NEW.tanggal_pengajuan = NOW();
    END IF;
END$$

CREATE TRIGGER trg_peminjaman_ai AFTER INSERT ON peminjaman
FOR EACH ROW
BEGIN
    DECLARE v_kode VARCHAR(20);

    INSERT INTO riwayat_peminjaman (id_peminjaman, tahapan, keterangan)
    VALUES (NEW.id, NEW.status,
            IF(NEW.status = 'pending', 'Pengajuan dikirim', 'Draft peminjaman dibuat'));

    IF NEW.status = 'pending' THEN
        SET v_kode = (SELECT kode_ruangan FROM data_ruangan WHERE id = NEW.id_ruangan);
        INSERT INTO notifikasi (id_mahasiswa, judul, isi, kategori)
        VALUES (NEW.id_mahasiswa,
                'Pengajuan terkirim',
                LEFT(CONCAT('Pengajuan peminjaman ', v_kode, ' (', fn_nomor_6digit(NEW.id),
                            ') menunggu keputusan TU.'), 255),
                'peminjaman');
    END IF;
END$$

CREATE TRIGGER trg_peminjaman_bu BEFORE UPDATE ON peminjaman
FOR EACH ROW
BEGIN
    -- 1) Pemilik peminjaman tidak boleh berganti.
    IF NEW.id_mahasiswa <> OLD.id_mahasiswa THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pemilik peminjaman tidak dapat diubah.';
    END IF;

    -- 2) Isi pengajuan dibekukan setelah dikirim (hanya draft dan revisi yang boleh diedit).
    IF OLD.status NOT IN ('draft','revisi') AND (
           NEW.id_ruangan               <> OLD.id_ruangan
        OR NEW.nama_kegiatan            <> OLD.nama_kegiatan
        OR NEW.tujuan                   <> OLD.tujuan
        OR NEW.departemen_penyelenggara <> OLD.departemen_penyelenggara
        OR NEW.tanggal_pakai            <> OLD.tanggal_pakai
        OR NEW.waktu_mulai              <> OLD.waktu_mulai
        OR NEW.waktu_selesai            <> OLD.waktu_selesai
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Data peminjaman tidak dapat diubah setelah dikirim.';
    END IF;

    -- 3) Perpindahan status harus sesuai matriks.
    IF NEW.status <> OLD.status THEN
        IF fn_status_boleh(OLD.status, NEW.status) = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Perpindahan status peminjaman tidak diizinkan.';
        END IF;

        IF NEW.status = 'pending' THEN
            SET NEW.tanggal_pengajuan = NOW();
        END IF;

        IF NEW.status = 'berlangsung' AND NEW.waktu_checkin IS NULL THEN
            SET NEW.waktu_checkin = NOW();
        END IF;

        IF NEW.status = 'selesai' AND NEW.waktu_checkout IS NULL THEN
            SET NEW.waktu_checkout = NOW();
        END IF;
    END IF;

    -- 4) Slot tidak boleh bentrok saat peminjaman menahan slot (disetujui / berlangsung).
    IF NEW.status IN ('disetujui','berlangsung')
       AND (NEW.status <> OLD.status OR NEW.id_ruangan <> OLD.id_ruangan
            OR NEW.tanggal_pakai <> OLD.tanggal_pakai
            OR NEW.waktu_mulai <> OLD.waktu_mulai OR NEW.waktu_selesai <> OLD.waktu_selesai) THEN
        IF fn_slot_tersedia(NEW.id_ruangan, NEW.tanggal_pakai, NEW.waktu_mulai, NEW.waktu_selesai, NEW.id) = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Slot ruangan sudah terpakai oleh peminjaman lain yang disetujui.';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_peminjaman_au AFTER UPDATE ON peminjaman
FOR EACH ROW
BEGIN
    DECLARE v_kode  VARCHAR(20);
    DECLARE v_ket   VARCHAR(255);
    DECLARE v_judul VARCHAR(100);

    IF NEW.status <> OLD.status THEN
        -- Riwayat: satu baris tiap status berubah (DDL tabel riwayat_peminjaman).
        SET v_ket = COALESCE(@riwayat_keterangan, CASE NEW.status
            WHEN 'pending'     THEN 'Pengajuan dikirim'
            WHEN 'revisi'      THEN 'TU meminta revisi'
            WHEN 'disetujui'   THEN 'Pengajuan disetujui TU'
            WHEN 'ditolak'     THEN 'Pengajuan ditolak TU'
            WHEN 'berlangsung' THEN 'Check-in dilakukan'
            WHEN 'selesai'     THEN 'Check-out dilakukan, peminjaman selesai'
            WHEN 'dibatalkan'  THEN 'Peminjaman dibatalkan'
            ELSE NULL END);

        INSERT INTO riwayat_peminjaman (id_peminjaman, tahapan, keterangan)
        VALUES (NEW.id, NEW.status, v_ket);

        -- Notifikasi untuk peminjam (kategori 'peminjaman').
        SET v_kode  = (SELECT kode_ruangan FROM data_ruangan WHERE id = NEW.id_ruangan);
        SET v_judul = CASE NEW.status
            WHEN 'pending'     THEN 'Pengajuan terkirim'
            WHEN 'revisi'      THEN 'Pengajuan perlu revisi'
            WHEN 'disetujui'   THEN 'Pengajuan disetujui'
            WHEN 'ditolak'     THEN 'Pengajuan ditolak'
            WHEN 'berlangsung' THEN 'Check-in berhasil'
            WHEN 'selesai'     THEN 'Peminjaman selesai'
            WHEN 'dibatalkan'  THEN 'Peminjaman dibatalkan'
            ELSE NULL END;

        IF v_judul IS NOT NULL THEN
            INSERT INTO notifikasi (id_mahasiswa, judul, isi, kategori)
            VALUES (NEW.id_mahasiswa, v_judul,
                    LEFT(CONCAT(v_kode, ' (', fn_nomor_6digit(NEW.id), '): ', COALESCE(v_ket, v_judul)), 255),
                    'peminjaman');
        END IF;

        SET @riwayat_keterangan = NULL;
    END IF;
END$$

-- =====================================================================
--  DOKUMEN PEMINJAMAN (UC-09, UC-10)
--  Status dokumen (diterima/revisi/ditolak) diubah TU saat pending, jadi UPDATE tidak dibatasi.
-- =====================================================================

CREATE TRIGGER trg_dokumen_bi BEFORE INSERT ON dokumen_peminjaman
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20);
    SET v_status = (SELECT status FROM peminjaman WHERE id = NEW.id_peminjaman);

    IF v_status IS NOT NULL AND v_status NOT IN ('draft','revisi') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Dokumen hanya dapat diunggah saat peminjaman berstatus draft atau revisi.';
    END IF;
END$$

CREATE TRIGGER trg_dokumen_bd BEFORE DELETE ON dokumen_peminjaman
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20);
    SET v_status = (SELECT status FROM peminjaman WHERE id = OLD.id_peminjaman);

    IF v_status IS NOT NULL AND v_status NOT IN ('draft','revisi') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Dokumen hanya dapat dihapus saat peminjaman berstatus draft atau revisi.';
    END IF;
END$$

-- =====================================================================
--  ULASAN (UC-14)  Aturan DDL: ulasan hanya setelah waktu booking selesai.
--  Satu peminjaman satu ulasan sudah dijaga UNIQUE di DDL.
-- =====================================================================

CREATE TRIGGER trg_ulasan_bi BEFORE INSERT ON ulasan_peminjaman
FOR EACH ROW
BEGIN
    DECLARE v_status  VARCHAR(20);
    DECLARE v_selesai DATETIME;

    SET v_status  = (SELECT status FROM peminjaman WHERE id = NEW.id_peminjaman);
    SET v_selesai = (SELECT TIMESTAMP(tanggal_pakai, waktu_selesai) FROM peminjaman WHERE id = NEW.id_peminjaman);

    IF v_status IS NOT NULL AND (v_status NOT IN ('disetujui','berlangsung','selesai') OR NOW() < v_selesai) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ulasan hanya dapat dibuat setelah waktu peminjaman selesai.';
    END IF;
END$$

-- =====================================================================
--  PERCAKAPAN  (pengganti CHECK ck_percakapan_peserta, yang ditolak MySQL 8
--  karena kolomnya dipakai FK ber-referential action; error 3823)
-- =====================================================================

CREATE TRIGGER trg_percakapan_bi BEFORE INSERT ON percakapan
FOR EACH ROW
BEGIN
    IF NEW.id_mahasiswa_1 >= NEW.id_mahasiswa_2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Peserta percakapan: id_mahasiswa_1 harus lebih kecil dari id_mahasiswa_2.';
    END IF;
END$$

CREATE TRIGGER trg_percakapan_bu BEFORE UPDATE ON percakapan
FOR EACH ROW
BEGIN
    IF NEW.id_mahasiswa_1 >= NEW.id_mahasiswa_2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Peserta percakapan: id_mahasiswa_1 harus lebih kecil dari id_mahasiswa_2.';
    END IF;
END$$

-- =====================================================================
--  PESAN (UC-15)
-- =====================================================================

CREATE TRIGGER trg_pesan_bi BEFORE INSERT ON pesan
FOR EACH ROW
BEGIN
    IF fn_peserta_percakapan(NEW.id_percakapan, NEW.id_pengirim) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pengirim bukan peserta percakapan ini.';
    END IF;

    IF CHAR_LENGTH(TRIM(NEW.isi_pesan)) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Isi pesan tidak boleh kosong.';
    END IF;
END$$

CREATE TRIGGER trg_pesan_ai AFTER INSERT ON pesan
FOR EACH ROW
BEGIN
    DECLARE v_penerima BIGINT UNSIGNED;
    DECLARE v_nama     VARCHAR(100);
    DECLARE v_judul    VARCHAR(100);

    SET v_penerima = (SELECT IF(c.id_mahasiswa_1 = NEW.id_pengirim, c.id_mahasiswa_2, c.id_mahasiswa_1)
                        FROM percakapan c WHERE c.id = NEW.id_percakapan);
    SET v_nama  = (SELECT nama FROM mahasiswa_its WHERE id = NEW.id_pengirim);
    SET v_judul = LEFT(CONCAT('Pesan baru dari ', v_nama), 100);

    -- Satu notifikasi belum dibaca per pengirim, supaya tidak membanjiri daftar notifikasi.
    IF v_penerima IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM notifikasi n
         WHERE n.id_mahasiswa = v_penerima AND n.kategori = 'pesan'
           AND n.sudah_dibaca = 0 AND n.judul = v_judul
    ) THEN
        INSERT INTO notifikasi (id_mahasiswa, judul, isi, kategori)
        VALUES (v_penerima, v_judul, LEFT(NEW.isi_pesan, 255), 'pesan');
    END IF;
END$$

-- =====================================================================
--  LAPORAN KERUSAKAN (UC-19, UC-20)
-- =====================================================================

CREATE TRIGGER trg_laporan_bi BEFORE INSERT ON laporan_kerusakan
FOR EACH ROW
BEGIN
    IF NEW.status NOT IN ('draft','pending') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Laporan baru harus berstatus draft atau pending.';
    END IF;
END$$

CREATE TRIGGER trg_laporan_ai AFTER INSERT ON laporan_kerusakan
FOR EACH ROW
BEGIN
    IF NEW.status = 'pending' THEN
        INSERT INTO notifikasi (id_mahasiswa, judul, isi, kategori)
        VALUES (NEW.id_mahasiswa, 'Laporan diterima',
                LEFT(CONCAT('Laporan ', fn_nomor_6digit(NEW.id), ' "', NEW.judul, '" sudah masuk.'), 255),
                'laporan');
    END IF;
END$$

CREATE TRIGGER trg_laporan_bu BEFORE UPDATE ON laporan_kerusakan
FOR EACH ROW
BEGIN
    IF NEW.id_mahasiswa <> OLD.id_mahasiswa THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelapor tidak dapat diubah.';
    END IF;

    -- Isi laporan dibekukan setelah dikirim.
    IF OLD.status <> 'draft' AND (
           NEW.id_ruangan         <> OLD.id_ruangan
        OR NEW.judul              <> OLD.judul
        OR NEW.kategori_kerusakan <> OLD.kategori_kerusakan
        OR NEW.deskripsi_masalah  <> OLD.deskripsi_masalah
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Isi laporan tidak dapat diubah setelah dikirim.';
    END IF;

    IF NEW.status <> OLD.status AND fn_status_laporan_boleh(OLD.status, NEW.status) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Perpindahan status laporan tidak diizinkan.';
    END IF;
END$$

CREATE TRIGGER trg_laporan_au AFTER UPDATE ON laporan_kerusakan
FOR EACH ROW
BEGIN
    DECLARE v_judul VARCHAR(100);

    IF NEW.status <> OLD.status THEN
        SET v_judul = CASE NEW.status
            WHEN 'pending'  THEN 'Laporan diterima'
            WHEN 'diproses' THEN 'Laporan sedang diproses'
            WHEN 'selesai'  THEN 'Laporan selesai ditangani'
            ELSE NULL END;

        IF v_judul IS NOT NULL THEN
            INSERT INTO notifikasi (id_mahasiswa, judul, isi, kategori)
            VALUES (NEW.id_mahasiswa, v_judul,
                    LEFT(CONCAT('Laporan ', fn_nomor_6digit(NEW.id), ' "', NEW.judul, '"'), 255),
                    'laporan');
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_bukti_bi BEFORE INSERT ON bukti_laporan
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20);
    SET v_status = (SELECT status FROM laporan_kerusakan WHERE id = NEW.id_laporan);

    IF v_status IS NOT NULL AND v_status NOT IN ('draft','pending') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Bukti hanya dapat ditambahkan saat laporan berstatus draft atau pending.';
    END IF;
END$$

DELIMITER ;
