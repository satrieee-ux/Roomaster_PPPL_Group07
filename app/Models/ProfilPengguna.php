<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

// ===== Hanya untuk testing tanpa myitssso =====
// Model login: Auth::attempt(['username' => ..., 'password' => ...]).
// Dipasang sebagai provider "users" di config/auth.php.
class ProfilPengguna extends Authenticatable
{
    protected $table = 'profil_pengguna';

    protected $fillable = ['id_mahasiswa', 'username', 'password', 'terakhir_login'];

    // Jangan pernah ikut ditampilkan / dikirim ke view.
    protected $hidden = ['password', 'remember_token'];

    // Identitas mahasiswa pemilik akun ini.
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa');
    }
}
