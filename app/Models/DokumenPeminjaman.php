<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Dokumen pendukung peminjaman (tabel dokumen_peminjaman). File fisik ada di storage, di sini hanya path-nya.
class DokumenPeminjaman extends Model
{
    protected $table = 'dokumen_peminjaman';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_peminjaman', 'jenis_dokumen', 'nama_berkas', 'path_berkas', 'ukuran_berkas', 'status',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman');
    }
}
