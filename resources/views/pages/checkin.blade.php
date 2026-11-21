{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-12 Check-In.
     Variabel yang diharapkan dari controller:
       $booking     = satu baris v_peminjaman_tampil
                      (dipakai: id, nama_tamu, kode_ruangan, tanggal_pakai, waktu_mulai, waktu_selesai)
       $bisaCheckin = true jika check-in boleh dilakukan sekarang
                      (sementara: is_upcoming = 1, atau jadwal sedang berjalan dengan status 'disetujui').
     Proceed di popup mengirim form POST (hanya @csrf). Controller mengisi waktu_checkin = NOW(),
     status = 'berlangsung', dan insert riwayat_peminjaman, lalu redirect ke halaman berhasil.
     Foto dan Additional Notes hanya tampilan, TIDAK disimpan (keputusan DDL no. 8). --}}
@extends('layouts.app')

@php
// ID booking 6 digit, contoh 123 → "#000123"
$kodeBooking = '#'.str_pad($booking->id, 6, '0', STR_PAD_LEFT);

// TODO [TIM] no. 17: bahasa tanggal belum diputuskan. Sementara format Inggris seperti file lama, contoh "Jun 7, 2026".
$tanggal = \Illuminate\Support\Carbon::parse($booking->tanggal_pakai)->format('M j, Y');

// Jam dari kolom TIME ("08:00:00") → "08:00 - 10:00 WIB"
$jam = substr($booking->waktu_mulai, 0, 5).' - '.substr($booking->waktu_selesai, 0, 5).' WIB';
@endphp

@section('content')
<div class="flex min-h-full flex-col bg-grey-100">
  <x-header_topappbar title="Check-In" :back="route('home')" :showHome="false" />

  <main class="flex flex-1 flex-col gap-4 px-6 pb-5 pt-5">
    <h2 class="text-[24px] leading-7 font-extrabold text-grey-800">Check-In Process</h2>

    {{-- Booking Details (dari v_peminjaman_tampil) --}}
    <section class="rounded-xl border border-grey-300 bg-grey-50 px-5 py-4 shadow-card">
      <h3 class="text-[16px] leading-6 font-semibold text-grey-800">Booking Details</h3>
      <div class="mt-2 flex flex-col">
        <div class="flex min-h-10 items-center justify-between">
          <span class="text-[14px] leading-5 font-normal text-grey-500">Guest Name</span>
          <span class="text-[14px] leading-5 font-semibold text-grey-800">{{ $booking->nama_tamu }}</span>
        </div>
        <div class="flex min-h-10 items-center justify-between border-t border-grey-200">
          <span class="text-[14px] leading-5 font-normal text-grey-500">Room Booked</span>
          <span class="text-[18px] leading-7 font-semibold text-grey-800">{{ $booking->kode_ruangan }}</span>
        </div>
        <div class="flex min-h-10 items-center justify-between border-t border-grey-200">
          <span class="text-[14px] leading-5 font-normal text-grey-500">Check-In Date</span>
          <span class="text-[14px] leading-5 font-medium text-grey-800">{{ $tanggal }}</span>
        </div>
        <div class="flex min-h-10 items-center justify-between border-t border-grey-200">
          <span class="text-[14px] leading-5 font-normal text-grey-500">Time</span>
          <span class="text-[14px] leading-5 font-medium text-grey-800">{{ $jam }}</span>
        </div>
        <div class="flex min-h-10 items-center justify-between border-t border-grey-200">
          <span class="text-[14px] leading-5 font-normal text-grey-500">Reservation ID</span>
          <span class="text-[14px] leading-5 font-medium text-grey-800">{{ $kodeBooking }}</span>
        </div>
      </div>
    </section>

    {{-- Standard Procedure --}}
    <section class="flex gap-3 rounded-xl border border-grey-300 bg-grey-50 p-4 shadow-card">
      <x-icon name="alert-circle" :size="24" class="mt-px shrink-0 text-warning-500" />
      <div class="flex flex-col">
        <h3 class="text-[16px] leading-6 font-medium text-grey-800">Standard Procedure</h3>
        <p class="text-[14px] leading-5 font-normal text-grey-600">
          Please document the initial state of the room before using the room. Any form of failure of compliance will be handled according Roomaster's
          <a href="#" class="font-semibold text-primary-500 underline">user Terms &amp; Conditions.</a>
        </p>
      </div>
    </section>

    <hr class="border-grey-300">

    {{-- Foto kondisi awal: hanya tampilan, tidak disimpan (keputusan DDL no. 8) --}}
    <h3 class="text-[18px] leading-7 font-semibold text-grey-800">Initial Condition Documentation</h3>
    <x-media_upload />

    <hr class="border-grey-300">

    {{-- Additional Notes: hanya tampilan, tidak disimpan (tanpa atribut name, di luar form) --}}
    <div class="flex flex-col gap-2">
      <label for="notes" class="text-[14px] leading-5 font-semibold text-grey-800">Additional Notes</label>
      <textarea id="notes" rows="4" placeholder="Note any existing damages, odors, or missing items here..."
        class="h-[120px] w-full resize-none rounded-lg border border-grey-600 bg-white px-4 py-3 text-[14px] leading-5 font-normal text-grey-800 outline-none placeholder:text-grey-400"></textarea>
    </div>
  </main>

  {{-- Bar bawah sticky --}}
  <div class="sticky bottom-0 z-30 bg-grey-100 px-6 pb-5">
    <div class="flex flex-col gap-3 border-t border-grey-300 pt-4">
      {{-- TODO [TIM] B5: jendela check-in 1 jam (is_upcoming) atau 30 menit belum diputuskan.
           Jika berubah, ubah juga teks di bawah tombol nonaktif. --}}
      @if($bisaCheckin)
        <x-button type="button" variant="primary" size="large" class="w-full" data-popup-open="popup-checkin">Check In</x-button>
      @else
        <x-button type="button" variant="disabled" size="large" class="w-full">Check In</x-button>
        <p class="text-center text-[12px] leading-4 font-medium text-warning-500">Check-in opens 1 hour before the booking starts.</p>
      @endif
      <p class="text-center text-[12px] leading-4 font-normal text-grey-600">By checking in, you agree to the facility usage terms and conditions.</p>
    </div>
  </div>

  {{-- Form check-in: hanya @csrf, id booking ada di URL. Dikirim oleh tombol Proceed di popup.--}}
  <form id="form-checkin" method="POST" action="{{ route('checkin.store', $booking->id) }}" class="hidden">
    @csrf
  </form>

  <x-popup_checkin form="form-checkin" />
</div>
@endsection
