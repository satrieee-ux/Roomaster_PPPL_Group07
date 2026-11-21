@extends('layouts.app')

@section('content')
{{-- Tab SKPB: data_ruangan.kategori = 'skpb' (RuanganController@filterBySkpb).
     Bagian TW-2 / UPBG diganti karena DDL tidak punya kolom gedung. --}}
<x-rooms_page :active="1">
  <x-room_section title="Recommended" :rooms="$rekomendasi" />
  <x-room_section title="All SKPB Rooms" layout="stack" :rooms="$ruangan" />
</x-rooms_page>
@endsection
