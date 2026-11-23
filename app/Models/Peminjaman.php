<!-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) -->
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Peminjaman ruangan (tabel peminjaman). Aturan status, slot, dan pembekuan data dijaga trigger database.
class Peminjaman extends Model
{
    protected $table = 'peminjaman';

    protected $fillable = [
        'id_mahasiswa', 'id_ruangan', 'nama_kegiatan', 'tujuan', 'departemen_penyelenggara',
        'tanggal_pakai', 'waktu_mulai', 'waktu_selesai', 'status',
    ];

    // Draft milik mahasiswa untuk satu ruangan (maksimal satu per ruangan, yang terbaru bila ada lebih).
    public function scopeDraftUntuk($query, int $idMahasiswa, int $idRuangan)
    {
        return $query->where('id_mahasiswa', $idMahasiswa)
            ->where('id_ruangan', $idRuangan)
            ->where('status', 'draft')
            ->latest('id');
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }

    // Dokumen pendukung (KTM/KTP dan surat kegiatan), maksimal satu per jenis.
    public function dokumen()
    {
        return $this->hasMany(DokumenPeminjaman::class, 'id_peminjaman');
    }
}
