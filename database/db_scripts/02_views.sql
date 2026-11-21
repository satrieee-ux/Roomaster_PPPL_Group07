-- =====================================================================
--  ROOMASTER - 02_views.sql
--  View tambahan di atas 3 view bawaan DDL (v_ketersediaan_ruangan,
--  v_peminjaman_tampil, v_ulasan_ruangan). Jalankan SETELAH 01_functions.sql.
--  Aman dijalankan ulang (CREATE OR REPLACE).
--
--  View untuk keamanan per-pengguna (rls_*) ada di 05_security_rls.sql.
--
--  Dibuat oleh: Muhammad Satrio Adjie (5026241065)
-- =====================================================================

USE roomaster;

-- ---------------------------------------------------------------------
-- v_jam_operasional: jam mulai slot 08.00 s.d. 18.00 (slot terakhir 18-19).
--   Dipakai untuk membuat daftar jam terpakai/kosong (UC-08, UC-03).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_jam_operasional AS
SELECT 8 AS jam UNION ALL SELECT 9  UNION ALL SELECT 10 UNION ALL SELECT 11
UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18;

-- ---------------------------------------------------------------------
-- v_rating_ruangan: jumlah ulasan dan rata-rata rating per ruangan.
--   Ruangan tanpa ulasan tetap muncul dengan rating 0 (untuk "Recommended").
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_rating_ruangan AS
SELECT
    r.id                                AS id_ruangan,
    r.kode_ruangan,
    COUNT(u.id_ulasan)                  AS jumlah_ulasan,
    COALESCE(ROUND(AVG(u.rating), 1), 0) AS rata_rating
FROM data_ruangan r
LEFT JOIN v_ulasan_ruangan u ON u.id_ruangan = r.id
GROUP BY r.id, r.kode_ruangan;

-- ---------------------------------------------------------------------
-- v_ruangan_kartu: satu baris per ruangan, lengkap untuk kartu ruangan dan
--   Room Detail (UC-02, UC-03, UC-04): rating, Now Available, PIC, fasilitas.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_ruangan_kartu AS
SELECT
    r.id,
    r.kode_ruangan,
    r.nama_ruangan,
    r.kategori,
    r.tipe_ruangan,
    r.kapasitas,
    r.lokasi,
    r.deskripsi,
    r.foto_ruangan,
    rt.rata_rating,
    rt.jumlah_ulasan,
    k.tersedia_sekarang,
    t.nama                  AS nama_pic,
    t.rata_respon_menit,
    (SELECT GROUP_CONCAT(f.nama_fasilitas ORDER BY f.nama_fasilitas SEPARATOR ', ')
       FROM fasilitas_ruangan fr
       JOIN fasilitas f ON f.id = fr.id_fasilitas
      WHERE fr.id_ruangan = r.id)                               AS daftar_fasilitas,
    (SELECT COUNT(*)
       FROM peminjaman p
      WHERE p.id_ruangan = r.id
        AND p.status IN ('disetujui','berlangsung','selesai'))  AS total_dipinjam
FROM data_ruangan r
JOIN v_rating_ruangan        rt ON rt.id_ruangan = r.id
JOIN v_ketersediaan_ruangan  k  ON k.id_ruangan  = r.id
LEFT JOIN informasi_tu       t  ON t.id          = r.id_pic;

-- ---------------------------------------------------------------------
-- v_ulasan_top: "Top Reviews" = 3 ulasan terbaik per ruangan
--   (rating tertinggi, lalu yang terbaru). UC-05.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_ulasan_top AS
SELECT x.id_ulasan, x.id_ruangan, x.id_mahasiswa, x.nama_pengulas, x.foto_profil,
       x.rating, x.komentar, x.tanggal_ulasan, x.peringkat
FROM (
    SELECT u.id_ulasan, u.id_ruangan, u.id_mahasiswa, u.nama_pengulas, u.foto_profil,
           u.rating, u.komentar, u.tanggal_ulasan,
           ROW_NUMBER() OVER (
               PARTITION BY u.id_ruangan
               ORDER BY u.rating DESC, u.tanggal_ulasan DESC
           ) AS peringkat
    FROM v_ulasan_ruangan u
) x
WHERE x.peringkat <= 3;

-- ---------------------------------------------------------------------
-- v_booking_terdekat: satu booking 'disetujui' terdekat per mahasiswa yang
--   belum lewat. Untuk kartu "Upcoming Bookings" di Menu Utama (UC-02).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_booking_terdekat AS
SELECT x.id, x.id_mahasiswa, x.nama_tamu, x.id_ruangan, x.kode_ruangan, x.nama_ruangan,
       x.nama_kegiatan, x.tanggal_pakai, x.waktu_mulai, x.waktu_selesai,
       x.status, x.is_upcoming
FROM (
    SELECT v.id, v.id_mahasiswa, v.nama_tamu, v.id_ruangan, v.kode_ruangan, v.nama_ruangan,
           v.nama_kegiatan, v.tanggal_pakai, v.waktu_mulai, v.waktu_selesai,
           v.status, v.is_upcoming,
           ROW_NUMBER() OVER (
               PARTITION BY v.id_mahasiswa
               ORDER BY TIMESTAMP(v.tanggal_pakai, v.waktu_mulai)
           ) AS urutan
    FROM v_peminjaman_tampil v
    WHERE v.status = 'disetujui'
      AND TIMESTAMP(v.tanggal_pakai, v.waktu_selesai) > NOW()
) x
WHERE x.urutan = 1;

-- ---------------------------------------------------------------------
-- v_percakapan_ringkas: daftar percakapan dari sudut pandang SETIAP peserta
--   (dua baris per percakapan). Filter dengan id_mahasiswa = pengguna login.
--   Berisi lawan bicara, pesan terakhir, dan jumlah belum dibaca (UC-16).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_percakapan_ringkas AS
SELECT
    s.id_percakapan,
    s.id_mahasiswa,
    s.id_lawan,
    m.nama                AS nama_lawan,
    m.foto_profil         AS foto_lawan,
    lp.isi_pesan          AS pesan_terakhir,
    lp.waktu_kirim        AS waktu_pesan_terakhir,
    (SELECT COUNT(*)
       FROM pesan p
      WHERE p.id_percakapan = s.id_percakapan
        AND p.id_pengirim  <> s.id_mahasiswa
        AND p.sudah_dibaca  = 0)  AS jumlah_belum_dibaca
FROM (
    SELECT id AS id_percakapan, id_mahasiswa_1 AS id_mahasiswa, id_mahasiswa_2 AS id_lawan FROM percakapan
    UNION ALL
    SELECT id AS id_percakapan, id_mahasiswa_2 AS id_mahasiswa, id_mahasiswa_1 AS id_lawan FROM percakapan
) s
JOIN mahasiswa_its m ON m.id = s.id_lawan
LEFT JOIN pesan lp ON lp.id = (
    SELECT p2.id
      FROM pesan p2
     WHERE p2.id_percakapan = s.id_percakapan
     ORDER BY p2.waktu_kirim DESC, p2.id DESC
     LIMIT 1
);

-- ---------------------------------------------------------------------
-- v_laporan_ringkas: laporan kerusakan + ruangan + jumlah bukti (UC-19, UC-20).
--   nomor_laporan = id 6 digit (kolom nomor_laporan sudah dihapus dari DDL).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_laporan_ringkas AS
SELECT
    l.id,
    fn_nomor_6digit(l.id)   AS nomor_laporan,
    l.id_mahasiswa,
    l.id_ruangan,
    r.kode_ruangan,
    r.nama_ruangan,
    r.lokasi,
    l.judul,
    l.kategori_kerusakan,
    l.deskripsi_masalah,
    l.tanggal_lapor,
    l.status,
    l.created_at,
    l.updated_at,
    (SELECT COUNT(*) FROM bukti_laporan b WHERE b.id_laporan = l.id) AS jumlah_bukti
FROM laporan_kerusakan l
JOIN data_ruangan r ON r.id = l.id_ruangan;

-- ---------------------------------------------------------------------
-- v_antrean_pengajuan: pengajuan 'pending' menunggu keputusan TU,
--   yang paling lama mengantre di atas (urutkan saat SELECT).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_antrean_pengajuan AS
SELECT
    v.id,
    fn_nomor_6digit(v.id)   AS nomor_booking,
    v.nama_tamu,
    v.kode_ruangan,
    v.nama_kegiatan,
    v.departemen_penyelenggara,
    v.tanggal_pakai,
    v.waktu_mulai,
    v.waktu_selesai,
    v.tanggal_pengajuan,
    (SELECT COUNT(*) FROM dokumen_peminjaman d WHERE d.id_peminjaman = v.id) AS jumlah_dokumen
FROM v_peminjaman_tampil v
WHERE v.status = 'pending';

-- ---------------------------------------------------------------------
-- v_badge_mahasiswa: angka badge di header (notifikasi dan pesan belum dibaca).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_badge_mahasiswa AS
SELECT
    m.id AS id_mahasiswa,
    (SELECT COUNT(*) FROM notifikasi n
      WHERE n.id_mahasiswa = m.id AND n.sudah_dibaca = 0)           AS notifikasi_belum_dibaca,
    (SELECT COUNT(*) FROM pesan p
       JOIN percakapan c ON c.id = p.id_percakapan
      WHERE (c.id_mahasiswa_1 = m.id OR c.id_mahasiswa_2 = m.id)
        AND p.id_pengirim <> m.id
        AND p.sudah_dibaca = 0)                                      AS pesan_belum_dibaca
FROM mahasiswa_its m;

-- ---------------------------------------------------------------------
-- v_statistik_ruangan: ringkasan jumlah peminjaman per status per ruangan
--   (bahan dashboard / bukti penilaian). Ruangan tanpa peminjaman = 0.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_statistik_ruangan AS
SELECT
    r.id AS id_ruangan,
    r.kode_ruangan,
    COUNT(p.id)                                         AS total_peminjaman,
    COALESCE(SUM(p.status = 'pending'),     0)          AS pending,
    COALESCE(SUM(p.status = 'disetujui'),   0)          AS disetujui,
    COALESCE(SUM(p.status = 'berlangsung'), 0)          AS berlangsung,
    COALESCE(SUM(p.status = 'selesai'),     0)          AS selesai,
    COALESCE(SUM(p.status IN ('ditolak','dibatalkan')), 0) AS ditolak_atau_batal
FROM data_ruangan r
LEFT JOIN peminjaman p ON p.id_ruangan = r.id
GROUP BY r.id, r.kode_ruangan;
