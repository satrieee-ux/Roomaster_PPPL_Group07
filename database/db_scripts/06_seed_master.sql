-- =====================================================================
--  ROOMASTER - 06_seed_master.sql
--  DML awal supaya ruangan langsung tampil: kontak TU (PIC), fasilitas,
--  12 ruangan (6 SKPB + 6 Departemen), dan fasilitas tiap ruangan.
--  Aman dijalankan BERULANG (INSERT IGNORE / ON DUPLICATE KEY UPDATE).
--  Tidak membuat mahasiswa atau peminjaman (itu di 07_seed_demo.sql).
--      mysql -u root -p roomaster < 06_seed_master.sql
--
--  DATA INI DUMMY. Kode ruangan, lokasi, kapasitas, dan deskripsi hanya
--  contoh untuk demo; ganti dengan data ruangan ITS yang sebenarnya.
--  foto_ruangan memakai gambar yang sudah ada di public/images/rooms/
--  (kolom DDL menyebut "path relatif di storage", tetapi komponen
--  card_room memakai asset($foto_ruangan) sehingga path public dipakai).
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

-- ---------------------------------------------------------------------
-- 1. informasi_tu: PIC ruangan (kontak, bukan akun). Sama dengan dummy di DDL.
-- ---------------------------------------------------------------------
INSERT INTO informasi_tu (nama, email, foto_profil, rata_respon_menit) VALUES
    ('TU Dummy 1', 'tu.dummy1@example.test', NULL, 120),
    ('TU Dummy 2', 'tu.dummy2@example.test', NULL, 60)
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama), rata_respon_menit = VALUES(rata_respon_menit);

-- ---------------------------------------------------------------------
-- 2. fasilitas
--    Nama menentukan ikon di Room Detail (Fasilitas::ikon()):
--    mengandung "ac" -> air-vent, "proyektor" -> projector, "wi-fi" -> wifi,
--    "kursi" -> armchair, "lampu" -> lightbulb, lainnya -> check.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO fasilitas (nama_fasilitas) VALUES
    ('AC'),
    ('Proyektor'),
    ('Wi-Fi'),
    ('Whiteboard'),
    ('Kursi'),
    ('Stop Kontak'),
    ('Komputer'),
    ('Sound System'),
    ('Podium'),
    ('Panggung'),
    ('Lampu Sorot');

-- ---------------------------------------------------------------------
-- 3. data_ruangan
--    kategori: 'skpb' | 'departemen' (tab dashboard).
--    Kapasitas sengaja tersebar agar semua rentang filter punya hasil:
--    0-19, 20-49, 50-99, 100+.
-- ---------------------------------------------------------------------
INSERT INTO data_ruangan
    (kode_ruangan, nama_ruangan, kategori, tipe_ruangan, kapasitas, lokasi, deskripsi, foto_ruangan, id_pic)
VALUES
    -- ---------- Departemen ----------
    ('SI-4202', 'Ruang Kelas SI-4202', 'departemen', 'Kelas', 40,
     'Gedung Departemen Sistem Informasi, Lantai 4',
     'Ruang kelas dengan proyektor dan AC, cocok untuk kuliah pengganti, diskusi kelompok, dan presentasi.',
     'images/rooms/si-4202.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy1@example.test')),
    ('SI-4203', 'Ruang Kelas SI-4203', 'departemen', 'Kelas', 40,
     'Gedung Departemen Sistem Informasi, Lantai 4',
     'Ruang kelas bersebelahan dengan SI-4202. Dilengkapi whiteboard besar dan koneksi Wi-Fi stabil.',
     'images/rooms/class.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy1@example.test')),
    ('SI-LAB1', 'Laboratorium Komputer SI-1', 'departemen', 'Laboratorium', 30,
     'Gedung Departemen Sistem Informasi, Lantai 3',
     'Laboratorium dengan 30 komputer untuk praktikum, pelatihan perangkat lunak, dan ujian berbasis komputer.',
     'images/rooms/computer-lab.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy1@example.test')),
    ('SI-LAB2', 'Laboratorium Basis Data', 'departemen', 'Laboratorium', 30,
     'Gedung Departemen Sistem Informasi, Lantai 3',
     'Laboratorium untuk praktikum basis data dan analitik data. Komputer sudah terpasang peladen basis data lokal.',
     'images/rooms/computer-lab.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy1@example.test')),
    ('SI-RPT', 'Ruang Rapat Departemen SI', 'departemen', 'Ruang Rapat', 15,
     'Gedung Departemen Sistem Informasi, Lantai 2',
     'Ruang rapat kecil dengan meja oval, layar proyektor, dan AC. Nyaman untuk rapat organisasi dan bimbingan.',
     'images/rooms/blue-class.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy1@example.test')),
    ('IF-1101', 'Ruang Kelas IF-1101', 'departemen', 'Kelas', 60,
     'Gedung Departemen Teknik Informatika, Lantai 1',
     'Ruang kelas luas di lantai dasar, akses mudah, cocok untuk kelas besar dan kuliah tamu.',
     'images/rooms/class.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy2@example.test')),

    -- ---------- SKPB ----------
    ('SKPB-A101', 'Ruang Kelas SKPB A-101', 'skpb', 'Kelas', 50,
     'Gedung SKPB, Lantai 1',
     'Ruang kelas standar dengan proyektor, AC, dan kursi bersusun fleksibel.',
     'images/rooms/blue-class.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy2@example.test')),
    ('SKPB-A201', 'Ruang Kelas SKPB A-201', 'skpb', 'Kelas', 80,
     'Gedung SKPB, Lantai 2',
     'Ruang kelas besar untuk perkuliahan gabungan, pelatihan, dan workshop.',
     'images/rooms/class.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy2@example.test')),
    ('SKPB-AULA', 'Aula SKPB', 'skpb', 'Aula', 250,
     'Gedung SKPB, Lantai 1',
     'Aula serbaguna dengan panggung, sound system, dan lampu sorot untuk seminar, pelantikan, dan acara besar.',
     'images/rooms/hall.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy2@example.test')),
    ('SKPB-DISK', 'Ruang Diskusi SKPB', 'skpb', 'Ruang Rapat', 12,
     'Gedung SKPB, Lantai 3',
     'Ruang diskusi kecil untuk belajar kelompok dan rapat tim, tersedia whiteboard dan layar.',
     'images/rooms/building.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy2@example.test')),
    ('SKPB-LAB', 'Laboratorium Multimedia SKPB', 'skpb', 'Laboratorium', 35,
     'Gedung SKPB, Lantai 2',
     'Laboratorium multimedia untuk desain, penyuntingan video, dan pelatihan perangkat lunak kreatif.',
     'images/rooms/computer-lab.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy2@example.test')),
    ('SKPB-B101', 'Ruang Seminar SKPB B-101', 'skpb', 'Ruang Seminar', 120,
     'Gedung SKPB, Lantai 1',
     'Ruang seminar bertingkat dengan sound system dan podium, kapasitas sekitar 120 orang.',
     'images/rooms/building.jpg', (SELECT id FROM informasi_tu WHERE email = 'tu.dummy2@example.test'))
ON DUPLICATE KEY UPDATE
    nama_ruangan = VALUES(nama_ruangan),
    kategori     = VALUES(kategori),
    tipe_ruangan = VALUES(tipe_ruangan),
    kapasitas    = VALUES(kapasitas),
    lokasi       = VALUES(lokasi),
    deskripsi    = VALUES(deskripsi),
    foto_ruangan = VALUES(foto_ruangan),
    id_pic       = VALUES(id_pic);

-- ---------------------------------------------------------------------
-- 4. fasilitas_ruangan: daftar fasilitas per ruangan (dipisah koma).
--    Bila mengubah daftar, hapus baris lama dulu atau jalankan DELETE
--    FROM fasilitas_ruangan; sebelum menjalankan ulang file ini.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO fasilitas_ruangan (id_ruangan, id_fasilitas)
SELECT r.id, f.id
FROM (
    SELECT 'SI-4202'   AS kode, 'AC,Proyektor,Wi-Fi,Whiteboard,Kursi,Stop Kontak' AS daftar
    UNION ALL SELECT 'SI-4203',   'AC,Proyektor,Wi-Fi,Whiteboard,Kursi'
    UNION ALL SELECT 'SI-LAB1',   'AC,Komputer,Proyektor,Wi-Fi,Stop Kontak'
    UNION ALL SELECT 'SI-LAB2',   'AC,Komputer,Wi-Fi,Stop Kontak,Whiteboard'
    UNION ALL SELECT 'SI-RPT',    'AC,Proyektor,Wi-Fi,Whiteboard,Stop Kontak'
    UNION ALL SELECT 'IF-1101',   'AC,Proyektor,Kursi,Whiteboard'
    UNION ALL SELECT 'SKPB-A101', 'AC,Proyektor,Wi-Fi,Kursi,Whiteboard'
    UNION ALL SELECT 'SKPB-A201', 'AC,Proyektor,Wi-Fi,Kursi,Sound System'
    UNION ALL SELECT 'SKPB-AULA', 'AC,Sound System,Proyektor,Podium,Panggung,Kursi,Wi-Fi,Lampu Sorot'
    UNION ALL SELECT 'SKPB-DISK', 'AC,Wi-Fi,Whiteboard,Stop Kontak'
    UNION ALL SELECT 'SKPB-LAB',  'AC,Komputer,Proyektor,Wi-Fi,Stop Kontak'
    UNION ALL SELECT 'SKPB-B101', 'AC,Proyektor,Sound System,Podium,Kursi,Wi-Fi'
) x
JOIN data_ruangan r ON r.kode_ruangan = x.kode
JOIN fasilitas    f ON FIND_IN_SET(f.nama_fasilitas, x.daftar) > 0;

-- ---------------------------------------------------------------------
-- Pemeriksaan cepat (hasil yang diharapkan: 12 ruangan, 11 fasilitas)
-- ---------------------------------------------------------------------
SELECT COUNT(*) AS jumlah_ruangan   FROM data_ruangan;
SELECT COUNT(*) AS jumlah_fasilitas FROM fasilitas;
SELECT kode_ruangan, kategori, tipe_ruangan, kapasitas, rata_rating, tersedia_sekarang, daftar_fasilitas
FROM v_ruangan_kartu ORDER BY kategori, kode_ruangan;
