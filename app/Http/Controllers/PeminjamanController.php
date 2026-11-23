<?php

// Dibuat oleh: Muhammad Satrio Adjie (5026241065), kerangka method sesuai Katalog Penamaan.
// UC yang dicakup: UC-06, UC-07, UC-11, UC-13 (Thalia); UC-08, UC-09, UC-10, UC-12 (Thoriq).
// Isi method dikerjakan PIC masing-masing; tanda UC-xx (Nama) menunjukkan pemilik isinya.

namespace App\Http\Controllers;

use App\Models\DokumenPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PeminjamanController extends Controller
{
    // Tab di halaman Your Bookings -> status peminjaman (DDL).
    // [TIM] Asumsi: draft dan revisi masuk tab Pending; ditolak dan dibatalkan masuk History.
    private const KATEGORI_STATUS = [
        'active'  => ['disetujui', 'berlangsung'],
        'pending' => ['draft', 'pending', 'revisi'],
        'history' => ['ditolak', 'selesai', 'dibatalkan'],
    ];

    // UC-07 (Thalia) -> pages.daftar_bookings
    // + Satrio: isi awal agar /bookings bisa dibuka; PIC boleh menyesuaikan
    public function daftarPengajuan()
    {
        return $this->tampilkanDaftar('all');
    }

    // UC-07 (Thalia): kategori = active | pending | history (kunci KATEGORI_STATUS).
    public function filterPengajuan(string $kategori)
    {
        return $this->tampilkanDaftar($kategori);
    }

    // Dipakai daftarPengajuan() dan filterPengajuan(): ambil peminjaman milik user, saring per kategori,
    // ubah tiap baris menjadi props <x-card_booking>, lalu tampilkan pages.daftar_bookings.
    private function tampilkanDaftar(string $kategori)
    {
        $idMahasiswa = (int) Auth::user()->id_mahasiswa;

        // Data dari view v_peminjaman_tampil + lokasi dan foto_ruangan dari data_ruangan
        $rows = DB::table('v_peminjaman_tampil AS p')
            ->join('data_ruangan AS r', 'r.id', '=', 'p.id_ruangan')
            ->select('p.*', 'r.lokasi', 'r.foto_ruangan')
            ->where('p.id_mahasiswa', $idMahasiswa)
            ->when(isset(self::KATEGORI_STATUS[$kategori]),
                fn ($q) => $q->whereIn('p.status', self::KATEGORI_STATUS[$kategori]))
            ->orderByDesc('p.tanggal_pakai')
            ->orderByDesc('p.waktu_mulai')
            ->get();

        // Satu array per kartu, nama kuncinya mengikuti props <x-card_booking>
        $bookings = $rows->map(fn ($b) => [
            'id'          => $b->id,
            'title'       => $b->kode_ruangan,
            'date'        => Carbon::parse($b->tanggal_pakai)->format('d F Y'),
            'time'        => substr($b->waktu_mulai, 0, 5).' - '.substr($b->waktu_selesai, 0, 5).' WIB',
            'location'    => $b->lokasi,
            'image'       => $b->foto_ruangan,
            'status'      => $b->status,
            'isUpcoming'  => (bool) $b->is_upcoming,
            'href'        => route('bookings.summary', $b->id),
            // Tombol utama: Check-In (upcoming) atau Continue (draft, kembali ke form ruangan itu)
            'primaryHref' => $b->status === 'draft'
                ? route('booking.form', ['id' => $b->id_ruangan, 'lanjut' => 1])
                : route('checkin', $b->id),
        ])->all();

        return view('pages.daftar_bookings', [
            'bookings'  => $bookings,
            'activeTab' => isset(self::KATEGORI_STATUS[$kategori]) ? $kategori : 'all',
        ]);
    }

        // UC-06 (Thalia) -> pages.booking_summary
    public function riwayat(int $id) {}

    // =====================================================================
    // Alur booking UC-08 s.d. UC-11. Di route sebelum pengajuan dikirim, {id} = id RUANGAN.
    // Satu mahasiswa boleh punya banyak draft, tetapi hanya satu per ruangan.
    // Aturan bisnis (status, slot, pembekuan data) tetap dijaga trigger database.
    // =====================================================================

    private const JENIS_DOKUMEN = ['ktm_ktp', 'surat_kegiatan'];
    private const JAM_AWAL = 8;    // jam operasional di popup pilih jam: 08:00 s.d. 19:00 (slot terakhir 19-20)
    private const JAM_AKHIR = 19;

    private function idMahasiswa(): int
    {
        return (int) Auth::user()->id_mahasiswa;
    }

    // Draft milik user untuk ruangan ini, atau null.
    private function draftSaya(int $idRuangan): ?Peminjaman
    {
        return Peminjaman::draftUntuk($this->idMahasiswa(), $idRuangan)->first();
    }

    // Jam (8-19) yang ditahan peminjaman 'disetujui'/'berlangsung' di ruangan dan tanggal itu.
    // Peminjaman 13:00-15:00 menjadi [13, 14]. Draft dan pending tidak menahan slot.
    private function jamTerpakai(int $idRuangan, string $tanggal): array
    {
        $jam = [];
        $rows = DB::table('peminjaman')
            ->where('id_ruangan', $idRuangan)
            ->where('tanggal_pakai', $tanggal)
            ->whereIn('status', ['disetujui', 'berlangsung'])
            ->get(['waktu_mulai', 'waktu_selesai']);

        foreach ($rows as $r) {
            $mulai = (int) substr($r->waktu_mulai, 0, 2);
            $akhir = (int) substr($r->waktu_selesai, 0, 2) + (substr($r->waktu_selesai, 3, 2) !== '00' ? 1 : 0);
            for ($h = $mulai; $h < $akhir; $h++) {
                $jam[$h] = $h;
            }
        }
        ksort($jam);

        return array_values($jam);
    }

    // Jam yang sudah lewat dari waktu sekarang (zona waktu aplikasi). Tanggal lampau = semua jam.
    private function jamLewat(string $tanggal): array
    {
        $hariIni = now()->toDateString();
        if ($tanggal > $hariIni) {
            return [];
        }

        $jam = [];
        for ($h = self::JAM_AWAL; $h <= self::JAM_AKHIR; $h++) {
            if ($tanggal < $hariIni || now()->setTime($h, 0)->lt(now())) {
                $jam[] = $h;
            }
        }

        return $jam;
    }

    // Jam yang tidak bisa dipilih di popup: terpakai ATAU sudah lewat.
    private function jamTidakTersedia(int $idRuangan, string $tanggal): array
    {
        $gabung = array_unique(array_merge($this->jamTerpakai($idRuangan, $tanggal), $this->jamLewat($tanggal)));
        sort($gabung);

        return array_values($gabung);
    }

    // true jika rentang jam menabrak peminjaman 'disetujui'/'berlangsung' (sama dengan fn_slot_tersedia).
    private function slotBentrok(int $idRuangan, string $tanggal, string $mulai, string $selesai): bool
    {
        return DB::table('peminjaman')
            ->where('id_ruangan', $idRuangan)
            ->where('tanggal_pakai', $tanggal)
            ->whereIn('status', ['disetujui', 'berlangsung'])
            ->where('waktu_mulai', '<', $selesai)
            ->where('waktu_selesai', '>', $mulai)
            ->exists();
    }

    // Data untuk halaman dokumen (UC-09 dan UC-10).
    private function dataDokumen(Ruangan $ruangan, Peminjaman $draft): array
    {
        return [
            'ruangan'    => $ruangan,
            'peminjaman' => $draft,
            'dokumen'    => $draft->dokumen()->get()->keyBy('jenis_dokumen'),
        ];
    }

    // Hapus draft beserta file dokumennya (baris dokumen dan riwayat ikut terhapus oleh ON DELETE CASCADE).
    private function hapusDraft(Peminjaman $draft): void
    {
        $paths = $draft->dokumen()->pluck('path_berkas')->all();
        $draft->delete();
        Storage::disk('local')->delete($paths);
    }

    // UC-08 (Thoriq) -> pages.booking_isiformulir_peminjaman
    // Tanpa ?lanjut=1 dan draft ada: form kosong + popup "lanjutkan atau timpa". Dengan ?lanjut=1: form terisi draft.
    public function isiFormulir(Request $request, int $id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $draft = $this->draftSaya($id);
        $lanjut = $draft !== null && $request->boolean('lanjut');

        $tanggal = $mulai = $selesai = null;
        if ($lanjut && $draft->tanggal_pakai >= now()->toDateString()) {
            // Tanggal draft yang sudah lewat tidak dipakai lagi; user memilih ulang
            $tanggal = Carbon::parse($draft->tanggal_pakai)->toDateString();
            $mulai = substr($draft->waktu_mulai, 0, 5);
            $selesai = substr($draft->waktu_selesai, 0, 5);
        }

        return view('pages.booking_isiformulir_peminjaman', [
            'ruangan'       => $ruangan,
            'peminjaman'    => $lanjut ? $draft : null,
            'draftAda'      => $draft !== null && ! $lanjut,
            'tanggal_pakai' => $tanggal,
            'waktu_mulai'   => $mulai,
            'waktu_selesai' => $selesai,
            'jamTerpakai'   => $tanggal ? $this->jamTidakTersedia($id, $tanggal) : [],
        ]);
    }

    // UC-08 (Thoriq): cek jam yang tidak tersedia pada ruangan dan tanggal.
    // ?tanggal=Y-m-d. JSON (dipakai popup pilih jam) atau halaman form dengan tanggal terpilih.
    public function cekKetersediaan(Request $request, int $id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $request->validate(['tanggal' => 'required|date_format:Y-m-d']);
        $tanggal = $request->query('tanggal');
        $jam = $this->jamTidakTersedia($id, $tanggal);

        if ($request->expectsJson()) {
            return response()->json(['tanggal' => $tanggal, 'booked' => $jam]);
        }

        return view('pages.booking_pilihtanggal_peminjaman', [
            'ruangan'       => $ruangan,
            'peminjaman'    => $this->draftSaya($id),
            'tanggal_pakai' => $tanggal,
            'jamTerpakai'   => $jam,
        ]);
    }

    // UC-08 (Thoriq): validasi formulir, lalu simpan sebagai draft (buat baru atau perbarui draft ruangan ini).
    public function simpanDraft(Request $request, int $id)
    {
        Ruangan::findOrFail($id);

        // + Satrio: aturan sesuai DDL peminjaman; pesan per kolom tampil di bawah field (x-form_error)
        $data = $request->validate([
            'nama_kegiatan'            => 'required|string|max:150',
            'tujuan'                   => 'required|string',
            'departemen_penyelenggara' => 'required|string|max:100',
            'tanggal_pakai'            => 'required|date|after_or_equal:today',
            'waktu_mulai'              => 'required|date_format:H:i',
            'waktu_selesai'            => 'required|date_format:H:i|after:waktu_mulai',
        ], [
            'required'            => ':attribute wajib diisi.',
            'max.string'          => ':attribute maksimal :max karakter.',
            'after_or_equal'      => ':attribute tidak boleh sebelum hari ini.',
            'waktu_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ], [
            'nama_kegiatan' => 'Nama kegiatan', 'tujuan' => 'Tujuan',
            'departemen_penyelenggara' => 'Departemen', 'tanggal_pakai' => 'Tanggal',
            'waktu_mulai' => 'Jam mulai', 'waktu_selesai' => 'Jam selesai',
        ]);

        // Jam yang sudah lewat hari ini dan slot yang sudah terpakai tidak boleh disimpan
        if (Carbon::parse($data['tanggal_pakai'].' '.$data['waktu_mulai'])->lt(now())) {
            throw ValidationException::withMessages(['waktu_mulai' => 'Jam mulai sudah lewat.']);
        }
        if ($this->slotBentrok($id, $data['tanggal_pakai'], $data['waktu_mulai'], $data['waktu_selesai'])) {
            throw ValidationException::withMessages(['waktu_mulai' => 'Jam tersebut sudah terpakai. Pilih jam lain.']);
        }

        $draft = $this->draftSaya($id);
        if ($draft) {
            $draft->update($data);
        } else {
            Peminjaman::create($data + [
                'id_mahasiswa' => $this->idMahasiswa(),
                'id_ruangan'   => $id,
                'status'       => 'draft',
            ]);
        }

        return redirect()->route('booking.dokumen', $id);
    }

    // Popup "timpa": buang draft lama ruangan ini, lalu mulai form kosong.
    public function timpaDraft(int $id)
    {
        Ruangan::findOrFail($id);
        if ($draft = $this->draftSaya($id)) {
            $this->hapusDraft($draft);
        }

        return redirect()->route('booking.form', $id);
    }

    // UC-09 (Thoriq) -> pages.booking_melampirkan_dokumenpendukung
    public function halamanDokumen(int $id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $draft = $this->draftSaya($id);
        if (! $draft) {
            return redirect()->route('booking.form', $id);
        }

        return view('pages.booking_melampirkan_dokumenpendukung', $this->dataDokumen($ruangan, $draft));
    }

    // UC-09 (Thoriq): simpan berkas. Slot yang tidak diisi memakai dokumen lama. Jenis sama = ganti file lama.
    public function uploadDokumen(Request $request, int $id)
    {
        Ruangan::findOrFail($id);
        $draft = $this->draftSaya($id);
        if (! $draft) {
            return redirect()->route('booking.form', $id);
        }

        $ada = $draft->dokumen()->get()->keyBy('jenis_dokumen');
        $aturan = [];
        foreach (self::JENIS_DOKUMEN as $jenis) {
            $aturan[$jenis] = [$ada->has($jenis) ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        $request->validate($aturan, [
            'required' => 'Berkas :attribute wajib diunggah.',
            'mimes'    => 'Format berkas harus PDF, JPG, atau PNG.',
            'max'      => 'Ukuran berkas maksimal 5 MB.',
            'file'     => 'Berkas gagal diunggah. Coba lagi.',
            'uploaded' => 'Berkas gagal diunggah. Coba lagi.',
        ], ['ktm_ktp' => 'KTM/KTP', 'surat_kegiatan' => 'Surat Peminjaman']);

        $pathLama = [];
        DB::transaction(function () use ($request, $draft, $ada, &$pathLama) {
            foreach (self::JENIS_DOKUMEN as $jenis) {
                if (! $request->hasFile($jenis)) {
                    continue;
                }
                $file = $request->file($jenis);

                if ($lama = $ada->get($jenis)) {
                    $pathLama[] = $lama->path_berkas;
                    $lama->delete();
                }

                DokumenPeminjaman::create([
                    'id_peminjaman' => $draft->id,
                    'jenis_dokumen' => $jenis,
                    'nama_berkas'   => $file->getClientOriginalName(),
                    'path_berkas'   => $file->store('dokumen', 'local'),
                    'ukuran_berkas' => $file->getSize(),
                    'status'        => 'draft',
                ]);
            }
        });
        // File lama dihapus setelah transaksi berhasil
        Storage::disk('local')->delete($pathLama);

        return redirect()->route('booking.ringkasan', $id);
    }

    // UC-10 (Thoriq) -> pages.booking_berkasterunggah (halaman dokumen dengan aksi hapus)
    public function ambilDokumen(int $id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $draft = $this->draftSaya($id);
        if (! $draft) {
            return redirect()->route('booking.form', $id);
        }

        return view('pages.booking_berkasterunggah', $this->dataDokumen($ruangan, $draft));
    }

    // UC-10 (Thoriq): hapus satu dokumen (baris database dan filenya), lalu muat ulang halaman.
    public function hapusDokumen(Request $request, int $id)
    {
        Ruangan::findOrFail($id);
        $draft = $this->draftSaya($id);
        if (! $draft) {
            return redirect()->route('booking.form', $id);
        }

        $request->validate(['jenis_dokumen' => 'required|in:'.implode(',', self::JENIS_DOKUMEN)]);

        $dok = $draft->dokumen()->where('jenis_dokumen', $request->input('jenis_dokumen'))->first();
        if ($dok) {
            $path = $dok->path_berkas;
            $dok->delete();
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('booking.dokumen.terunggah', $id);
    }

    // UC-11 (Thalia) -> pages.booking_ringkasanpeminjaman
    public function ambilDataPeminjaman(int $id)
    {
        $ruangan = Ruangan::with('fasilitas')->findOrFail($id);
        $draft = $this->draftSaya($id);
        if (! $draft) {
            return redirect()->route('booking.form', $id);
        }

        $dokumen = $draft->dokumen()->get()->sortBy('jenis_dokumen')->values();
        if ($dokumen->pluck('jenis_dokumen')->unique()->count() < count(self::JENIS_DOKUMEN)) {
            return redirect()->route('booking.dokumen', $id)
                ->withErrors(['surat_kegiatan' => 'Lengkapi dokumen KTM/KTP dan Surat Peminjaman terlebih dulu.']);
        }

        return view('pages.booking_ringkasanpeminjaman', [
            'id'      => $id,
            'booking' => [
                'code'          => str_pad($draft->id, 6, '0', STR_PAD_LEFT),
                'room'          => $ruangan->nama_ruangan ?: $ruangan->kode_ruangan,
                'location'      => $ruangan->lokasi,
                'capacity'      => $ruangan->kapasitas.' Orang',
                'facilities'    => $ruangan->fasilitas->map(fn ($f) => ['icon' => $f->ikon(), 'label' => $f->nama_fasilitas])->all(),
                'date'          => Carbon::parse($draft->tanggal_pakai)->format('d F Y'),
                'time'          => substr($draft->waktu_mulai, 0, 5).' - '.substr($draft->waktu_selesai, 0, 5).' WIB',
                'activity_name' => $draft->nama_kegiatan,
                'description'   => $draft->tujuan,
                'documents'     => $dokumen->map(fn ($d) => [
                    'nama_berkas'   => $d->nama_berkas,
                    'ukuran_berkas' => $d->ukuran_berkas,
                    'status'        => $d->status,
                ])->all(),
            ],
        ]);
    }

    // UC-11 (Thalia): kirim pengajuan (draft -> pending). Trigger database mengisi tanggal_pengajuan,
    // riwayat, dan notifikasi. Setelah itu {id} di URL berikutnya = id PEMINJAMAN.
    public function simpanPengajuan(Request $request, int $id)
    {
        Ruangan::findOrFail($id);
        $draft = $this->draftSaya($id);
        if (! $draft) {
            return redirect()->route('booking.form', $id);
        }

        $jumlahJenis = $draft->dokumen()->distinct()->count('jenis_dokumen');
        if ($jumlahJenis < count(self::JENIS_DOKUMEN)) {
            return redirect()->route('booking.dokumen', $id)
                ->withErrors(['surat_kegiatan' => 'Lengkapi dokumen KTM/KTP dan Surat Peminjaman terlebih dulu.']);
        }

        $tanggal = Carbon::parse($draft->tanggal_pakai)->toDateString();
        $mulai = substr($draft->waktu_mulai, 0, 5);
        $selesai = substr($draft->waktu_selesai, 0, 5);

        if (Carbon::parse($tanggal.' '.$mulai)->lt(now())) {
            return redirect()->route('booking.form', ['id' => $id, 'lanjut' => 1])
                ->withErrors(['tanggal_pakai' => 'Jadwal sudah lewat. Pilih tanggal dan jam yang baru.']);
        }
        if ($this->slotBentrok($id, $tanggal, $mulai, $selesai)) {
            return redirect()->route('booking.form', ['id' => $id, 'lanjut' => 1])
                ->withErrors(['waktu_mulai' => 'Slot ruangan sudah terpakai pada jam tersebut. Pilih jam lain.']);
        }

        try {
            $draft->update(['status' => 'pending']);
        } catch (QueryException $e) {
            // Pesan dari trigger/aturan database (SQLSTATE 45000)
            return redirect()->route('booking.ringkasan', $id)
                ->withErrors(['kirim' => 'Pengajuan gagal dikirim. '.($e->errorInfo[2] ?? '')]);
        }

        return redirect()->route('booking.berhasil', $draft->id);
    }

    // UC-11 (Thalia) -> pages.booking_berhasil. {id} = id peminjaman yang baru dikirim.
    public function berhasil(int $id)
    {
        $p = Peminjaman::where('id', $id)
            ->where('id_mahasiswa', $this->idMahasiswa())
            ->where('status', '<>', 'draft')
            ->firstOrFail();

        return view('pages.booking_berhasil', ['id' => $p->id]);
    }

    // UC-12 Check-In (Thoriq) dan UC-13 Check-Out (Thalia)
    // $aksi = 'checkin' | 'checkout' -> pages.checkin / checkout / berhasilcheckin / berhasilcheckout
    public function halamanRoomState(int $id, string $aksi) {}
    public function updateRoomState(Request $request, int $id, string $aksi) {}
}
