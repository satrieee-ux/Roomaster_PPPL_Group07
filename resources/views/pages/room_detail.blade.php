@extends('layouts.app')

@section('content')
{{-- UC-04 Memeriksa Informasi & Fasilitas Ruangan (tab About). Data dari RuanganController@showDetail:
     $ruangan      = model Ruangan (data_ruangan) + relasi fasilitas dan pic (informasi_tu)
     $rataRating   = rata-rata rating dari v_ulasan_ruangan, $jumlahUlasan = jumlah ulasan
     $tersedia     = v_ketersediaan_ruangan.tersedia_sekarang (true = Now Available)
     $favorit      = true bila ruangan ada di ruangan_favorit milik user
     Tab Reviews ada di halaman terpisah /rooms/{id}/reviews (pages/room_reviews, milik Thalia). --}}
@php
  $pic = $ruangan->pic; // bisa null bila id_pic kosong
@endphp

<div class="flex min-h-full flex-col bg-grey-100">
  <x-room_header :ruangan="$ruangan" :rataRating="$rataRating" :jumlahUlasan="$jumlahUlasan"
                 :tersedia="$tersedia" :active="0" />

  {{-- pb-6 = jarak 24px antara isi terbawah dan tepi atas BookBar --}}
  <main class="flex flex-1 flex-col pb-6">
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

      {{-- PIC ruangan dari informasi_tu (nama, foto_profil, rata_respon_menit). Titik hijau statis sesuai Figma. --}}
      @if($pic)
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <x-avatar :src="$pic->foto_profil" :name="$pic->nama" size="size-10" online />
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

  {{-- BookBar (milik Thalia): Book Now ke alur booking, hati mengirim POST /rooms/{id}/favorite --}}
  <x-component_bookbar :href="route('booking.form', $ruangan->id)" :favorite="$favorit"
                       :favoriteAction="url('/rooms/'.$ruangan->id.'/favorite')" />
</div>
@endsection
