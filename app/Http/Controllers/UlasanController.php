<?php

// Dibuat oleh: Muhammad Satrio Adjie (5026241065) -- kerangka method sesuai Katalog Penamaan.

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UlasanController extends Controller
{
    // UC-14 (Thalia): hanya jika peminjaman milik user dan sudah selesai
    public function addReview(Request $request, int $id) {}
}
