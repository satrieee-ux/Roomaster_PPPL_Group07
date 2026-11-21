{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-02 Melihat Dashboard (Menu Utama) --}}
@extends('layouts.app')

@section('content')
{{-- Tab All. $sering = paling sering dipinjam, $ruangan = semua data_ruangan (RuanganController@index) --}}
<x-rooms_page :active="0">
  <x-room_section title="Frequently Booked" :rooms="$sering" empty="No bookings yet." />
  <x-room_section title="All Rooms" layout="stack" :rooms="$ruangan" />
</x-rooms_page>
@endsection
