-- =====================================================================
--  ROOMASTER - Skema Database MySQL
--  Kelompok 7. Disusun dari usulan revisi class diagram gabungan.
--  Target  : MySQL 8.0.16+ (CHECK constraint aktif), engine InnoDB, utf8mb4.
--  Konvensi: primary key = id, foreign key = id_<tabel>, snake_case.
--  Kolom created_at / updated_at ditambahkan agar langsung cocok
--  dengan Eloquent Laravel (tidak digambar di class diagram).
-- =====================================================================

DROP DATABASE IF EXISTS roomaster;
CREATE DATABASE roomaster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE roomaster;

-- ---------------------------------------------------------------------
-- 1. mahasiswa_its
--    Data identitas dari MyITS SSO (UC-01) dan foto_profil
--    (profil pengguna dilebur ke tabel ini, sesuai PDF revisi).
--    Tidak menyimpan password. Login testing ada di bagian 14 (profil_pengguna).
-- ---------------------------------------------------------------------
CREATE TABLE mahasiswa_its (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nrp         VARCHAR(20)     NOT NULL,              -- teks, NRP bisa diawali nol
    nama        VARCHAR(100)    NOT NULL,
    email_its   VARCHAR(100)    NOT NULL,              -- dicocokkan saat callback MyITS
    foto_profil VARCHAR(255)    NULL,              -- path relatif di storage, contoh: profil/a1b2c3.jpg
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_mahasiswa_its PRIMARY KEY (id),
    CONSTRAINT uq_mahasiswa_its_nrp UNIQUE (nrp),
    CONSTRAINT uq_mahasiswa_its_email UNIQUE (email_its)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. data_ruangan
--    Isi QR di pintu ruangan = kode_ruangan (UC-18).
--    Ketersediaan TIDAK disimpan di sini; dihitung dari tabel peminjaman.
-- ---------------------------------------------------------------------
CREATE TABLE data_ruangan (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_ruangan    VARCHAR(20)     NOT NULL,
    nama_ruangan    VARCHAR(100)    NOT NULL,
    kategori        ENUM('skpb','departemen') NOT NULL,             -- tab dashboard UC-02
    tipe_ruangan    VARCHAR(50)     NOT NULL,                       -- kelas, laboratorium, aula, ruang rapat, ...
    kapasitas       INT UNSIGNED    NOT NULL,
    lokasi          VARCHAR(255)    NOT NULL,                       -- gedung dan lantai
    foto_ruangan    VARCHAR(255)    NULL,                           -- path relatif di storage, contoh: ruangan/x9y8.jpg
    status          ENUM('available','not_available') NOT NULL DEFAULT 'available',
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_data_ruangan PRIMARY KEY (id),
    CONSTRAINT uq_data_ruangan_kode UNIQUE (kode_ruangan),
    CONSTRAINT ck_data_ruangan_kapasitas CHECK (kapasitas > 0),
    INDEX idx_data_ruangan_nama (nama_ruangan)                      -- searchByName (UC-03)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. fasilitas
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
-- 4. fasilitas_ruangan  (tabel penghubung banyak-ke-banyak)
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
-- 5. ruangan_favorit  (filterByFavorites, UC-02)
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
-- 6. peminjaman
--    status menggantikan status_pengajuan. Antrean = status 'pending'
--    diurutkan menurut tanggal_pengajuan (tidak perlu tabel antrean).
--    Filter UC-07: Active = berlangsung/disetujui, Pending = pending/revisi,
--    History = selesai/ditolak/dibatalkan.
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
-- 7. dokumen_peminjaman  (UC-09, UC-10)
--    File disimpan di storage Laravel, database hanya menyimpan path relatif.
-- ---------------------------------------------------------------------
CREATE TABLE dokumen_peminjaman (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_peminjaman   BIGINT UNSIGNED NOT NULL,
    jenis_dokumen   ENUM('ktm_ktp','surat_kegiatan') NOT NULL,
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
-- 8. riwayat_peminjaman  (UC-06)
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
-- 9. ulasan_peminjaman  (UC-05, UC-14)
--     Satu peminjaman hanya bisa diulas sekali. Penulis ulasan diambil
--     lewat peminjaman.id_mahasiswa; ruangan lewat peminjaman.id_ruangan.
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
    CONSTRAINT ck_ulasan_peminjaman_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. percakapan  (UC-16)
--     Pesan terakhir dan jumlah belum dibaca dihitung dari tabel pesan.
--     Jika lawan bicara ternyata pengelola ruangan, ganti FK id_mahasiswa_2.
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
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT ck_percakapan_peserta CHECK (id_mahasiswa_1 < id_mahasiswa_2)  -- simpan id kecil di kolom 1, cegah pasangan dobel/terbalik
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. pesan  (UC-15)
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
-- 12. notifikasi  (UC-17)
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
-- 13. laporan_kerusakan  (UC-18, UC-19, UC-20)
--     id_mahasiswa menggantikan id_pelapor.
-- ---------------------------------------------------------------------
CREATE TABLE laporan_kerusakan (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nomor_laporan       VARCHAR(20)     NOT NULL,          -- format: LPR-2026-0001
    id_ruangan          BIGINT UNSIGNED NOT NULL,
    id_mahasiswa        BIGINT UNSIGNED NOT NULL,
    kategori_kerusakan  ENUM('listrik','ac','furnitur','proyektor_av',
                             'jaringan','kebersihan','lainnya') NOT NULL,
    deskripsi_masalah   TEXT            NOT NULL,
    tanggal_lapor       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status              ENUM('draft','pending','diproses','selesai') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_laporan_kerusakan PRIMARY KEY (id),
    CONSTRAINT uq_laporan_kerusakan_nomor UNIQUE (nomor_laporan),
    CONSTRAINT fk_laporan_kerusakan_ruangan FOREIGN KEY (id_ruangan)
        REFERENCES data_ruangan (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laporan_kerusakan_mahasiswa FOREIGN KEY (id_mahasiswa)
        REFERENCES mahasiswa_its (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_laporan_kerusakan_status (status, tanggal_lapor)
) ENGINE=InnoDB;


-- #####################################################################
-- #####################################################################
--  14. profil_pengguna
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
