<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Daftar fasilitas (dipakai di Pop up Filter dan Room Detail).
class Fasilitas extends Model
{
    protected $table = 'fasilitas';

    protected $fillable = ['nama_fasilitas'];

    // Ruangan yang punya fasilitas ini (tabel penghubung fasilitas_ruangan).
    public function ruangan()
    {
        return $this->belongsToMany(Ruangan::class, 'fasilitas_ruangan', 'id_fasilitas', 'id_ruangan');
    }

    // Pilih ikon dari nama fasilitas. Nama yang tidak dikenal memakai ikon "check".
    public function ikon(): string
    {
        $nama = strtolower($this->nama_fasilitas);
        $daftar = [
            'ac'        => 'air-vent',
            'proyektor' => 'projector',
            'projector' => 'projector',
            'wi-fi'     => 'wifi',
            'wifi'      => 'wifi',
            'kursi'     => 'armchair',
            'seat'      => 'armchair',
            'lampu'     => 'lightbulb',
            'light'     => 'lightbulb',
        ];
        foreach ($daftar as $kata => $ikon) {
            if (str_contains($nama, $kata)) {
                return $ikon;
            }
        }
        return 'check';
    }
}
