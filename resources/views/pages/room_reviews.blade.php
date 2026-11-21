@extends('layouts.app')

@section('content')
{{-- UC-05 Membaca Ulasan Pengguna Ruangan (tab Reviews). Data dari RuanganController@reviews.
     Kepala halaman (foto, info, tab) dari components/room_header, sama dengan tab About. --}}
@php
  $topReviews = collect($topUlasan);
  $allReviews = collect($semuaUlasan);
  $tanggal = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('j F Y') : '';
@endphp

<div class="flex min-h-full flex-col bg-grey-100">
  <x-room_header :ruangan="$ruangan" :rataRating="$rataRating" :jumlahUlasan="$jumlahUlasan"
                 :tersedia="$tersedia" :active="1" :back="route('rooms.detail', ['id' => $ruangan->id])" />

  <main class="flex flex-1 flex-col gap-6 pb-8 pt-5">

    <section class="flex flex-col gap-2">
      <h2 class="px-6 text-[18px] leading-7 font-semibold text-primary-800">Top Reviews</h2>

      @if($topReviews->isEmpty())
        <p class="px-6 text-[12px] leading-4 text-grey-500">Belum ada ulasan untuk ruangan ini.</p>
      @else
        <div class="no-scrollbar -mb-4 flex gap-2 overflow-x-auto px-6 pb-4">
          @foreach($topReviews as $review)
            <x-card_review class="w-[240px] shrink-0"
              :name="data_get($review, 'nama_pengulas', '')"
              :date="$tanggal(data_get($review, 'tanggal_ulasan'))"
              :rating="data_get($review, 'rating', 0)"
              :comment="data_get($review, 'komentar', '')"
              :avatar="data_get($review, 'foto_profil')" />
          @endforeach
        </div>
      @endif
    </section>

    <section class="flex flex-col gap-2 px-6">
      <h2 class="text-[18px] leading-7 font-semibold text-primary-800">Reviews</h2>

      @if($allReviews->isEmpty())
        <p class="text-[12px] leading-4 text-grey-500">Belum ada ulasan yang tersedia.</p>
      @else
        <div class="flex flex-col gap-2">
          @foreach($allReviews as $review)
            <x-card_review
              :name="data_get($review, 'nama_pengulas', '')"
              :date="$tanggal(data_get($review, 'tanggal_ulasan'))"
              :rating="data_get($review, 'rating', 0)"
              :comment="data_get($review, 'komentar', '')"
              :avatar="data_get($review, 'foto_profil')" />
          @endforeach
        </div>
      @endif
    </section>
  </main>
</div>
@endsection
