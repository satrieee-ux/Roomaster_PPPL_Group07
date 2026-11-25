{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Mengisi Form Peminjaman --}}
{{-- Diperbaiki oleh: Muhammad Satrio Adjie (5026241065), disamakan dengan gambar referensi dan perbaikan bug --}}
{{-- Form Booking langkah 1 (Details). Nama input mengikuti kolom tabel peminjaman.
     Contoh:
       <x-booking_form :id_ruangan="$ruangan->id" :kode_ruangan="$ruangan->kode_ruangan" state="date"
                       :tanggal_pakai="$tanggal_pakai" :jam_terpakai="$jamTerpakai" :nama_kegiatan="$peminjaman?->nama_kegiatan" />

     state (tahap tampilan form):
       empty = tanggal belum dipilih
       date  = tanggal sudah dipilih, jam belum
       time  = tanggal dan jam sudah dipilih (tombol Continue muncul).
               Tampilan "time" juga otomatis dipakai jika waktu_mulai dan waktu_selesai sudah terisi.
     Setelah Apply di popup jam, user TETAP di halaman ini: popup menampilkan elemen [data-time-show]
     (field Time, bar Continue) dan menyembunyikan elemen [data-time-hide] (kotak info, bar Check Availability).

     Input yang dikirim ke server:
       nama_kegiatan, tujuan, departemen_penyelenggara, tanggal_pakai, waktu_mulai, waktu_selesai

     + Satrio: pesan error validasi tampil di bawah tiap kolom (x-form_error), kolom berbingkai merah,
       isian dipertahankan lewat old(). Form diberi novalidate supaya pesan server yang tampil,
       bukan balon bawaan browser. --}}
@props([
  'id_ruangan' => null,               // data_ruangan.id (dipakai di URL)
  'kode_ruangan' => null,             // data_ruangan.kode_ruangan (ditampilkan di popup jam)
  'state' => 'empty',
  'nama_kegiatan' => null,            // peminjaman.nama_kegiatan (VARCHAR 150)
  'tujuan' => null,                   // peminjaman.tujuan (TEXT)
  'departemen_penyelenggara' => null, // peminjaman.departemen_penyelenggara (VARCHAR 100)
  'tanggal_pakai' => null,            // peminjaman.tanggal_pakai (DATE, format Y-m-d)
  'waktu_mulai' => null,              // peminjaman.waktu_mulai (TIME, misal "15:00" atau "15:00:00")
  'waktu_selesai' => null,            // peminjaman.waktu_selesai (TIME)
  'jam_terpakai' => [],               // jam (8-19) tidak tersedia dari server: terpakai ('disetujui'/'berlangsung') atau sudah lewat
])
@php
// Setelah validasi gagal, isian terakhir user (old) menang atas data draft
$nama_kegiatan            = old('nama_kegiatan', $nama_kegiatan);
$tujuan                   = old('tujuan', $tujuan);
$departemen_penyelenggara = old('departemen_penyelenggara', $departemen_penyelenggara);
$tanggal_pakai            = old('tanggal_pakai', $tanggal_pakai);
$waktu_mulai              = old('waktu_mulai', $waktu_mulai);
$waktu_selesai            = old('waktu_selesai', $waktu_selesai);

// Kolom TIME bisa berisi "15:00:00"; ambil 5 karakter pertama saja ("15:00")
$mulai = $waktu_mulai ? substr($waktu_mulai, 0, 5) : null;
$selesai = $waktu_selesai ? substr($waktu_selesai, 0, 5) : null;

// Tampilan "time" dipakai jika state = time ATAU jam sudah terisi (misal kembali dari halaman dokumen)
$showTime = $state === 'time' || ($mulai && $selesai);

// --- Tanggal ---
// Ubah tanggal_pakai menjadi objek Carbon supaya mudah diformat
$tanggal = $tanggal_pakai ? \Illuminate\Support\Carbon::parse($tanggal_pakai) : null;
$hasDate = $tanggal !== null;
$tanggalIso = $tanggal ? $tanggal->format('Y-m-d') : null;    // untuk input hidden, contoh 2026-05-20
$tanggalPendek = $tanggal ? $tanggal->format('d/m/y') : null;  // untuk tombol tanggal, contoh 20/05/26
$tanggalPanjang = $tanggal ? $tanggal->format('j F Y') : '—';  // untuk judul popup jam, contoh 20 May 2026

// --- Jam ---
// Teks jam untuk ditampilkan, contoh "15:00 - 17:00 WIB"
$teksJam = ($mulai && $selesai) ? $mulai.' - '.$selesai.' WIB' : null;

// Jam-jam yang sudah tercentang di popup jam, contoh 15:00-17:00 → [15, 16]
$jamTerpilih = [];
if ($mulai && $selesai) {
  $jamTerpilih = range((int) substr($mulai, 0, 2), (int) substr($selesai, 0, 2) - 1);
}

// --- Kelas Tailwind yang dipakai berulang ---
$kelasField = 'rounded-xl border border-grey-200 bg-grey-50 px-3 shadow-[0_1px_2px_rgba(20,26,40,0.04)] outline-hidden focus:border-primary-500';
$kelasLabel = 'text-[14px] leading-5 font-semibold text-grey-800';
$merah = 'border-danger-500!'; // bingkai merah saat kolom punya error
@endphp

<div class="flex min-h-full flex-col bg-grey-100">
  <x-header_topappbar title="Booking" endIcon="x" :endUrl="route('rooms.detail', $id_ruangan)" />

  {{-- Tombol Continue (setelah jam dipilih) mengirim form dengan POST ke /booking/{id_ruangan}.
       Controller menyimpan baris peminjaman berstatus 'draft', lalu redirect ke GET /booking/{id}/dokumen.
       Halaman pilih jam terpisah tidak dipakai.--}}
  <form id="booking-form" method="POST" class="flex flex-1 flex-col" novalidate
        action="{{ route('booking.store', $id_ruangan) }}">
    @csrf
    <main class="flex flex-1 flex-col px-6 pb-5 pt-[22px]">
      <h2 class="text-[24px] leading-7 font-extrabold text-grey-800">Details</h2>
      <x-booking_stepper :step="1" class="mt-4" />

      <div class="mt-8 flex flex-col gap-2.5">
        {{-- Nama kegiatan → peminjaman.nama_kegiatan --}}
        <label for="nama_kegiatan" class="{{ $kelasLabel }}">Activity Name</label>
        <input id="nama_kegiatan" name="nama_kegiatan" type="text" maxlength="150" required
               value="{{ $nama_kegiatan }}" placeholder="e.g. Weekly Meeting"
               aria-invalid="{{ $errors->has('nama_kegiatan') ? 'true' : 'false' }}"
               class="h-[42px] w-full {{ $kelasField }} {{ $errors->has('nama_kegiatan') ? $merah : '' }} text-[14px] leading-5 font-normal text-grey-800 placeholder:text-grey-500">
        <x-form_error name="nama_kegiatan" class="-mt-1" />

        {{-- Tujuan → peminjaman.tujuan --}}
        <label for="tujuan" class="{{ $kelasLabel }}">Purpose</label>
        <textarea id="tujuan" name="tujuan" required placeholder="Briefly describe the purpose of the booking"
                  aria-invalid="{{ $errors->has('tujuan') ? 'true' : 'false' }}"
                  class="h-[106px] w-full resize-none {{ $kelasField }} {{ $errors->has('tujuan') ? $merah : '' }} py-3 text-[14px] leading-5 font-normal text-grey-800 placeholder:text-grey-500">{{ $tujuan }}</textarea>
        <x-form_error name="tujuan" class="-mt-1" />

        {{-- Departemen → peminjaman.departemen_penyelenggara --}}
        <span class="{{ $kelasLabel }}">Department</span>
        <x-dropdown_departemen :value="$departemen_penyelenggara" />
        <x-form_error name="departemen_penyelenggara" class="-mt-1" />

        {{-- Tanggal → peminjaman.tanggal_pakai. Tombol membuka popup kalender. --}}
        <span class="{{ $kelasLabel }}">Date</span>
        <button type="button" data-date-open data-popup-open="popup-tanggal" class="flex h-[46px] items-center gap-3 text-left {{ $kelasField }} {{ $errors->has('tanggal_pakai') ? $merah : '' }}">
          <x-icon name="calendar" :size="24" class="shrink-0 text-grey-500" />
          <span data-date-text @class(['text-[16px] leading-6 font-normal', 'text-grey-800' => $hasDate, 'text-grey-500' => ! $hasDate])>{{ $tanggalPendek ?? 'DD/MM/YY' }}</span>
        </button>
        <input type="hidden" name="tanggal_pakai" value="{{ $tanggalIso }}">
        <x-form_error name="tanggal_pakai" class="-mt-1" />

        {{-- Jam → peminjaman.waktu_mulai dan peminjaman.waktu_selesai.
             Popup jam langsung mengisi kedua input hidden ini (contoh 15:00 dan 17:00). --}}
        <input type="hidden" name="waktu_mulai" value="{{ $mulai }}">
        <input type="hidden" name="waktu_selesai" value="{{ $selesai }}">

        {{-- Field Time: selalu dirender, disembunyikan sampai jam dipilih (popup jam yang menampilkannya) --}}
        <span data-time-show @class([$kelasLabel, 'hidden' => ! $showTime])>Time</span>
        <button type="button" data-time-show data-popup-open="popup-waktu" @class(['flex h-[46px] items-center gap-3 text-left '.$kelasField, 'hidden' => ! $showTime])>
          <x-icon name="clock" :size="24" class="shrink-0 text-grey-500" />
          <span data-time-label class="text-[16px] leading-6 font-normal text-grey-800">{{ $teksJam }}</span>
        </button>
        <x-form_error name="waktu_mulai" data-time-show :class="'-mt-1 '.($showTime ? '' : 'hidden')" />
        <x-form_error name="waktu_selesai" data-time-show :class="'-mt-1 '.($showTime ? '' : 'hidden')" />
      </div>

      {{-- Kotak info ketersediaan: tampil sebelum jam dipilih, disembunyikan popup jam setelah Apply --}}
      <div data-time-hide @class(['mt-8 flex gap-3 rounded-xl border border-grey-300 bg-grey-50 p-4 shadow-card', 'hidden' => $showTime])>
        <x-icon name="info" :size="20" class="mt-0.5 shrink-0 text-warning-500" />
        <div class="flex flex-col">
          <span class="text-[16px] leading-6 font-medium text-grey-800">Availability Status</span>
          <span class="text-[14px] leading-5 font-normal text-grey-600">Please fill in the date above to</span>
          <button type="button" data-avail-link class="w-fit text-left text-[14px] leading-5 font-semibold text-primary-500 underline">check room availability.</button>
        </div>
      </div>
    </main>

    {{-- Bar bawah: kedua bar selalu dirender, salah satu disembunyikan.
         Sebelum jam dipilih: Cancel + Check Availability. Sesudah jam dipilih: Continue (kirim form ke halaman dokumen). --}}
    <x-component_bookdetailbar :active="$hasDate" data-time-hide :class="$showTime ? 'hidden' : ''" />
    <x-component_bookdocbar :active="true" :submit="true" data-time-show :class="$showTime ? '' : 'hidden'" />
  </form>

  {{-- Popup pendukung form --}}
  <x-popup_booking_pilihtanggal_peminjaman :value="$tanggalIso" />
  <x-popup_booking_pilihwaktu_peminjaman :room="$kode_ruangan" :dateLabel="$tanggalPanjang" :booked="$jam_terpakai" :selected="$jamTerpilih"
                                         :slotUrl="route('booking.tanggal', $id_ruangan)" />
  <x-popup_cancelbooking :open="false" :yesUrl="route('rooms.detail', $id_ruangan)" />
</div>

<script>
  (function () {
    var f = document.getElementById('booking-form');
    if (!f) return;

    // Link "check room availability.": buka popup jam jika tanggal sudah ada, kalau belum buka kalender
    var link = f.querySelector('[data-avail-link]');
    if (link) {
      link.addEventListener('click', function () {
        var adaTanggal = f.querySelector('input[name=tanggal_pakai]').value;
        var target = adaTanggal ? f.querySelector('[data-ca-on]') : f.querySelector('[data-date-open]');
        if (target) target.click();
      });
    }
  })();
</script>
