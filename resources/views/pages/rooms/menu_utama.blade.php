{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-02 Melihat Dashboard (Menu Utama) --}}
@extends('layouts.app')

@section('content')
{{-- UC-02 Menu Utama. Data dari AkunController@showHome:
     $upcoming    = booking 'disetujui' terdekat (array) atau null
     $terakhir    = ruangan dari peminjaman 'selesai' milik user (Recently Booked)
     $rekomendasi = 4 ruangan dengan rating tertinggi (Recommended)
     Quick Book tetap statis (tidak ada di DDL). Nama & avatar header otomatis dari akun login. --}}
@php
  // Pintasan kategori Quick Book: [label, ikon]. Statis, tidak ada di DDL.
  $quickBook = [
    ['Academic',    'globe'],
    ['Meetings',    'user-round'],
    ['Events',      'users-round'],
    ['Group Study', 'lightbulb'],
    ['Creativity',  'palette'],
  ];
@endphp

<div class="flex min-h-full flex-col bg-grey-100">
  <x-header variant="home" searchUrl="/rooms/search" chatUrl="/chat" notifUrl="/notifikasi" />

  {{-- Jarak header ke konten 24, antar section 16, ujung scroll 20px di atas navbar sticky. --}}
  <main class="flex flex-1 flex-col gap-4 pb-5 pt-6">

    {{-- Upcoming Bookings --}}
    <section class="flex flex-col gap-3 px-6">
      <div class="flex h-6 items-center justify-between">
        <h2 class="text-[18px] leading-7 font-semibold text-primary-800">Upcoming Bookings</h2>
        <x-button href="{{ route('bookings') }}" variant="soft" size="tiny" iconRight="chevron-right" class="shrink-0 whitespace-nowrap">See All</x-button>
      </div>
      @if($upcoming)
        {{-- card_booking milik Thalia: Check-In hanya muncul bila status upcoming --}}
        <x-card_booking
          :title="$upcoming['judul']" :date="$upcoming['tanggal']" :time="$upcoming['waktu']"
          :department="$upcoming['lokasi']" :image="$upcoming['foto']" :status="$upcoming['status']"
          :is-upcoming="$upcoming['isUpcoming']"
          :href="route('bookings.summary', $upcoming['id'])" :primaryHref="route('checkin', $upcoming['id'])" />
      @else
        <p class="text-[14px] leading-5 text-grey-500">No upcoming bookings.</p>
      @endif
    </section>

    {{-- Quick Book: item 88px tanpa gap, scroll horizontal, padding kiri 24px --}}
    <section>
      <h2 class="px-6 text-[18px] leading-7 font-semibold text-primary-800">Quick Book</h2>
      <div class="no-scrollbar flex overflow-x-auto px-6">
        @foreach($quickBook as [$label, $icon])
          <a href="/rooms?category={{ urlencode($label) }}" class="flex w-[88px] shrink-0 flex-col items-center gap-2.5 py-2">
            <span class="flex size-[72px] items-center justify-center rounded-3xl bg-grey-200 text-primary-500">
              <x-icon :name="$icon" :size="36" :stroke="1.5" />
            </span>
            <span class="whitespace-nowrap text-[14px] leading-5 font-medium text-grey-800">{{ $label }}</span>
          </a>
        @endforeach
      </div>
    </section>

    {{-- Recently Booked: card kecil 200px, scroll horizontal --}}
    <section class="flex flex-col gap-3">
    <div class="flex h-7 items-center justify-between px-6">
        <h2 class="text-[18px] leading-7 font-semibold text-primary-800">Recently Booked</h2>
        <x-button href="/rooms" variant="soft" size="tiny" iconRight="chevron-right" class="shrink-0 whitespace-nowrap">See All</x-button>
      </div>
      @if($terakhir->isEmpty())
        <p class="px-6 text-[14px] leading-5 text-grey-500">No completed bookings yet.</p>
      @else
        <div class="no-scrollbar -mb-4 flex items-stretch gap-3 overflow-x-auto px-6 pb-4">
          @foreach($terakhir as $r)
            <x-card_room class="shrink-0" layout="vertical"
              :id="$r->id" :kode_ruangan="$r->kode_ruangan" :lokasi="$r->lokasi"
              :kapasitas="$r->kapasitas" :foto_ruangan="$r->foto_ruangan" :favorit="$r->is_favorit" />
          @endforeach
        </div>
      @endif
    </section>

    {{-- Recommended: card lebar penuh (342) bertumpuk --}}
    <section class="flex flex-col gap-3 px-6">
      <div class="flex h-7 items-center justify-between">
        <h2 class="text-[18px] leading-7 font-semibold text-primary-800">Recommended</h2>
        <x-button href="/rooms" variant="soft" size="tiny" iconRight="chevron-right" class="shrink-0 whitespace-nowrap">See All</x-button>
      </div>
      @foreach($rekomendasi as $r)
        <x-card_room layout="vertical_large"
          :id="$r->id" :kode_ruangan="$r->kode_ruangan" :lokasi="$r->lokasi"
          :kapasitas="$r->kapasitas" :foto_ruangan="$r->foto_ruangan" :favorit="$r->is_favorit" />
      @endforeach
    </section>
  </main>

  <x-component_navbar active="home" />
</div>
@endsection
