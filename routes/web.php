<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ChatController;         
use App\Http\Controllers\PesanController;
use Illuminate\Support\Facades\Route;

// =====================================================================
// ===== Satya: UC-01 s.d. UC-04 (Login, Menu Utama, Rooms, Search, Room Detail)
// Nama route dan method mengikuti README sequence diagram (bagian 4. Registry).
// Method bertanda [tambahan] tidak ada di sequence diagram, tapi dibutuhkan aplikasi.
// =====================================================================

// ---------- AkunController (bisa dibuka tanpa login) ----------
Route::get('/', [AkunController::class, 'splash'])->name('splash');                        // [tambahan] halaman intro

Route::get('/signup', [AkunController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AkunController::class, 'register']);

Route::get('/login', [AkunController::class, 'showLogin'])->name('login');
Route::post('/login', [AkunController::class, 'login']);                                   // [tambahan] login testing username

// Simulasi MyITS SSO: pilih akun -> email -> password -> callback
Route::get('/auth/myits', [AkunController::class, 'redirectToMyITS'])->name('myits');
Route::get('/auth/myits/email', [AkunController::class, 'showEmailITS'])->name('myits.email');          // [tambahan] halaman MyITS
Route::get('/auth/myits/password', [AkunController::class, 'showPasswordITS'])->name('myits.password'); // [tambahan] halaman MyITS
Route::post('/auth/myits/callback', [AkunController::class, 'handleMyITSCallback'])->name('myits.callback');

Route::post('/logout', [AkunController::class, 'logout'])->name('logout');               // [tambahan]

// ---------- Halaman yang wajib login ----------
Route::middleware('auth')->group(function () {

    // Menu utama
    Route::get('/home', [AkunController::class, 'showHome'])->name('home');
    Route::redirect('/menu-utama', '/home');   // alamat lama, tetap jalan untuk link anggota lain

    // RuanganController: dashboard Rooms. Route literal ditulis SEBELUM /rooms/{id}.
    Route::get('/rooms', [RuanganController::class, 'index'])->name('rooms');
    Route::get('/rooms/skpb', [RuanganController::class, 'filterBySkpb'])->name('rooms.skpb');
    Route::get('/rooms/departemen', [RuanganController::class, 'filterByDepartemen'])->name('rooms.departemen');
    Route::get('/rooms/favorites', [RuanganController::class, 'filterByFavorites'])->name('rooms.favorites');

    // RuanganController: pencarian
    Route::get('/rooms/search', [RuanganController::class, 'showSearch'])->name('rooms.search');
    Route::get('/rooms/search/name', [RuanganController::class, 'searchByName'])->name('rooms.search.name');
    Route::get('/rooms/search/filter', [RuanganController::class, 'searchByFilter'])->name('rooms.search.filter');
    Route::get('/rooms/search/date', [RuanganController::class, 'searchByDate'])->name('rooms.search.date');

    // RuanganController: detail ruangan, ulasan (screen Thalia), tombol hati
    Route::get('/rooms/{id}', [RuanganController::class, 'showDetail'])->whereNumber('id')->name('rooms.detail');
    Route::get('/rooms/{id}/reviews', [RuanganController::class, 'reviews'])->whereNumber('id')->name('rooms.reviews');
    Route::post('/rooms/{id}/favorite', [RuanganController::class, 'toggleFavorite'])->whereNumber('id')->name('rooms.favorite'); // [tambahan]
});

// ===== Akhir blok Satya =====

// =====================================================================
// ===== Tasia: UC-15 s.d. UC-20 (Chat, Notifikasi, QReport, Laporan)
// Semua halaman wajib login. Route literal ditulis SEBELUM route {id}.
// =====================================================================
Route::middleware('auth')->group(function () {
    // LaporanController
    Route::get('/qreporting', [LaporanController::class, 'scan'])->name('qreporting');
    Route::get('/qreporting/report', [LaporanController::class, 'create'])->name('report.create');
    Route::post('/qreporting/report', [LaporanController::class, 'simpanLaporan'])->name('report.store');
    Route::get('/qreporting/submitted/{id}', [LaporanController::class, 'submitted'])->whereNumber('id')->name('report.submitted');
    Route::get('/qreporting/history', [LaporanController::class, 'ambilHistoriLaporan'])->name('report.history');
    Route::get('/qreporting/summary/{id}', [LaporanController::class, 'ambilRingkasanLaporan'])->whereNumber('id')->name('report.summary');

    // ChatController + PesanController
    Route::get('/chat', [ChatController::class, 'ambilDaftarPercakapan'])->name('chat');
    Route::get('/chat/{id}', [ChatController::class, 'show'])->whereNumber('id')->name('chat.show');
    Route::post('/chat/{id}', [PesanController::class, 'addMessage'])->whereNumber('id')->name('chat.send');

    // NotifikasiController
    Route::get('/notifikasi', [NotifikasiController::class, 'ambilNotifikasi'])->name('notifikasi');
});
// ===== Akhir blok Tasia =====
