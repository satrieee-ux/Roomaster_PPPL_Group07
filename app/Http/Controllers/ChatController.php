<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    private function mahasiswaId(): int
    {
        return (int) Auth::user()->id_mahasiswa;   // ASSUMPTION: lihat LaporanController
    }

    // UC-16: daftar percakapan. Lawan bicara = mahasiswa_its (guide 5.7)
    public function ambilDaftarPercakapan()
    {
        $me = $this->mahasiswaId();

        $conversations = DB::table('percakapan as p')
            ->where(fn ($q) => $q->where('p.id_mahasiswa_1', $me)->orWhere('p.id_mahasiswa_2', $me))
            ->get()
            ->map(function ($p) use ($me) {
                $otherId = $p->id_mahasiswa_1 == $me ? $p->id_mahasiswa_2 : $p->id_mahasiswa_1;
                $other   = DB::table('mahasiswa_its')->where('id', $otherId)->first();

                // ASSUMPTION: kolom pesan.id_percakapan
                $last = DB::table('pesan')
                    ->where('id_percakapan', $p->id)
                    ->orderByDesc('waktu_kirim')
                    ->first();

                $unread = DB::table('pesan')
                    ->where('id_percakapan', $p->id)
                    ->where('id_pengirim', '!=', $me)
                    ->where('sudah_dibaca', 0)
                    ->count();

                return [
                    'id'      => $p->id,
                    'name'    => $other->nama ?? 'Unknown',
                    'avatar'  => $other->foto_profil ?? null,
                    'preview' => $last->isi_pesan ?? '',
                    'time'    => $last ? Carbon::parse($last->waktu_kirim)->format('H:i') : '',
                    'unread'  => $unread,
                    'sort'    => $last->waktu_kirim ?? '',
                ];
            })
            ->sortByDesc('sort')
            ->values()
            ->all();

        return view('pages.percakapan', compact('conversations'));
    }

    // UC-15: buka satu percakapan, tandai pesan lawan sudah dibaca
    public function show(int $id)
    {
        $me = $this->mahasiswaId();

        $p = DB::table('percakapan')
            ->where('id', $id)
            ->where(fn ($q) => $q->where('id_mahasiswa_1', $me)->orWhere('id_mahasiswa_2', $me))
            ->first();
        abort_if(! $p, 404);

        $otherId  = $p->id_mahasiswa_1 == $me ? $p->id_mahasiswa_2 : $p->id_mahasiswa_1;
        $other    = DB::table('mahasiswa_its')->where('id', $otherId)->first();
        $meAvatar = DB::table('mahasiswa_its')->where('id', $me)->value('foto_profil');

        DB::table('pesan')
            ->where('id_percakapan', $id)
            ->where('id_pengirim', '!=', $me)
            ->where('sudah_dibaca', 0)
            ->update(['sudah_dibaca' => 1]);

        $rows = DB::table('pesan')->where('id_percakapan', $id)->orderBy('waktu_kirim')->get();

        $messages = $rows->map(fn ($m) => [
            'side'   => $m->id_pengirim == $me ? 'out' : 'in',     // sesuai x-chat_bubble
            'text'   => $m->isi_pesan,
            'time'   => Carbon::parse($m->waktu_kirim)->format('H:i'),
            'avatar' => $m->id_pengirim == $me ? $meAvatar : ($other->foto_profil ?? null),
        ])->all();

        $last = $rows->last();
        $date = $last ? Carbon::parse($last->waktu_kirim)->translatedFormat('l, d M') : '';

        // Nama variabel $admin dipertahankan supaya pesan.blade.php tetap cocok
        $admin = [
            'id'     => $id,
            'name'   => $other->nama ?? 'Unknown',
            'avatar' => $other->foto_profil ?? null,
        ];

        return view('pages.pesan', compact('admin', 'date', 'messages'));
    }
}
