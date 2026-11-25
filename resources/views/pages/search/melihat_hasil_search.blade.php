{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-03 Mencari Ruangan (Explore Rooms) & Melihat Hasil Pencarian Ruangan --}}
@extends('layouts.app')

@section('content')
{{-- UC-03 Hasil pencarian. Data dari RuanganController@searchByName / searchByFilter / searchByDate:
     $hasil     = ruangan yang cocok dengan nama / kapasitas / fasilitas / kategori / tanggal
     $lainnya   = rekomendasi lain yang tidak ada di hasil
     $chip      = chip filter aktif di header (chip "x" = hapus semua filter)
     $fasilitas = isi Pop up Filter (tabel fasilitas) --}}
<div class="flex min-h-full flex-col bg-grey-100">
  <x-header_search :back="url('/home')" :filters="$chip" />

  <main class="flex flex-1 flex-col gap-4 pb-5 pt-4">
    <x-room_section title="Search Results" :rooms="$hasil" layout="stack" empty="No rooms match your search." />
    <x-room_section title="More For You" :rooms="$lainnya" layout="row" />
  </main>

  <x-popup_filter :action="url('/rooms/search/filter')" :fasilitas="$fasilitas" />
  <x-popup_datepicker :action="url('/rooms/search/date')" />
</div>
@endsection
