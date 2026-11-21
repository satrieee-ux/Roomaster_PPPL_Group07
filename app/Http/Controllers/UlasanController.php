<?php

// Dibuat oleh: Muhammad Satrio Adjie (5026241065) -- kerangka method sesuai Katalog Penamaan.

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UlasanController extends Controller
{
    // UC-14 (Thalia): hanya jika peminjaman milik user dan sudah selesai
    public function addReview(Request $request, int $id) {
    // + Satrio: rating wajib 1-5 supaya bintang kosong tidak lolos; pesan tampil di bawah kolom
        $request->validate([
            'rating'   => 'required|integer|between:1,5',
            'komentar' => 'nullable|string|max:1000',
        ], [
            'rating.required'  => 'Pilih rating bintang terlebih dulu.',
            'rating.between'   => 'Pilih rating bintang terlebih dulu.',
            'komentar.max'     => 'Komentar maksimal :max karakter.',
        ]);
    }
}
