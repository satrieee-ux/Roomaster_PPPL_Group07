<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PesanController extends Controller
{
    // UC-15: kirim pesan. Input bernama isi_pesan (lihat component_chatbar)
    public function addMessage(Request $request, int $id)
    {
        $me = (int) Auth::user()->id_mahasiswa;   // ASSUMPTION: lihat LaporanController

        $request->validate(['isi_pesan' => ['required', 'string', 'max:1000']]);

        $ada = DB::table('percakapan')
            ->where('id', $id)
            ->where(fn ($q) => $q->where('id_mahasiswa_1', $me)->orWhere('id_mahasiswa_2', $me))
            ->exists();
        abort_if(! $ada, 404);

        DB::table('pesan')->insert([
            'id_percakapan' => $id,
            'id_pengirim'   => $me,
            'isi_pesan'     => $request->input('isi_pesan'),
            'waktu_kirim'   => now(),
            'sudah_dibaca'  => 0,
        ]);

        return redirect()->route('chat.show', $id);
    }
}
