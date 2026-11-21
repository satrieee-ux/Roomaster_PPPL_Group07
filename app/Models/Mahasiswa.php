<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Data identitas mahasiswa (dari MyITS SSO). Tidak menyimpan password.
class Mahasiswa extends Model
{
    protected $table = 'mahasiswa_its';

    protected $fillable = ['nrp', 'nama', 'email_its', 'foto_profil'];

    // Akun testing (username + password) milik mahasiswa ini, bisa kosong.
    public function profil()
    {
        return $this->hasOne(ProfilPengguna::class, 'id_mahasiswa');
    }

    // Ruangan yang difavoritkan. Tabel ruangan_favorit hanya punya created_at,
    // dan nilainya diisi otomatis oleh database (DEFAULT CURRENT_TIMESTAMP).
    public function favorit()
    {
        return $this->belongsToMany(Ruangan::class, 'ruangan_favorit', 'id_mahasiswa', 'id_ruangan');
    }
}
