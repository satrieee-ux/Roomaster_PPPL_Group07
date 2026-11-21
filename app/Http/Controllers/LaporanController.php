<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LaporanController extends Controller
{
    // ENUM laporan_kerusakan.kategori_kerusakan => label
    private const KATEGORI = [
        'ac' => 'Air Conditioning', 'proyektor_av' => 'Projector',
        'listrik' => 'Lighting', 'furnitur' => 'Desk/Seating',
        'jaringan' => 'Network', 'kebersihan' => 'Cleanliness', 'lainnya' => 'Others',
    ];

    // ASSUMPTION: model auth profil_pengguna punya kolom id_mahasiswa.
    // Kalau login hanya menyimpan id di session, ganti dengan (int) session('id_mahasiswa').
    private function mahasiswaId(): int
    {
        return (int) Auth::user()->id_mahasiswa;
    }

    // UC-18 (Tasia): Katalog menaruh ambilDataRuangan(QR) di RuanganController. Sengaja dipertahankan di
    // LaporanController (scan() dan create()) karena langsung terkait alur laporan. Dicatat di Satrio, 21 Nov 2026.
    public function scan()
    {
        // Simulasi hasil scan: pakai kode ruangan pertama di data dummy
        $kodeDemo = DB::table('data_ruangan')->orderBy('id')->value('kode_ruangan');

        return view('pages.Qreporting', compact('kodeDemo'));
    }

    // UC-19: form laporan. ?kode=SI-4202 (hasil scan) | ?draft=ID (lanjut draft)
    public function create(Request $request)
    {
        $draft = null;
        if ($request->filled('draft')) {
            $draft = DB::table('laporan_kerusakan')
                ->where('id', $request->query('draft'))
                ->where('id_mahasiswa', $this->mahasiswaId())
                ->where('status', 'draft')
                ->first();
        }

        $ruangan = DB::table('data_ruangan')
            ->when($draft, fn ($q) => $q->where('id', $draft->id_ruangan))
            ->when(! $draft, fn ($q) => $q->where('kode_ruangan', $request->query('kode')))
            ->first();

        abort_if(! $ruangan, 404, 'Ruangan tidak ditemukan.');

        $location = [
            'id'      => $ruangan->id,
            'room'    => $ruangan->kode_ruangan . ' - ' . $ruangan->nama_ruangan,
            'address' => $ruangan->lokasi,
        ];

        // ASSUMPTION: nama ikon harus ada di icon.blade.php
        $categories = [
            ['value' => 'ac',           'label' => 'Air Conditioning', 'icon' => 'snowflake'],
            ['value' => 'proyektor_av', 'label' => 'Projector',        'icon' => 'projector'],
            ['value' => 'listrik',      'label' => 'Lighting',         'icon' => 'lightbulb'],
            ['value' => 'furnitur',     'label' => 'Desk/Seating',     'icon' => 'armchair'],
        ];

        return view('pages.laporkerusakan', compact('location', 'categories', 'draft'));
    }

    // UC-19: simpan laporan. aksi=draft -> status draft, aksi=kirim -> status pending
    public function simpanLaporan(Request $request)
    {
        $data = $request->validate([
            'aksi'               => ['required', Rule::in(['draft', 'kirim'])],
            'id_draft'           => ['nullable', 'integer'],
            'id_ruangan'         => ['required', 'exists:data_ruangan,id'],
            'judul'              => ['required', 'string', 'max:150'],
            'kategori_kerusakan' => ['required', Rule::in(array_keys(self::KATEGORI))],
            'deskripsi_masalah'  => ['required', 'string'],
            'bukti.*'            => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:20480'],
        ]);

        $id = DB::transaction(function () use ($data, $request) {
            $payload = [
                'id_ruangan'         => $data['id_ruangan'],
                'id_mahasiswa'       => $this->mahasiswaId(),
                'judul'              => $data['judul'],
                'kategori_kerusakan' => $data['kategori_kerusakan'],
                'deskripsi_masalah'  => $data['deskripsi_masalah'],
                'tanggal_lapor'      => now()->toDateString(),
                'status'             => $data['aksi'] === 'draft' ? 'draft' : 'pending',
                'updated_at'         => now(),
            ];

            // Lanjut draft -> update baris lama, bukan buat baris baru
            $draft = ! empty($data['id_draft'])
                ? DB::table('laporan_kerusakan')
                    ->where('id', $data['id_draft'])
                    ->where('id_mahasiswa', $this->mahasiswaId())
                    ->where('status', 'draft')
                    ->first()
                : null;

            if ($draft) {
                DB::table('laporan_kerusakan')->where('id', $draft->id)->update($payload);
                $id = $draft->id;
            } else {
                $id = DB::table('laporan_kerusakan')->insertGetId($payload + ['created_at' => now()]);
            }

            foreach ($request->file('bukti', []) as $file) {
                DB::table('bukti_laporan')->insert([
                    'id_laporan'    => $id,
                    // ASSUMPTION: nilai ENUM jenis_media = 'foto' | 'video'
                    'jenis_media'   => str_starts_with($file->getMimeType(), 'video') ? 'video' : 'foto',
                    'nama_berkas'   => $file->getClientOriginalName(),
                    'path_berkas'   => $file->store('bukti_laporan', 'public'),
                    'ukuran_berkas' => $file->getSize(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            return $id;
        });

        return $data['aksi'] === 'draft'
            ? redirect()->route('report.history')
            : redirect()->route('report.submitted', $id);
    }

    // UC-19: halaman "Report Submitted"
    public function submitted(int $id)
    {
        $laporan = DB::table('laporan_kerusakan')
            ->where('id', $id)
            ->where('id_mahasiswa', $this->mahasiswaId())
            ->first();
        abort_if(! $laporan, 404);

        $kode = str_pad($laporan->id, 6, '0', STR_PAD_LEFT);   // guide 3.1

        return view('pages.laporkerusakan_submitted', compact('laporan', 'kode'));
    }

    // UC-20: ringkasan satu laporan
    public function ambilRingkasanLaporan(int $id)
    {
        $l = DB::table('laporan_kerusakan as l')
            ->join('data_ruangan as r', 'r.id', '=', 'l.id_ruangan')
            ->where('l.id', $id)
            ->where('l.id_mahasiswa', $this->mahasiswaId())
            ->select('l.*', 'r.kode_ruangan', 'r.nama_ruangan', 'r.lokasi')
            ->first();
        abort_if(! $l, 404);

        // Upload user => storage/app/public (guide 3.7)
        $evidence = DB::table('bukti_laporan')
            ->where('id_laporan', $id)
            ->where('jenis_media', '!=', 'video')
            ->pluck('path_berkas')
            ->map(fn ($p) => asset('storage/' . $p))
            ->all();

        $report = [
            'status'      => $l->status,                                  // ENUM, untuk x-status_banner
            'updated'     => Carbon::parse($l->updated_at)->diffForHumans(),
            'code'        => str_pad($l->id, 6, '0', STR_PAD_LEFT),
            'judul'       => $l->judul,
            'room'        => $l->kode_ruangan,
            'location'    => $l->lokasi,
            'date'        => Carbon::parse($l->tanggal_lapor)->translatedFormat('j F Y'),
            'category'    => self::KATEGORI[$l->kategori_kerusakan] ?? 'Others',   // SATU chip
            'description' => $l->deskripsi_masalah,
            'evidence'    => $evidence,
        ];

        return view('pages.ringkasanlaporan', compact('report'));
    }

    // UC-20: riwayat laporan milik user. ?filter=pending|diproses|selesai
    public function ambilHistoriLaporan(Request $request)
    {
        $filter = $request->query('filter');
        $filter = in_array($filter, ['pending', 'diproses', 'selesai'], true) ? $filter : null;

        $reports = DB::table('laporan_kerusakan as l')
            ->join('data_ruangan as r', 'r.id', '=', 'l.id_ruangan')
            ->where('l.id_mahasiswa', $this->mahasiswaId())
            ->when($filter, fn ($q) => $q->where('l.status', $filter))   // draft hanya muncul di tab All
            ->orderByDesc('l.created_at')
            ->select(
                'l.id', 'l.judul', 'l.tanggal_lapor as tanggal', 'l.kategori_kerusakan as kategori',
                'l.status', 'r.kode_ruangan', 'r.lokasi'
            )
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $active = ['pending' => 'Pending', 'diproses' => 'In Progress', 'selesai' => 'Resolved'][$filter] ?? 'All';

        return view('pages.riwayatlaporan', compact('reports', 'active'));
    }
}
