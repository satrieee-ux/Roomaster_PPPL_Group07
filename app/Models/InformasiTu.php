<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Kontak TU yang menjadi PIC ruangan (bukan akun login).
class InformasiTu extends Model
{
    protected $table = 'informasi_tu';

    protected $fillable = ['nama', 'email', 'foto_profil', 'rata_respon_menit'];

    // Ruangan yang dipegang TU ini.
    public function ruangan()
    {
        return $this->hasMany(Ruangan::class, 'id_pic');
    }

    // Ubah rata_respon_menit menjadi teks, contoh 120 -> "~2 Hours", 45 -> "~45 Minutes".
    public function teksRespon(): string
    {
        $menit = $this->rata_respon_menit;
        if ($menit === null) {
            return '-';
        }
        if ($menit >= 60) {
            $jam = round($menit / 60);
            return '~'.$jam.($jam == 1 ? ' Hour' : ' Hours');
        }
        return '~'.$menit.' Minutes';
    }
}
