-- =====================================================================
--  ROOMASTER - Skema Database MySQL  (DOKUMENTASI)
--  Kelompok 7. Target: MySQL 8.0.16+ (CHECK constraint aktif), InnoDB, utf8mb4.
--  Konvensi: primary key = id, foreign key = id_<tabel>, snake_case.
--  Kolom created_at / updated_at disediakan agar cocok dengan Eloquent Laravel.
--  File ini TIDAK menghapus database. Untuk menimpa database lama, pakai
--  database/roomaster_reset.sql (isi skema sama persis).
--
--  RIWAYAT PERUBAHAN (dibanding skema awal)
--   + data_ruangan.deskripsi             deskripsi ruangan
--   + data_ruangan.id_pic                FK ke informasi_tu (PIC / kontak CS)
--   - data_ruangan.status                dihapus; 'Now Available' dihitung dari peminjaman
--   + informasi_tu                       tabel baru, kontak TU (dummy, bukan akun)
--   + view v_ketersediaan_ruangan        Now Available (disetujui/berlangsung)
--   + view v_peminjaman_tampil           nama tamu, ruangan, is_upcoming (1 jam sebelum mulai)
--   + view v_ulasan_ruangan              ulasan per ruangan, Top Reviews = rating tertinggi
--   + idx_ulasan_rating                  indeks pendukung Top Reviews
--   - laporan_kerusakan.nomor_laporan    dihapus; nomor laporan = id (incremental)
--   + laporan_kerusakan.judul            judul laporan
--   + bukti_laporan                      tabel baru, bukti foto/video laporan
--   ID booking = peminjaman.id (incremental, tampil 000001 di aplikasi)
--
--  KEPUTUSAN YANG DIJAGA DI APLIKASI / TIDAK DI DDL
--   * Slot terpakai hanya jika status 'disetujui' atau 'berlangsung'.
--     'draft' dan 'pending' tidak menahan slot; DDL tidak mencegah dua
--     pengajuan pending di slot yang sama.
--   * Ulasan hanya boleh dibuat setelah waktu booking selesai.
--   * Foto, catatan, dan checklist check-in/check-out tidak disimpan;
--     check-in/out hanya mengisi peminjaman.waktu_checkin / waktu_checkout.
--   * Timeline laporan di screen statis (tidak ada tabel riwayat laporan).
--   * Tidak ada kolom prioritas laporan. Quick Book tidak ada di DDL.
--   * Rating bilangan bulat 1-5. percakapan masih antar-mahasiswa_its.
--   * Tabel tanpa perubahan mengikuti skema awal.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS roomaster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE roomaster;

-- ---------------------------------------------------------------------
-- 1. mahasiswa_its
--    Data identitas dari MyITS SSO (UC-01) dan foto_profil.
--    Tidak menyimpan password. Login testing ada di bagian terakhir
--    (profil_pengguna).
-- ---------------------------------------------------------------------
CREATE TABLE mahasiswa_its (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nrp         VARCHAR(20)     NOT NULL,              -- teks, NRP bisa diawali nol
    nama        VARCHAR(100)    NOT NULL,
    email_its   VARCHAR(100)    NOT NULL,              -- dicocokkan saat callback MyITS
    foto_profil VARCHAR(255)    NULL,                  -- path relatif di storage, contoh: profil/a1b2c3.jpg
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_mahasiswa_its PRIMARY KEY (id),
    CONSTRAINT uq_mahasiswa_its_nrp UNIQUE (nrp),
    CONSTRAINT uq_mahasiswa_its_email UNIQUE (email_its)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. informasi_tu  (BARU)
--    Kontak TU yang menjadi PIC ruangan (tampil sebagai "CS Name" dan
--    "Response Time" di Room Detail). Dipakai sebagai FK data_ruangan.id_pic.
--    Tabel ini hanya data kontak: BUKAN akun, tidak dipakai untuk login
--    atau persetujuan. Isinya dummy.
-- ---------------------------------------------------------------------
CREATE TABLE informasi_tu (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama                VARCHAR(100)    NOT NULL,
    email               VARCHAR(100)    NOT NULL,
    foto_profil         VARCHAR(255)    NULL,              -- path relatif di storage
    rata_respon_menit   INT UNSIGNED    NULL,              -- rata-rata waktu respon dalam menit, contoh 120 = "~2 Hours"
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_informasi_tu PRIMARY KEY (id),
    CONSTRAINT uq_informasi_tu_email UNIQUE (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. data_ruangan
--    Isi QR di pintu ruangan = kode_ruangan (UC-18).
--    Kolom status DIHAPUS: "Now Available" dihitung dari tabel peminjaman
--    lewat view v_ketersediaan_ruangan (bagian 16).
--    Perubahan: + deskripsi, + id_pic (FK ke informasi_tu).
-- ---------------------------------------------------------------------
CREATE TABLE data_ruangan (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_ruangan    VARCHAR(20)     NOT NULL,
    nama_ruangan    VARCHAR(100)    NOT NULL,
    kategori        ENUM('skpb','departemen') NOT NULL,             -- tab dashboard UC-02
    tipe_ruangan    VARCHAR(50)     NOT NULL,                       -- kelas, laboratorium, aula, ruang rapat, ...
    kapasitas       INT UNSIGNED    NOT NULL,
    lokasi          VARCHAR(255)    NOT NULL,                       -- gedung dan lantai
    deskripsi       TEXT            NULL,                           -- paragraf "Description" di Room Detail
    foto_ruangan    VARCHAR(255)    NULL,                           -- path relatif di storage, contoh: ruangan/x9y8.jpg
    id_pic          BIGINT UNSIGNED NULL,                           -- PIC ruangan (kontak TU)
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_data_ruangan PRIMARY KEY (id),
    CONSTRAINT uq_data_ruangan_kode UNIQUE (kode_ruangan),
    CONSTRAINT ck_data_ruangan_kapasitas CHECK (kapasitas > 0),
    CONSTRAINT fk_data_ruangan_pic FOREIGN KEY (id_pic)
        REFERENCES informasi_tu (id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_data_ruangan_nama (nama_ruangan)                      -- searchByName (UC-03)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. fasilitas
--    Daftar fasilitas yang dapat dipilih di Pop up Filter (UC-03).
-- ---------------------------------------------------------------------
CREATE TABLE fasilitas (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_fasilitas  VARCHAR(50)     NOT NULL,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_fasilitas PRIMARY KEY (id),
    CONSTRAINT uq_fasilitas_nama UNIQUE (nama_fasilitas)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. fasilitas_ruangan  (tabel penghubung banyak-ke-banyak)
-- ---------------------------------------------------------------------
CREATE TABLE fasilitas_ruangan (
    id_ruangan      BIGINT UNSIGNED NOT NULL,
    id_fasilitas    BIGINT UNSIGNED NOT NULL,
    CONSTRAINT pk_fasilitas_ruangan PRIMARY KEY (id_ruangan, id_fasilitas),
    CONSTRAINT fk_fasilitas_ruangan_ruangan FOREIGN KEY (id_ruangan)
        REFERENCES data_ruangan (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_fasilitas_ruangan_fasilitas FOREIGN KEY (id_fasilitas)
        REFERENCES fasilitas (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. ruangan_favorit  (filterByFavorites, UC-02)
-- ---------------------------------------------------------------------
CREATE TABLE ruangan_favorit (
    id_mahasiswa    BIGINT UNSIGNED NOT NULL,
    id_ruangan      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_ruangan_favorit PRIMARY KEY (id_mahasiswa, id_ruangan),
    CONSTRAINT fk_ruangan_favorit_mahasiswa FOREIGN KEY (id_mahasiswa)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ruangan_favorit_ruangan FOREIGN KEY (id_ruangan)
        REFERENCES data_ruangan (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. peminjaman
--    ID booking = kolom id (auto increment, mulai 1). Tampilan 6 digit
--    (000001) dibuat di aplikasi, mis. str_pad($id, 6, '0', STR_PAD_LEFT).
--    Status:
--      draft      = pengguna belum menyelesaikan pengisian lalu keluar.
--      pending    = sudah dikirim, menunggu keputusan TU.
--      revisi, disetujui, ditolak = keputusan TU.
--      berlangsung, selesai, dibatalkan.
--    Slot dianggap TERPAKAI hanya jika status 'disetujui' atau 'berlangsung'.
--    draft dan pending TIDAK menahan slot.
--    Check-in / check-out: cukup mengisi waktu_checkin / waktu_checkout.
--    Nama tamu dan ruangan diambil lewat JOIN (lihat v_peminjaman_tampil).
--    Foto, catatan, dan checklist check-in/out TIDAK disimpan.
--    "Upcoming" tidak disimpan sebagai status (lihat v_peminjaman_tampil).
-- ---------------------------------------------------------------------
CREATE TABLE peminjaman (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_mahasiswa                BIGINT UNSIGNED NOT NULL,
    id_ruangan                  BIGINT UNSIGNED NOT NULL,
    nama_kegiatan               VARCHAR(150)    NOT NULL,
    tujuan                      TEXT            NOT NULL,
    departemen_penyelenggara    VARCHAR(100)    NOT NULL,
    tanggal_pakai               DATE            NOT NULL,
    waktu_mulai                 TIME            NOT NULL,
    waktu_selesai               TIME            NOT NULL,
    status  ENUM('draft','pending','revisi','disetujui','ditolak',
                 'berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'draft',
    tanggal_pengajuan           DATETIME        NULL,          -- diisi saat dikirim (UC-11)
    waktu_checkin               DATETIME        NULL,          -- diisi oleh check-in (UC-12)
    waktu_checkout              DATETIME        NULL,          -- diisi oleh check-out (UC-13)
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_peminjaman PRIMARY KEY (id),
    CONSTRAINT fk_peminjaman_mahasiswa FOREIGN KEY (id_mahasiswa)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_peminjaman_ruangan FOREIGN KEY (id_ruangan)
        REFERENCES data_ruangan (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_peminjaman_waktu CHECK (waktu_selesai > waktu_mulai),
    CONSTRAINT ck_peminjaman_checkout CHECK (waktu_checkout IS NULL OR waktu_checkin IS NOT NULL),
    INDEX idx_peminjaman_jadwal (id_ruangan, tanggal_pakai, waktu_mulai, waktu_selesai),  -- cekKetersediaan / searchByDate
    INDEX idx_peminjaman_status (status, tanggal_pengajuan)                                -- antrean dan filter UC-07
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. dokumen_peminjaman  (UC-09, UC-10)
--    File disimpan di storage Laravel, database hanya menyimpan path relatif.
-- ---------------------------------------------------------------------
CREATE TABLE dokumen_peminjaman (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_peminjaman   BIGINT UNSIGNED NOT NULL,
    jenis_dokumen   ENUM('ktm_ktp','surat_kegiatan') NOT NULL,   -- surat_kegiatan = "Surat Peminjaman" di screen
    nama_berkas     VARCHAR(255)    NOT NULL,          -- nama asli file dari pengguna, hanya untuk ditampilkan
    path_berkas     VARCHAR(255)    NOT NULL,          -- path relatif di storage, contoh: dokumen/8f3k2a.pdf
    ukuran_berkas   INT UNSIGNED    NOT NULL,          -- dalam byte
    status          ENUM('draft','diterima','revisi','ditolak') NOT NULL DEFAULT 'draft',
    waktu_unggah    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_dokumen_peminjaman PRIMARY KEY (id),
    CONSTRAINT uq_dokumen_peminjaman_path UNIQUE (path_berkas),
    CONSTRAINT fk_dokumen_peminjaman_peminjaman FOREIGN KEY (id_peminjaman)
        REFERENCES peminjaman (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. riwayat_peminjaman  (UC-06)
--    Satu baris setiap kali status peminjaman berubah.
-- ---------------------------------------------------------------------
CREATE TABLE riwayat_peminjaman (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_peminjaman   BIGINT UNSIGNED NOT NULL,
    tahapan  ENUM('draft','pending','revisi','disetujui','ditolak',
                  'berlangsung','selesai','dibatalkan') NOT NULL,   -- sama dengan peminjaman.status
    keterangan      VARCHAR(255)    NULL,
    waktu           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_riwayat_peminjaman PRIMARY KEY (id),
    CONSTRAINT fk_riwayat_peminjaman_peminjaman FOREIGN KEY (id_peminjaman)
        REFERENCES peminjaman (id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_riwayat_peminjaman_waktu (id_peminjaman, waktu)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. ulasan_peminjaman  (UC-05, UC-14)
--     Satu peminjaman hanya bisa diulas sekali. Penulis ulasan diambil
--     lewat peminjaman.id_mahasiswa; ruangan lewat peminjaman.id_ruangan.
--     ATURAN (dijaga di aplikasi, bukan di DDL): ulasan hanya boleh dibuat
--     setelah waktu booking selesai.
--     "Top Reviews" = rating tertinggi (urut rating DESC, lalu tanggal_ulasan
--     DESC), tersedia lewat view v_ulasan_ruangan dan indeks idx_ulasan_rating.
--     Rating bilangan bulat 1-5 (tidak ada setengah bintang).
-- ---------------------------------------------------------------------
CREATE TABLE ulasan_peminjaman (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_peminjaman   BIGINT UNSIGNED NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL,
    komentar        TEXT            NULL,
    tanggal_ulasan  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_ulasan_peminjaman PRIMARY KEY (id),
    CONSTRAINT uq_ulasan_peminjaman_peminjaman UNIQUE (id_peminjaman),   -- relasi 1 ke 0..1
    CONSTRAINT fk_ulasan_peminjaman_peminjaman FOREIGN KEY (id_peminjaman)
        REFERENCES peminjaman (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT ck_ulasan_peminjaman_rating CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_ulasan_rating (rating, tanggal_ulasan)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. percakapan  (UC-16)
--     Pesan terakhir dan jumlah belum dibaca dihitung dari tabel pesan.
--     Peserta saat ini dua mahasiswa_its. Jika lawan bicara ternyata
--     admin/pengelola, FK id_mahasiswa_2 perlu diganti.
-- ---------------------------------------------------------------------
CREATE TABLE percakapan (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_mahasiswa_1  BIGINT UNSIGNED NOT NULL,
    id_mahasiswa_2  BIGINT UNSIGNED NOT NULL,
    dibuat_pada     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_percakapan PRIMARY KEY (id),
    CONSTRAINT uq_percakapan_peserta UNIQUE (id_mahasiswa_1, id_mahasiswa_2),
    CONSTRAINT fk_percakapan_mahasiswa_1 FOREIGN KEY (id_mahasiswa_1)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_percakapan_mahasiswa_2 FOREIGN KEY (id_mahasiswa_2)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE CASCADE
    -- Aturan "id kecil di kolom 1" (id_mahasiswa_1 < id_mahasiswa_2, cegah pasangan dobel/terbalik)
    -- TIDAK bisa berupa CHECK: MySQL 8 menolak CHECK pada kolom yang ikut FK dengan
    -- referential action (error 3823). Dijaga oleh trigger trg_percakapan_bi/bu di 03_triggers.sql.
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. pesan  (UC-15)
--     Penerima = peserta lain di percakapan yang sama.
-- ---------------------------------------------------------------------
CREATE TABLE pesan (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_percakapan   BIGINT UNSIGNED NOT NULL,
    id_pengirim     BIGINT UNSIGNED NOT NULL,
    isi_pesan       TEXT            NOT NULL,
    waktu_kirim     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sudah_dibaca    BOOLEAN         NOT NULL DEFAULT FALSE,
    CONSTRAINT pk_pesan PRIMARY KEY (id),
    CONSTRAINT fk_pesan_percakapan FOREIGN KEY (id_percakapan)
        REFERENCES percakapan (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pesan_pengirim FOREIGN KEY (id_pengirim)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_pesan_percakapan_waktu (id_percakapan, waktu_kirim)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. notifikasi  (UC-17)
-- ---------------------------------------------------------------------
CREATE TABLE notifikasi (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_mahasiswa    BIGINT UNSIGNED NOT NULL,
    judul           VARCHAR(100)    NOT NULL,
    isi             VARCHAR(255)    NOT NULL,
    kategori        ENUM('peminjaman','pesan','laporan','sistem') NOT NULL,
    waktu           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sudah_dibaca    BOOLEAN         NOT NULL DEFAULT FALSE,
    CONSTRAINT pk_notifikasi PRIMARY KEY (id),
    CONSTRAINT fk_notifikasi_mahasiswa FOREIGN KEY (id_mahasiswa)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_notifikasi_mahasiswa_waktu (id_mahasiswa, waktu)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 14. laporan_kerusakan  (UC-18, UC-19, UC-20)
--     Nomor laporan = kolom id (auto increment, mulai 1). Kolom
--     nomor_laporan DIHAPUS. Tampilan 6 digit dibuat di aplikasi.
--     Perubahan: + judul (judul yang diisi saat laporan dikirim).
--     Tidak ada kolom prioritas. Timeline laporan di screen statis,
--     tidak disimpan (tidak ada tabel riwayat laporan).
-- ---------------------------------------------------------------------
CREATE TABLE laporan_kerusakan (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_ruangan          BIGINT UNSIGNED NOT NULL,
    id_mahasiswa        BIGINT UNSIGNED NOT NULL,
    judul               VARCHAR(150)    NOT NULL,
    kategori_kerusakan  ENUM('listrik','ac','furnitur','proyektor_av',
                             'jaringan','kebersihan','lainnya') NOT NULL,
    deskripsi_masalah   TEXT            NOT NULL,
    tanggal_lapor       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status              ENUM('draft','pending','diproses','selesai') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_laporan_kerusakan PRIMARY KEY (id),
    CONSTRAINT fk_laporan_kerusakan_ruangan FOREIGN KEY (id_ruangan)
        REFERENCES data_ruangan (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laporan_kerusakan_mahasiswa FOREIGN KEY (id_mahasiswa)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_laporan_kerusakan_status (status, tanggal_lapor)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 15. bukti_laporan  (BARU)
--     Bukti foto/video kerusakan, disimpan sebagai bagian dari laporan
--     sehingga ikut tersimpan di histori laporan.
--     File di storage Laravel, database hanya menyimpan path relatif.
-- ---------------------------------------------------------------------
CREATE TABLE bukti_laporan (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_laporan      BIGINT UNSIGNED NOT NULL,
    jenis_media     ENUM('foto','video') NOT NULL DEFAULT 'foto',
    nama_berkas     VARCHAR(255)    NOT NULL,          -- nama asli file dari pengguna
    path_berkas     VARCHAR(255)    NOT NULL,          -- path relatif di storage, contoh: bukti/ab12cd.jpg
    ukuran_berkas   INT UNSIGNED    NOT NULL,          -- dalam byte
    waktu_unggah    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_bukti_laporan PRIMARY KEY (id),
    CONSTRAINT uq_bukti_laporan_path UNIQUE (path_berkas),
    CONSTRAINT fk_bukti_laporan_laporan FOREIGN KEY (id_laporan)
        REFERENCES laporan_kerusakan (id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_bukti_laporan_laporan (id_laporan)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 16. VIEW turunan  (BARU)
--     Memakai waktu server MySQL (NOW()). Pastikan zona waktu server
--     Asia/Jakarta, misalnya: SET GLOBAL time_zone = '+07:00';
-- ---------------------------------------------------------------------

-- 16a. "Now Available": tersedia_sekarang = 1 jika TIDAK ada peminjaman
--      disetujui/berlangsung yang mencakup waktu saat ini.
CREATE VIEW v_ketersediaan_ruangan AS
SELECT
    r.id            AS id_ruangan,
    r.kode_ruangan,
    r.nama_ruangan,
    CASE WHEN EXISTS (
        SELECT 1
        FROM peminjaman p
        WHERE p.id_ruangan = r.id
          AND p.status IN ('disetujui','berlangsung')
          AND NOW() >= TIMESTAMP(p.tanggal_pakai, p.waktu_mulai)
          AND NOW() <  TIMESTAMP(p.tanggal_pakai, p.waktu_selesai)
    ) THEN 0 ELSE 1 END AS tersedia_sekarang
FROM data_ruangan r;

-- 16b. Peminjaman lengkap untuk daftar/kartu/check-in. Menyertakan nama
--      tamu, ruangan, dan is_upcoming (turunan, bukan status tersimpan):
--      is_upcoming = 1 jika status 'disetujui' dan waktu sekarang berada
--      dalam 1 jam sebelum waktu mulai, sampai waktu mulai.
CREATE VIEW v_peminjaman_tampil AS
SELECT
    p.id,
    p.id_mahasiswa,
    m.nama          AS nama_tamu,
    p.id_ruangan,
    r.kode_ruangan,
    r.nama_ruangan,
    p.nama_kegiatan,
    p.tujuan,
    p.departemen_penyelenggara,
    p.tanggal_pakai,
    p.waktu_mulai,
    p.waktu_selesai,
    p.status,
    p.tanggal_pengajuan,
    p.waktu_checkin,
    p.waktu_checkout,
    CASE WHEN p.status = 'disetujui'
          AND NOW() >= TIMESTAMP(p.tanggal_pakai, p.waktu_mulai) - INTERVAL 1 HOUR
          AND NOW() <  TIMESTAMP(p.tanggal_pakai, p.waktu_mulai)
         THEN 1 ELSE 0 END AS is_upcoming
FROM peminjaman p
JOIN mahasiswa_its m ON m.id = p.id_mahasiswa
JOIN data_ruangan  r ON r.id = p.id_ruangan;

-- 16c. Ulasan per ruangan. Untuk "Top Reviews" cukup:
--      SELECT * FROM v_ulasan_ruangan WHERE id_ruangan = ?
--      ORDER BY rating DESC, tanggal_ulasan DESC LIMIT 3;
CREATE VIEW v_ulasan_ruangan AS
SELECT
    u.id            AS id_ulasan,
    p.id_ruangan,
    p.id_mahasiswa,
    m.nama          AS nama_pengulas,
    m.foto_profil,
    u.rating,
    u.komentar,
    u.tanggal_ulasan
FROM ulasan_peminjaman u
JOIN peminjaman    p ON p.id = u.id_peminjaman
JOIN mahasiswa_its m ON m.id = p.id_mahasiswa;


-- #####################################################################
-- #####################################################################
--  17. profil_pengguna
--      ===== Hanya untuk testing tanpa myitssso =====
--
--  SEMUA hal untuk login tanpa MyITS SSO dikumpulkan di bagian ini saja.
--  Tabel lain (termasuk mahasiswa_its) tidak berisi apa pun untuk testing.
--
--  Cara kerja login testing:
--    1. Cari baris profil_pengguna berdasarkan username.
--    2. Cocokkan password (hash bcrypt) dengan Hash::check() di Laravel.
--    3. Ambil identitas mahasiswa lewat id_mahasiswa -> mahasiswa_its.
--
--  Saat MyITS SSO sudah disambungkan, hapus seluruh bagian ini:
--      DROP TABLE profil_pengguna;
--  Tidak ada tabel lain yang bergantung pada tabel ini, jadi aman dihapus.
-- #####################################################################
-- #####################################################################
CREATE TABLE profil_pengguna (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_mahasiswa    BIGINT UNSIGNED NOT NULL,   -- Hanya untuk testing tanpa myitssso
    username        VARCHAR(50)     NOT NULL,   -- Hanya untuk testing tanpa myitssso
    password        VARCHAR(255)    NOT NULL,   -- Hanya untuk testing tanpa myitssso (hash bcrypt, bukan teks asli)
    remember_token  VARCHAR(100)    NULL,       -- Hanya untuk testing tanpa myitssso (fitur "ingat saya" Laravel)
    terakhir_login  DATETIME        NULL,       -- Hanya untuk testing tanpa myitssso
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_profil_pengguna PRIMARY KEY (id),
    CONSTRAINT uq_profil_pengguna_mahasiswa UNIQUE (id_mahasiswa),   -- satu akun testing per mahasiswa (1 ke 0..1)
    CONSTRAINT uq_profil_pengguna_username UNIQUE (username),
    CONSTRAINT fk_profil_pengguna_mahasiswa FOREIGN KEY (id_mahasiswa)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
-- ===== Akhir bagian testing tanpa myitssso =====


-- ---------------------------------------------------------------------
-- DATA DUMMY (sementara): kontak TU untuk PIC ruangan.
-- Hapus atau ganti saat data TU yang sebenarnya tersedia.
-- ---------------------------------------------------------------------
INSERT INTO informasi_tu (nama, email, foto_profil, rata_respon_menit) VALUES
    ('TU Dummy 1', 'tu.dummy1@example.test', NULL, 120),
    ('TU Dummy 2', 'tu.dummy2@example.test', NULL, 60);
