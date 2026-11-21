<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotifikasiController extends Controller
{
    // kategori ENUM => [label, tujuan tombol]. Tombol hanya ke halaman daftar (guide 5.9)
    private const KATEGORI = [
        'peminjaman' => ['Bookings', '/bookings'],
        'pesan'      => ['Messages', '/chat'],
        'laporan'    => ['Reports',  '/qreporting/history'],
        'sistem'     => ['System',   null],
    ];

    // UC-17: ?filter=peminjaman|pesan|laporan|sistem
    public function ambilNotifikasi(Request $request)
    {
        $filter = $request->query('filter');
        $filter = array_key_exists($filter, self::KATEGORI) ? $filter : null;

        $notifications = DB::table('notifikasi')
            ->where('id_mahasiswa', (int) Auth::user()->id_mahasiswa)   // ASSUMPTION: lihat LaporanController
            ->when($filter, fn ($q) => $q->where('kategori', $filter))
            ->orderByDesc('waktu')
            ->get()
            ->map(function ($n) {
                [$label, $url] = self::KATEGORI[$n->kategori] ?? ['', null];

                return [
                    'type'    => $n->kategori,                          // dipakai x-card_notification
                    'title'   => $n->judul,
                    'time'    => Carbon::parse($n->waktu)->diffForHumans(),
                    'message' => $n->isi,
                    'read'    => (bool) $n->sudah_dibaca,
                    'actions' => $url ? [['label' => 'View ' . $label, 'href' => url($url)]] : [],
                ];
            })
            ->all();

        // Label tab harus sama persis dengan label di notifikasi.blade.php
        $active = $filter ? self::KATEGORI[$filter][0] : 'All';

        return view('notifikasi', compact('notifications', 'active'));
    }
}
