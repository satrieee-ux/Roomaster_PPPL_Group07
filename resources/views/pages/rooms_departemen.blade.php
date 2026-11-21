{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-02 Melihat Dashboard (Menu Utama) --}}
@extends('layouts.app')

@section('content')
{{-- Tab Departemen: data_ruangan.kategori = 'departemen' (RuanganController@filterByDepartemen).
     Bagian FTEIC / FTIRS diganti karena DDL tidak punya kolom fakultas. --}}
<x-rooms_page :active="2">
  <x-room_section title="Recommended" :rooms="$rekomendasi" />
  <x-room_section title="All Departemen Rooms" layout="stack" :rooms="$ruangan" />
</x-rooms_page>
@endsection
