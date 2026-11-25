{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-02 Melihat Dashboard (Menu Utama) --}}
@extends('layouts.app')

@section('content')
{{-- Tab Favorites: ruangan_favorit milik user login (RuanganController@filterByFavorites).
     Tekan hati pada kartu untuk menghapus dari favorit. --}}
<x-rooms_page :active="3">
  <x-room_section title="Your Favorites" layout="stack" :rooms="$favorit"
                  empty="No favorite rooms yet. Tap the heart on a room to save it." />
</x-rooms_page>
@endsection
