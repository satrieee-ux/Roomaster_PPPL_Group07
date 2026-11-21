<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Data ruangan (tabel data_ruangan).
class Ruangan extends Model
{
    protected $table = 'data_ruangan';

    protected $fillable = [
        'kode_ruangan', 'nama_ruangan', 'kategori', 'tipe_ruangan',
        'kapasitas', 'lokasi', 'deskripsi', 'foto_ruangan', 'id_pic',
    ];

    // PIC ruangan (kontak TU), lewat data_ruangan.id_pic.
    public function pic()
    {
        return $this->belongsTo(InformasiTu::class, 'id_pic');
    }

    // Fasilitas ruangan lewat tabel penghubung fasilitas_ruangan.
    public function fasilitas()
    {
        return $this->belongsToMany(Fasilitas::class, 'fasilitas_ruangan', 'id_ruangan', 'id_fasilitas');
    }

    // Ruangan rekomendasi: urut rata-rata rating tertinggi dari view v_ulasan_ruangan.
    // Ruangan yang belum punya ulasan dianggap rating 0 (tetap ikut, di urutan bawah).
    // Pakai: Ruangan::rekomendasi()->take(4)->get()
    public function scopeRekomendasi($query)
    {
        $rataRating = DB::table('v_ulasan_ruangan')
            ->select('id_ruangan', DB::raw('AVG(rating) AS rata_rating'))
            ->groupBy('id_ruangan');

        return $query->leftJoinSub($rataRating, 'u', 'u.id_ruangan', '=', 'data_ruangan.id')
            ->select('data_ruangan.*')
            ->orderByRaw('COALESCE(u.rata_rating, 0) DESC')
            ->orderBy('data_ruangan.kode_ruangan');
    }

    // Ruangan dari peminjaman 'selesai' milik mahasiswa, ruangan unik, terbaru dulu (Recently Booked).
    public static function terakhirDipinjam(int $idMahasiswa, int $jumlah)
    {
        $idRuangan = DB::table('peminjaman')
            ->where('id_mahasiswa', $idMahasiswa)
            ->where('status', 'selesai')
            ->orderByDesc('tanggal_pakai')
            ->orderByDesc('waktu_mulai')
            ->pluck('id_ruangan')
            ->unique()        // satu ruangan cukup muncul sekali
            ->take($jumlah)
            ->values();

        // Ambil datanya lalu urutkan kembali sesuai urutan id di atas
        return self::whereIn('id', $idRuangan)->get()
            ->sortBy(fn ($r) => $idRuangan->search($r->id))
            ->values();
    }

    // Beri tanda is_favorit (true/false) pada setiap ruangan, sesuai ruangan_favorit
    // milik mahasiswa yang sedang login. Dipakai sebelum dikirim ke <x-room_section> / <x-card_room>.
    public static function tandaiFavorit($daftarRuangan)
    {
        $mahasiswa = Auth::user()?->mahasiswa;
        $idFavorit = $mahasiswa ? $mahasiswa->favorit()->pluck('data_ruangan.id')->all() : [];

        foreach ($daftarRuangan as $ruangan) {
            $ruangan->is_favorit = in_array($ruangan->id, $idFavorit);
        }

        return $daftarRuangan;
    }
}
