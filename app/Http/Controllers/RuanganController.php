<?php

// Dibuat oleh: Satya Azka Aqilla (5026241199)
// UC-02 Melihat Dashboard (Rooms), UC-03 Mencari Ruangan & Hasil Pencarian, UC-04 Memeriksa Informasi & Fasilitas Ruangan, UC-05 Membaca Ulasan (method reviews, Thalia)
// Diperbaiki oleh: Muhammad Satrio Adjie (5026241065), penyesuaian data untuk view Rooms dan hasil pencarian

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// RuanganController: dashboard Rooms (All / SKPB / Departemen / Favorites), pencarian, dan detail ruangan.
// Nama method mengikuti README sequence diagram. [tambahan] = tidak ada di diagram.
class RuanganController extends Controller
{
    // Rentang kapasitas di Pop up Filter => [minimal, maksimal]
    private const RENTANG_KAPASITAS = [
        '0-19'  => [0, 19],
        '20-49' => [20, 49],
        '50-99' => [50, 99],
        '100+'  => [100, null],
    ];

    // Jam operasional ruangan untuk cek "masih ada slot kosong" (08.00 - 19.00)
    private const JAM_BUKA = 8;
    private const JAM_TUTUP = 19;

    // Tab All: ruangan paling sering dipinjam + semua ruangan
    public function index()
    {
        return view('pages.rooms.rooms', [
            'sering'  => Ruangan::tandaiFavorit($this->palingSeringDipinjam(6)),
            'ruangan' => Ruangan::tandaiFavorit(Ruangan::orderBy('kode_ruangan')->get()),
        ]);
    }

    // Tab SKPB: data_ruangan.kategori = 'skpb'
    public function filterBySkpb()
    {
        return view('pages.rooms.rooms_skpb', $this->dataKategori('skpb'));
    }

    // Tab Departemen: data_ruangan.kategori = 'departemen'
    public function filterByDepartemen()
    {
        return view('pages.rooms.rooms_departemen', $this->dataKategori('departemen'));
    }

    // Tab Favorites: ruangan_favorit milik mahasiswa yang login
    public function filterByFavorites()
    {
        $favorit = Auth::user()->mahasiswa->favorit()->orderBy('kode_ruangan')->get();

        return view('pages.rooms.rooms_fav', ['favorit' => Ruangan::tandaiFavorit($favorit)]);
    }

    // Detail ruangan (tab About)
    public function showDetail($id)
    {
        $ruangan = Ruangan::with(['fasilitas', 'pic'])->findOrFail($id);

        // Rating rata-rata dan jumlah ulasan dari view v_ulasan_ruangan
        $ulasan = DB::table('v_ulasan_ruangan')->where('id_ruangan', $id);
        $jumlahUlasan = $ulasan->count();
        $rataRating = $jumlahUlasan > 0 ? round($ulasan->avg('rating'), 1) : 0;

        // "Now Available": 1 = tidak ada peminjaman disetujui/berlangsung saat ini
        $tersedia = DB::table('v_ketersediaan_ruangan')->where('id_ruangan', $id)->value('tersedia_sekarang');

        return view('pages.rooms.room_detail', [
            'ruangan'      => $ruangan,
            'rataRating'   => $rataRating,
            'jumlahUlasan' => $jumlahUlasan,
            'tersedia'     => (int) $tersedia === 1,
            'favorit'      => Auth::user()->mahasiswa->favorit()->where('data_ruangan.id', $id)->exists(),
        ]);
    }

    // Ulasan ruangan. Screen pages/room_reviews milik Thalia; di sini hanya menyiapkan datanya.
    public function reviews($idRuangan)
    {
        $ruangan = Ruangan::findOrFail($idRuangan);
        $ulasan = DB::table('v_ulasan_ruangan')->where('id_ruangan', $idRuangan);
        $tersedia = DB::table('v_ketersediaan_ruangan')->where('id_ruangan', $idRuangan)->value('tersedia_sekarang');

        return view('pages.rooms.room_reviews', [
            'ruangan'      => $ruangan,
            // Top Reviews: rating tertinggi, lalu yang terbaru, ambil 3
            'topUlasan'    => (clone $ulasan)->orderByDesc('rating')->orderByDesc('tanggal_ulasan')->limit(3)->get(),
            // Semua ulasan: terbaru dulu
            'semuaUlasan'  => (clone $ulasan)->orderByDesc('tanggal_ulasan')->get(),
            'rataRating'   => round((clone $ulasan)->avg('rating') ?? 0, 1),
            'jumlahUlasan' => (clone $ulasan)->count(),
            'tersedia'     => (int) $tersedia === 1,
        ]);
    }

    // [tambahan] Tombol hati: kalau belum favorit -> simpan, kalau sudah -> hapus (tabel ruangan_favorit)
    public function toggleFavorite($id)
    {
        Ruangan::findOrFail($id); // pastikan ruangan ada
        Auth::user()->mahasiswa->favorit()->toggle($id);

        return back();
    }

    // ---------------------------------------------------------------
    // Pencarian
    // ---------------------------------------------------------------

    // Halaman awal pencarian
    public function showSearch()
    {
        $idMahasiswa = Auth::user()->mahasiswa->id;

        return view('pages.search.melakukan_search', [
            // "Recently Searched" tidak punya tabel, diganti ruangan yang terakhir dipinjam
            'terakhir'  => Ruangan::tandaiFavorit(Ruangan::terakhirDipinjam($idMahasiswa, 5)),
            'untukAnda' => Ruangan::tandaiFavorit(Ruangan::rekomendasi()->take(4)->get()),
            'fasilitas' => Fasilitas::orderBy('nama_fasilitas')->get(),
        ]);
    }

    // Tiga pintu masuk pencarian, sesuai tombol yang ditekan (README UC-05):
    //   kolom Search + Enter -> searchByName, Pop up Filter Apply -> searchByFilter,
    //   Pop up Datepicker Apply -> searchByDate.
    // Ketiganya memakai tampilkanHasil() agar kriteria yang sudah dipilih tetap digabung.
    public function searchByName(Request $request)
    {
        return $this->tampilkanHasil($request);
    }

    public function searchByFilter(Request $request)
    {
        return $this->tampilkanHasil($request);
    }

    public function searchByDate(Request $request)
    {
        return $this->tampilkanHasil($request);
    }

    // Saring data_ruangan dengan semua kriteria yang ada di URL lalu tampilkan halaman hasil
    private function tampilkanHasil(Request $request)
    {
        $q         = trim((string) $request->query('q', ''));
        $kapasitas = (array) $request->query('capacity', []);   // contoh ['20-49', '100+']
        $fasilitas = (array) $request->query('facilities', []); // id fasilitas
        $kategori  = (array) $request->query('type', []);       // 'skpb' / 'departemen'
        $tanggal   = $request->query('date');                    // YYYY-MM-DD

        $query = Ruangan::query();

        // 1) Nama: cari di nama_ruangan (dan kode_ruangan agar "SI-4202" juga ketemu)
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nama_ruangan', 'like', '%'.$q.'%')
                  ->orWhere('kode_ruangan', 'like', '%'.$q.'%');
            });
        }

        // 2) Kapasitas: boleh pilih beberapa rentang (digabung dengan OR)
        $rentangDipilih = array_intersect_key(self::RENTANG_KAPASITAS, array_flip($kapasitas));
        if ($rentangDipilih) {
            $query->where(function ($w) use ($rentangDipilih) {
                foreach ($rentangDipilih as [$min, $max]) {
                    $w->orWhere(function ($r) use ($min, $max) {
                        $r->where('kapasitas', '>=', $min);
                        if ($max !== null) {
                            $r->where('kapasitas', '<=', $max);
                        }
                    });
                }
            });
        }

        // 3) Fasilitas: ruangan harus punya SEMUA fasilitas yang dicentang
        foreach ($fasilitas as $idFasilitas) {
            $query->whereHas('fasilitas', fn ($f) => $f->where('fasilitas.id', $idFasilitas));
        }

        // 4) Kategori (Room Type)
        $kategori = array_intersect($kategori, ['skpb', 'departemen']);
        if ($kategori) {
            $query->whereIn('kategori', $kategori);
        }

        // 5) Tanggal: buang ruangan yang sudah penuh di tanggal itu
        if ($tanggal) {
            $penuh = $this->ruanganPenuh($tanggal);
            if ($penuh) {
                $query->whereNotIn('id', $penuh);
            }
        }

        $hasil = $query->orderBy('kode_ruangan')->get();

        // "More For You": rekomendasi yang tidak ada di hasil
        $lainnya = Ruangan::rekomendasi()->whereNotIn('data_ruangan.id', $hasil->pluck('id'))->take(4)->get();

        return view('pages.search.melihat_hasil_search', [
            'hasil'     => Ruangan::tandaiFavorit($hasil),
            'lainnya'   => Ruangan::tandaiFavorit($lainnya),
            'fasilitas' => Fasilitas::orderBy('nama_fasilitas')->get(),
            'chip'      => $this->chipFilter($q, $kapasitas, $kategori, $fasilitas, $tanggal),
        ]);
    }

    // ---------------------------------------------------------------
    // Bantuan
    // ---------------------------------------------------------------

    // Data tab SKPB / Departemen: rekomendasi (rating tertinggi) + semua ruangan kategori tsb
    private function dataKategori(string $kategori): array
    {
        return [
            'rekomendasi' => Ruangan::tandaiFavorit(Ruangan::rekomendasi()->where('kategori', $kategori)->take(4)->get()),
            'ruangan'     => Ruangan::tandaiFavorit(Ruangan::where('kategori', $kategori)->orderBy('kode_ruangan')->get()),
        ];
    }

    // Ruangan dengan jumlah peminjaman terbanyak (status yang benar-benar terjadi/terjadwal)
    private function palingSeringDipinjam(int $jumlah)
    {
        $hitung = DB::table('peminjaman')
            ->select('id_ruangan', DB::raw('COUNT(*) AS total'))
            ->whereIn('status', ['disetujui', 'berlangsung', 'selesai'])
            ->groupBy('id_ruangan');

        return Ruangan::joinSub($hitung, 'p', 'p.id_ruangan', '=', 'data_ruangan.id')
            ->select('data_ruangan.*')
            ->orderByDesc('p.total')
            ->orderBy('data_ruangan.kode_ruangan')
            ->take($jumlah)
            ->get();
    }

    // Ruangan yang SEMUA jamnya (08-19) sudah terpakai pada tanggal tsb.
    // Slot dianggap terpakai hanya bila status 'disetujui' atau 'berlangsung' (aturan DDL / Panduan 3.5).
    private function ruanganPenuh(string $tanggal): array
    {
        $booking = DB::table('peminjaman')
            ->where('tanggal_pakai', $tanggal)
            ->whereIn('status', ['disetujui', 'berlangsung'])
            ->get(['id_ruangan', 'waktu_mulai', 'waktu_selesai']);

        // Catat jam yang terpakai per ruangan, contoh $terpakai[3] = [8 => true, 9 => true]
        $terpakai = [];
        foreach ($booking as $b) {
            for ($jam = self::JAM_BUKA; $jam < self::JAM_TUTUP; $jam++) {
                $awalSlot  = sprintf('%02d:00:00', $jam);
                $akhirSlot = sprintf('%02d:00:00', $jam + 1);
                // Slot jam ini bertabrakan dengan booking?
                if ($b->waktu_mulai < $akhirSlot && $b->waktu_selesai > $awalSlot) {
                    $terpakai[$b->id_ruangan][$jam] = true;
                }
            }
        }

        // Penuh = semua slot (19 - 8 = 11 slot) terpakai
        $jumlahSlot = self::JAM_TUTUP - self::JAM_BUKA;
        $penuh = [];
        foreach ($terpakai as $idRuangan => $jam) {
            if (count($jam) >= $jumlahSlot) {
                $penuh[] = $idRuangan;
            }
        }

        return $penuh;
    }

    // Chip di header hasil pencarian. Tombol "x" terakhir = hapus semua filter.
    private function chipFilter($q, $kapasitas, $kategori, $fasilitas, $tanggal): array
    {
        $chip = [];
        if ($q !== '') {
            $chip[] = ['icon' => 'search', 'label' => $q];
        }
        foreach ($kapasitas as $k) {
            $chip[] = ['icon' => 'users', 'label' => $k];
        }
        foreach ($kategori as $k) {
            $chip[] = ['label' => $k === 'skpb' ? 'SKPB' : 'Departemen'];
        }
        if ($fasilitas) {
            $chip[] = ['icon' => 'air-vent', 'label' => count($fasilitas).' Facilities'];
        }
        if ($tanggal) {
            $chip[] = ['icon' => 'calendar', 'label' => date('d M', strtotime($tanggal))];
        }
        $chip[] = ['icon' => 'x', 'href' => url('/rooms/search')];

        return $chip;
    }
}
