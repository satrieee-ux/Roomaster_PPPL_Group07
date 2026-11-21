{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-03 Mencari Ruangan (Explore Rooms) & Melihat Hasil Pencarian Ruangan --}}
@extends('layouts.app')

@section('content')
{{-- UC-03 Halaman pencarian. Data dari RuanganController@showSearch:
     $terakhir  = ruangan yang terakhir dipinjam (pengganti "Recently Searched", tidak ada tabelnya)
     $untukAnda = rekomendasi rating tertinggi
     $fasilitas = isi Pop up Filter (tabel fasilitas)
     Ketik di kolom Search lalu Enter, atau pakai ikon filter / kalender. --}}
<div class="flex min-h-full flex-col bg-grey-100">
  <x-header_search :back="url('/home')" />

  <main class="flex flex-1 flex-col gap-4 pb-5 pt-5">
    <x-room_section title="Recently Booked" :rooms="$terakhir" layout="row" empty="No completed bookings yet." />
    <x-room_section title="For You" :rooms="$untukAnda" layout="list" />
  </main>

  <x-popup_filter :action="url('/rooms/search/filter')" :fasilitas="$fasilitas" />
  <x-popup_datepicker :action="url('/rooms/search/date')" />
</div>
@endsection
