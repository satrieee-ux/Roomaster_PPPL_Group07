@extends('layouts.app')

@section('content')
{{-- UC-04 Memeriksa Informasi & Fasilitas Ruangan (tab About). Data dari RuanganController@showDetail:
     $ruangan      = model Ruangan (data_ruangan) + relasi fasilitas dan pic (informasi_tu)
     $rataRating   = rata-rata rating dari v_ulasan_ruangan, $jumlahUlasan = jumlah ulasan
     $tersedia     = v_ketersediaan_ruangan.tersedia_sekarang (true = Now Available)
     $favorit      = true bila ruangan ada di ruangan_favorit milik user
     Tab Reviews ada di halaman terpisah /rooms/{id}/reviews (pages/room_reviews, milik Thalia). --}}
@php
  $tabs = [
    ['label' => 'About',   'href' => url('/rooms/'.$ruangan->id)],
    ['label' => 'Reviews', 'href' => url('/rooms/'.$ruangan->id.'/reviews')],
  ];
  $pic = $ruangan->pic; // bisa null bila id_pic kosong
  $foto = $ruangan->foto_ruangan ? asset($ruangan->foto_ruangan) : asset('images/rooms/building.jpg');
  $petaUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode($ruangan->lokasi.' ITS Surabaya');
@endphp

<div class="flex min-h-full flex-col bg-grey-100">

  {{-- Tombol back & share (sticky, melayang di atas foto) --}}
  <div class="sticky top-0 z-20 h-0">
    <div class="flex items-center justify-between px-6 pt-14">
      <a href="{{ url()->previous() }}" aria-label="Kembali"
         class="flex size-8 items-center justify-center rounded-full bg-white text-grey-800 shadow-card">
        <x-icon name="arrow-left" :size="20" />
      </a>
      {{-- Bagikan: pakai fitur share bawaan HP; kalau tidak ada, salin link --}}
      <button type="button" aria-label="Bagikan"
         onclick="navigator.share ? navigator.share({ title: document.title, url: location.href }) : navigator.clipboard.writeText(location.href)"
         class="flex size-8 items-center justify-center rounded-full bg-white text-grey-800 shadow-card">
        <x-icon name="share-2" :size="20" />
      </button>
    </div>
  </div>

  {{-- Foto ruangan (DDL hanya punya satu foto, galeri dan "+10" dihapus) --}}
  <div class="relative h-[359px] shrink-0">
    <img src="{{ $foto }}" alt="{{ $ruangan->nama_ruangan }}" class="size-full rounded-b-[32px] object-cover">
  </div>

  {{-- pb-6 = jarak 24px antara isi terbawah dan tepi atas BookBar --}}
  <main class="flex flex-1 flex-col pb-6">

    {{-- Info ruangan --}}
    <div class="flex flex-col px-6 pt-6">
      <div class="flex items-center justify-between">
        <span class="text-[10px] leading-[14px] font-medium text-primary-500">
          {{ $ruangan->kode_ruangan }} · {{ $ruangan->kategori === 'skpb' ? 'SKPB' : 'Departemen' }} · {{ ucfirst($ruangan->tipe_ruangan) }}
        </span>
        <span class="flex items-center gap-2 text-[12px] leading-4 font-medium text-grey-800">
          <x-icon name="star" :size="20" class="fill-current text-secondary-300" />
          {{ number_format($rataRating, 1) }} ({{ $jumlahUlasan }} Reviews)
        </span>
      </div>

      <h1 class="mt-4 text-[24px] leading-7 font-extrabold text-grey-800">{{ $ruangan->nama_ruangan }}</h1>

      <div class="mt-1 flex items-center justify-between gap-3">
        <div class="flex items-start gap-1 text-[12px] leading-[18px] font-normal text-grey-800">
          <x-icon name="map-pin" :size="12" class="mt-[3px] shrink-0" />
          <span>{{ $ruangan->lokasi }}</span>
        </div>
        {{-- Petunjuk arah: buka Google Maps dengan kata kunci lokasi --}}
        <a href="{{ $petaUrl }}" target="_blank" rel="noopener" aria-label="Petunjuk arah"
           class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-900 text-white">
          <x-icon name="navigation" :size="20" />
        </a>
      </div>

      {{-- Pill ketersediaan dari view v_ketersediaan_ruangan --}}
      @if($tersedia)
        <span class="mt-2 inline-flex w-fit items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-[12px] leading-4 font-medium text-success-500">
          <span class="size-1.5 rounded-full bg-success-500"></span>
          Now Available
        </span>
      @else
        <span class="mt-2 inline-flex w-fit items-center gap-1.5 rounded-full bg-danger-50 px-3 py-1 text-[12px] leading-4 font-medium text-danger-500">
          <span class="size-1.5 rounded-full bg-danger-500"></span>
          In Use Now
        </span>
      @endif
    </div>

    <x-tabs :items="$tabs" :active="0" class="mt-5 px-6" />

    <div class="mt-4 flex flex-col gap-4 px-6">
      {{-- Fasilitas: kapasitas sebagai chip pertama, lalu fasilitas dari fasilitas_ruangan --}}
      <section class="flex flex-col gap-2">
        <h2 class="text-[18px] leading-7 font-semibold text-primary-800">Facilities</h2>
        <div class="grid grid-cols-3 gap-2.5">
          <div class="flex h-14 flex-col items-center justify-center gap-1 rounded-xl bg-grey-50 text-grey-800">
            <x-icon name="users" :size="20" />
            <span class="text-[12px] leading-4 font-semibold">{{ $ruangan->kapasitas }} Orang</span>
          </div>
          @foreach($ruangan->fasilitas as $f)
            <div class="flex h-14 flex-col items-center justify-center gap-1 rounded-xl bg-grey-50 px-1 text-center text-grey-800">
              <x-icon :name="$f->ikon()" :size="20" />
              <span class="text-[12px] leading-4 font-semibold">{{ $f->nama_fasilitas }}</span>
            </div>
          @endforeach
        </div>
      </section>

      <section class="flex flex-col gap-2">
        <h2 class="text-[18px] leading-7 font-semibold text-primary-800">Description</h2>
        <p class="text-[12px] leading-[18px] font-normal text-grey-800">{{ $ruangan->deskripsi ?: 'No description yet.' }}</p>
      </section>

      {{-- PIC ruangan dari informasi_tu (nama, foto_profil, rata_respon_menit). Tanpa titik online. --}}
      @if($pic)
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <img src="{{ asset($pic->foto_profil ?: 'images/avatar.jpg') }}" alt="{{ $pic->nama }}" class="size-10 shrink-0 rounded-full bg-primary-100 object-cover">
            <div class="flex flex-col">
              <span class="text-[16px] leading-6 font-semibold text-grey-800">{{ $pic->nama }}</span>
              <span class="text-[10px] leading-[14px] font-medium text-grey-500">Response Time: {{ $pic->teksRespon() }}</span>
            </div>
          </div>
          <a href="{{ url('/chat') }}" aria-label="Chat CS"
             class="flex size-10 shrink-0 items-center justify-center rounded-full bg-grey-100 text-primary-500">
            <x-icon name="message-square" :size="20" />
          </a>
        </div>
      @endif
    </div>
  </main>

  {{-- BookBar (milik Thalia): Book Now ke alur booking, hati sesuai ruangan_favorit --}}
  <x-component_bookbar :href="route('booking.form', $ruangan->id)" :favorite="$favorit" />
</div>
@endsection
