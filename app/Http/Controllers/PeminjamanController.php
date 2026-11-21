<?php

// Dibuat oleh: Muhammad Satrio Adjie (5026241065) -- kerangka method sesuai Katalog Penamaan.
// Isi method dikerjakan PIC masing-masing; tanda UC-xx (Nama) menunjukkan pemilik isinya.

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    // Tab di halaman Your Bookings -> status peminjaman (DDL).
    // [TIM] Asumsi: draft dan revisi masuk tab Pending; ditolak dan dibatalkan masuk History.
    private const KATEGORI_STATUS = [
        'active'  => ['disetujui', 'berlangsung'],
        'pending' => ['draft', 'pending', 'revisi'],
        'history' => ['ditolak', 'selesai', 'dibatalkan'],
    ];

    // UC-07 (Thalia) -> pages.daftar_bookings
    // + Satrio: isi awal agar /bookings bisa dibuka; PIC boleh menyesuaikan
    public function daftarPengajuan()
    {
        return $this->tampilkanDaftar('all');
    }

    public function filterPengajuan(string $kategori)
    {
        return $this->tampilkanDaftar($kategori);
    }

    private function tampilkanDaftar(string $kategori)
    {
        $idMahasiswa = (int) Auth::user()->id_mahasiswa;

        // Data dari view v_peminjaman_tampil + lokasi dan foto_ruangan dari data_ruangan
        $rows = DB::table('v_peminjaman_tampil AS p')
            ->join('data_ruangan AS r', 'r.id', '=', 'p.id_ruangan')
            ->select('p.*', 'r.lokasi', 'r.foto_ruangan')
            ->where('p.id_mahasiswa', $idMahasiswa)
            ->when(isset(self::KATEGORI_STATUS[$kategori]),
                fn ($q) => $q->whereIn('p.status', self::KATEGORI_STATUS[$kategori]))
            ->orderByDesc('p.tanggal_pakai')
            ->orderByDesc('p.waktu_mulai')
            ->get();

        // Satu array per kartu, nama kuncinya mengikuti props <x-card_booking>
        $bookings = $rows->map(fn ($b) => [
            'id'          => $b->id,
            'title'       => $b->kode_ruangan,
            'date'        => Carbon::parse($b->tanggal_pakai)->format('d F Y'),
            'time'        => substr($b->waktu_mulai, 0, 5).' - '.substr($b->waktu_selesai, 0, 5).' WIB',
            'location'    => $b->lokasi,
            'image'       => $b->foto_ruangan,
            'status'      => $b->status,
            'isUpcoming'  => (bool) $b->is_upcoming,
            'href'        => route('bookings.summary', $b->id),
            // Tombol utama: Check-In (upcoming) atau Continue (draft, kembali ke form ruangan itu)
            'primaryHref' => $b->status === 'draft'
                ? route('booking.form', $b->id_ruangan)
                : route('checkin', $b->id),
        ])->all();

        return view('pages.daftar_bookings', [
            'bookings'  => $bookings,
            'activeTab' => isset(self::KATEGORI_STATUS[$kategori]) ? $kategori : 'all',
        ]);
    }

    // UC-06 (Thalia) -> pages.booking_summary
    public function riwayat(int $id) {}

    // UC-08 (Thoriq) -> pages.booking_isiformulir_peminjaman, pages.booking_pilihtanggal_peminjaman
    public function isiFormulir(int $id) {}
    public function cekKetersediaan(Request $request, int $id) {}
    public function simpanDraft(Request $request, int $id) {
    // + Satrio: aturan sesuai DDL peminjaman; pesan per kolom tampil di bawah field (x-form_error)
        $request->validate([
            'nama_kegiatan'            => 'required|string|max:150',
            'tujuan'                   => 'required|string',
            'departemen_penyelenggara' => 'required|string|max:100',
            'tanggal_pakai'            => 'required|date|after_or_equal:today',
            'waktu_mulai'              => 'required|date_format:H:i',
            'waktu_selesai'            => 'required|date_format:H:i|after:waktu_mulai',
        ], [
            'required'            => ':attribute wajib diisi.',
            'max.string'          => ':attribute maksimal :max karakter.',
            'after_or_equal'      => ':attribute tidak boleh sebelum hari ini.',
            'waktu_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ], [
            'nama_kegiatan' => 'Nama kegiatan', 'tujuan' => 'Tujuan',
            'departemen_penyelenggara' => 'Departemen', 'tanggal_pakai' => 'Tanggal',
            'waktu_mulai' => 'Jam mulai', 'waktu_selesai' => 'Jam selesai',
        ]);
    }

    // UC-09, UC-10 (Thoriq) -> pages.booking_melampirkan_dokumenpendukung, pages.booking_berkasterunggah
    public function halamanDokumen(int $id) {}
    public function uploadDokumen(Request $request, int $id) {}
    public function ambilDokumen(int $id) {}
    public function hapusDokumen(Request $request, int $id) {}

    // UC-11 (Thalia) -> pages.booking_ringkasanpeminjaman, pages.booking_berhasil
    public function ambilDataPeminjaman(int $id) {}
    public function simpanPengajuan(Request $request, int $id) {}
    public function berhasil(int $id) {}

    // UC-12 Check-In (Thoriq) dan UC-13 Check-Out (Thalia)
    // $aksi = 'checkin' | 'checkout' -> pages.checkin / checkout / berhasilcheckin / berhasilcheckout
    public function halamanRoomState(int $id, string $aksi) {}
    public function updateRoomState(Request $request, int $id, string $aksi) {}
}
