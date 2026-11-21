{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Mengisi Form Peminjaman --}}
{{-- Sheet Choose Time. Dibuka dengan atribut data-popup-open="popup-waktu".
     Contoh: <x-popup_booking_pilihwaktu_peminjaman :room="$kode_ruangan" :booked="$jam_terpakai" :selected="[15, 16]" />
     room     = data_ruangan.kode_ruangan
     booked   = array jam (8-19) yang sudah terpakai, DIKIRIM DARI SERVER (peminjaman berstatus
                'disetujui' atau 'berlangsung' pada ruangan dan tanggal itu). Jam ini tampil oranye dan tidak bisa dipilih.
     selected = array jam yang sudah tercentang, contoh [15, 16]
     Tombol Apply (user tetap di halaman form, form TIDAK dikirim):
       - mengisi input[name=waktu_mulai] dan input[name=waktu_selesai] di form (contoh 15:00 dan 17:00)
       - menampilkan elemen [data-time-show] dan menyembunyikan elemen [data-time-hide] di form
     TODO: jika tanggal diganti setelah jam dipilih, jam perlu di-reset dan slot terpakai diambil ulang
           (dikerjakan nanti bersama endpoint Satrio saat Check Availability diklik). --}}
@props(['id' => 'popup-waktu', 'room' => '—', 'dateLabel' => '—', 'booked' => [], 'selected' => [], 'open' => false])
@php
// Kelompok jam per sesi: [nama sesi, ikon, daftar jam]. Jam operasional 08.00 - 19.00.
$groups = [
  ['Morning',   'sunrise', [8, 9, 10, 11]],
  ['Afternoon', 'sun',     [12, 13, 14, 15]],
  ['Evening',   'moon',    [16, 17, 18, 19]],
];
@endphp

@once
<style>
  @keyframes sheet-up   { from { transform: translateY(100%); } to { transform: translateY(0); } }
  @keyframes sheet-fade { from { opacity: 0; } to { opacity: 1; } }
  #popup-tanggal, #popup-waktu, [role="dialog"].items-end { animation: sheet-fade .2s ease-out; }
  #popup-tanggal > div:last-child, #popup-waktu > div:last-child, [role="dialog"].items-end > div:last-child {
    animation: sheet-up .3s cubic-bezier(.22, 1, .36, 1);
  }
</style>
@endonce

<x-popup :id="$id" :open="$open" :sheet="true">
  <div id="{{ $id }}-body" class="max-h-[calc(100dvh-80px)] overflow-y-auto overflow-x-hidden pb-2 no-scrollbar">
    <div class="relative flex items-center justify-center border-b border-grey-200 pb-3">
      <span class="text-[14px] leading-5 font-medium text-grey-800">Choose Time</span>
      <button type="button" data-popup-close aria-label="Tutup" class="absolute right-0 text-primary-500"><x-icon name="x" :size="24" /></button>
    </div>

    <h3 class="mt-4 text-[24px] leading-7 font-extrabold text-grey-800">{{ $room }}</h3>
    <p data-date-label class="text-[12px] leading-4 font-semibold text-grey-600">{{ $dateLabel }}</p>

    <div class="mt-5 flex items-center gap-4 text-[14px] leading-5 font-normal text-grey-600">
      <span class="flex items-center gap-2"><span class="size-4 rounded border border-grey-300 bg-grey-100"></span>Available</span>
      <span class="flex items-center gap-2"><span class="size-4 rounded bg-primary-900"></span>Selected</span>
      <span class="flex items-center gap-2"><span class="size-4 rounded bg-secondary-500"></span>Booked</span>
    </div>

    @foreach($groups as [$title, $icon, $hours])
      <div @class(['flex items-center gap-2 text-grey-600', 'mt-6' => $loop->first, 'mt-7' => ! $loop->first])>
        <x-icon :name="$icon" :size="20" />
        <span class="text-[12px] leading-4 font-medium uppercase tracking-wider">{{ $title }}</span>
        <span class="h-px flex-1 bg-grey-200"></span>
      </div>
      <div class="mt-3 grid grid-cols-2 gap-x-2 gap-y-4">
        @foreach($hours as $h)
          {{-- Jam terpakai (dari server) → oranye dan tidak bisa dicentang --}}
          @php $isBooked = in_array($h, $booked); @endphp
          <label @class(['block', 'cursor-pointer' => ! $isBooked, 'cursor-not-allowed' => $isBooked])>
            <input type="checkbox" class="peer sr-only" data-hour="{{ $h }}" @disabled($isBooked) @checked(in_array($h, $selected))>
            <span @class([
              'flex h-12 items-center justify-center rounded-full text-[16px] leading-6 font-medium',
              'bg-secondary-500 text-white' => $isBooked,
              'bg-grey-200 text-grey-800 peer-checked:bg-primary-900 peer-checked:text-white' => ! $isBooked,
            ])>{{ $h }}:00</span>
          </label>
        @endforeach
      </div>
    @endforeach

    <x-button type="button" variant="primary" size="medium" class="mt-8 w-full" data-time-apply>Apply</x-button>
  </div>
</x-popup>

<script>
  (function () {
    var root = document.getElementById('{{ $id }}');
    if (!root) return;

    // Ubah angka jam menjadi teks "HH:00", contoh 8 → "08:00"
    var pad = function (h) { return (h < 10 ? '0' : '') + h + ':00'; };

    // Jam terpakai dari server, untuk dicek saat Apply
    var booked = @json(array_values($booked));

    root.querySelector('[data-time-apply]').addEventListener('click', function () {
      // Kumpulkan jam yang dicentang, contoh [15, 16]
      var hs = [].slice.call(root.querySelectorAll('input[data-hour]:checked')).map(function (i) { return +i.dataset.hour; });
      if (!hs.length) return;

      // TODO: tim membolehkan jam tidak berurutan (nanti dipecah jadi beberapa peminjaman saat Finish Booking).
      // Versi pertama: pakai rentang dari jam terkecil sampai jam terbesar + 1.
      var awal = Math.min.apply(null, hs);
      var akhir = Math.max.apply(null, hs) + 1;

      // Rentang tidak boleh melewati jam terpakai, contoh pilih 8 dan 10 padahal 9 terpakai
      for (var h = awal; h < akhir; h++) {
        if (booked.indexOf(h) !== -1) {
          alert('Rentang jam melewati slot yang sudah terpakai (' + pad(h) + '). Pilih jam lain.');
          return;
        }
      }

      // Isi waktu_mulai dan waktu_selesai langsung (kolom peminjaman, format TIME "HH:MM")
      document.querySelectorAll('input[name=waktu_mulai]').forEach(function (i) { i.value = pad(awal); });
      document.querySelectorAll('input[name=waktu_selesai]').forEach(function (i) { i.value = pad(akhir); });

      // Tampilkan teks jam, contoh "15:00 - 17:00 WIB"
      var teks = pad(awal) + ' - ' + pad(akhir) + ' WIB';
      document.querySelectorAll('[data-time-label]').forEach(function (e) { e.textContent = teks; });

      // Tampilkan field Time dan bar Continue; sembunyikan kotak info dan bar Check Availability
      document.querySelectorAll('[data-time-show]').forEach(function (e) { e.classList.remove('hidden'); });
      document.querySelectorAll('[data-time-hide]').forEach(function (e) { e.classList.add('hidden'); });

      // Tutup popup (user tetap di halaman form)
      root.classList.add('hidden');
    });
  })();
</script>
