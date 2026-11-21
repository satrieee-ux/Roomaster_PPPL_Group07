<?php

// Dibuat oleh: Muhammad Satrio Adjie (5026241065) -- kerangka method sesuai Katalog Penamaan.
// Isi method dikerjakan PIC masing-masing; tanda UC-xx (Nama) menunjukkan pemilik isinya.

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PeminjamanController extends Controller
{
    // UC-07 (Thalia) -> pages.daftar_bookings
    public function daftarPengajuan() {}
    public function filterPengajuan(string $kategori) {}

    // UC-06 (Thalia) -> pages.booking_summary
    public function riwayat(int $id) {}

    // UC-08 (Thoriq) -> pages.booking_isiformulir_peminjaman, pages.booking_pilihtanggal_peminjaman
    public function isiFormulir(int $id) {}
    public function cekKetersediaan(Request $request, int $id) {}
    public function simpanDraft(Request $request, int $id) {}

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
