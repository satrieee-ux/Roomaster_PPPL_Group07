<?php

// Dibuat oleh: Satya Azka Aqilla (5026241199)
// UC-01 Login MyITS (Sign Up, login, simulasi MyITS), UC-02 Melihat Dashboard (Menu Utama)
// Diperbaiki oleh: Muhammad Satrio Adjie (5026241065), tambah prefix pages. pada view dan perbaikan status booking terdekat di Menu Utama

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\ProfilPengguna;
use App\Models\Ruangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// AkunController: Sign Up, Login MyITS (simulasi), login testing, dan Menu Utama.
// Nama method mengikuti README sequence diagram. [tambahan] = tidak ada di diagram.
class AkunController extends Controller
{
    // Pesan kesalahan isian dalam bahasa Indonesia (:attribute = nama kolom).
    private const PESAN = [
        'required' => ':attribute wajib diisi.',
        'email'    => ':attribute harus berupa alamat email.',
        'unique'   => ':attribute sudah terdaftar.',
        'min'      => ':attribute minimal :min karakter.',
        'max'      => ':attribute maksimal :max karakter.',
    ];

    // [tambahan] Halaman intro (UC-01 tidak punya sequence diagram)
    public function splash()
    {
        return view('pages.auth.splash');
    }

    // ---------------------------------------------------------------
    // Login testing: username + password dari tabel profil_pengguna
    // ---------------------------------------------------------------
    public function showLogin()
    {
        return view('pages.auth.login');
    }

    // [tambahan] Login testing tanpa MyITS
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], self::PESAN);

        // Auth::attempt mencari baris profil_pengguna dengan username tsb,
        // lalu mencocokkan password dengan hash bcrypt.
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'Username atau password salah.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $this->catatWaktuLogin();

        return redirect()->intended(route('home'));
    }

    // ---------------------------------------------------------------
    // Sign Up: simpan ke mahasiswa_its lalu profil_pengguna
    // ---------------------------------------------------------------
    public function showSignup()
    {
        return view('pages.auth.signup');
    }

    // UC-01 (Satya): Sign Up. Validasi, simpan ke mahasiswa_its dan profil_pengguna, lalu langsung login.
    public function register(Request $request)
    {
        $data = $request->validate([
            'nama'      => 'required|string|max:100',
            'nrp'       => 'required|string|max:20|unique:mahasiswa_its,nrp',
            'email_its' => 'required|email|max:100|unique:mahasiswa_its,email_its',
            'username'  => 'required|string|max:50|unique:profil_pengguna,username',
            'password'  => 'required|string|min:6',
        ], self::PESAN);

        // Dua insert dijalankan bersama: kalau salah satu gagal, keduanya dibatalkan.
        $akun = DB::transaction(function () use ($data) {
            $mahasiswa = Mahasiswa::create([
                'nrp'       => $data['nrp'],
                'nama'      => $data['nama'],
                'email_its' => $data['email_its'],
            ]);

            return ProfilPengguna::create([
                'id_mahasiswa' => $mahasiswa->id,
                'username'     => $data['username'],
                'password'     => Hash::make($data['password']), // disimpan sebagai hash, bukan teks asli
            ]);
        });

        Auth::login($akun);
        $request->session()->regenerate();
        $this->catatWaktuLogin();

        return redirect()->route('home');
    }

    // ---------------------------------------------------------------
    // Simulasi MyITS SSO (tanpa verifikasi password sungguhan)
    // ---------------------------------------------------------------

    // Arahkan ke halaman login MyITS (halaman Sign In Options, tanpa data akun)
    public function redirectToMyITS()
    {
        return view('pages.auth.pilih_akun_its');
    }

    // [tambahan] Halaman email MyITS (kolom email kosong)
    public function showEmailITS()
    {
        return view('pages.auth.email_its');
    }

    // [tambahan] Halaman password MyITS. Email dicek dulu: harus ada di mahasiswa_its.
    public function showPasswordITS(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        if (! Mahasiswa::where('email_its', $data['email'])->exists()) {
            return redirect()->route('myits.email')
                ->withErrors(['email' => 'Email ITS tidak terdaftar.'])->withInput();
        }

        return view('pages.auth.password_its', ['email' => $data['email']]);
    }

    // Verifikasi akun mahasiswa ITS: cocokkan email_its (tidak menyimpan password),
    // lalu login memakai akun testing milik mahasiswa itu.
    public function handleMyITSCallback(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string', // hanya simulasi, isinya tidak dicek
        ]);

        $mahasiswa = Mahasiswa::where('email_its', $data['email'])->first();

        if (! $mahasiswa || ! $mahasiswa->profil) {
            return redirect()->route('myits.email')
                ->withErrors(['email' => 'Akun ini belum punya profil testing. Silakan Sign Up.'])->withInput();
        }

        Auth::login($mahasiswa->profil, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->catatWaktuLogin();

        return redirect()->intended(route('home'));
    }

    // [tambahan] Keluar
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('splash');
    }

    // ---------------------------------------------------------------
    // Menu utama
    // ---------------------------------------------------------------
    public function showHome()
    {
        $mahasiswa = Auth::user()->mahasiswa;

        return view('pages.rooms.menu_utama', [
            'upcoming'    => $this->bookingTerdekat($mahasiswa->id),
            'terakhir'    => Ruangan::tandaiFavorit(Ruangan::terakhirDipinjam($mahasiswa->id, 5)),
            'rekomendasi' => Ruangan::tandaiFavorit(Ruangan::rekomendasi()->take(4)->get()),
        ]);
    }

    // Upcoming Bookings: peminjaman 'disetujui' milik user dengan jadwal terdekat yang belum lewat.
    // Data dari view v_peminjaman_tampil + lokasi dan foto_ruangan dari data_ruangan.
    // Hasil: array siap pakai untuk <x-card_booking>, atau null bila tidak ada.
    private function bookingTerdekat(int $idMahasiswa): ?array
    {
        $hariIni = now()->toDateString();
        $jamSekarang = now()->format('H:i:s');

        $b = DB::table('v_peminjaman_tampil AS p')
            ->join('data_ruangan AS r', 'r.id', '=', 'p.id_ruangan')
            ->select('p.*', 'r.lokasi', 'r.foto_ruangan')
            ->where('p.id_mahasiswa', $idMahasiswa)
            ->where('p.status', 'disetujui')
            ->where(function ($q) use ($hariIni, $jamSekarang) {
                $q->where('p.tanggal_pakai', '>', $hariIni)          // hari berikutnya
                  ->orWhere(function ($q2) use ($hariIni, $jamSekarang) {
                      $q2->where('p.tanggal_pakai', $hariIni)          // hari ini, belum selesai
                         ->where('p.waktu_selesai', '>', $jamSekarang);
                  });
            })
            ->orderBy('p.tanggal_pakai')
            ->orderBy('p.waktu_mulai')
            ->first();

        if (! $b) {
            return null;
        }

        return [
            'id'      => $b->id,
            'judul'   => $b->kode_ruangan,
            'tanggal' => Carbon::parse($b->tanggal_pakai)->format('d F Y'),         // 20 November 2026
            'waktu'   => substr($b->waktu_mulai, 0, 5).' - '.substr($b->waktu_selesai, 0, 5).' WIB',
            'lokasi'  => $b->lokasi,
            'foto'    => $b->foto_ruangan,
            // + Satrio: status mengikuti ENUM ('disetujui'); pill "Upcoming" dan tombol Check-In muncul
            // hanya bila is_upcoming = 1 (diatur card_booking dan status_pill lewat isUpcoming)
            'status'     => 'disetujui',
            'isUpcoming' => (bool) $b->is_upcoming,
        ];
    }

    // Isi profil_pengguna.terakhir_login dengan waktu sekarang.
    private function catatWaktuLogin(): void
    {
        $akun = Auth::user();
        $akun->terakhir_login = now();
        $akun->save();
    }
}
