{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-02 Melihat Dashboard (Menu Utama); juga dipakai di UC-03 --}}
{{-- Diperbaiki oleh: Muhammad Satrio Adjie (5026241065), disamakan dengan gambar referensi dan perbaikan bug --}}
{{-- Kartu ruangan, isi dari satu baris data_ruangan.
     Contoh: <x-card_room :id="$r->id" :kode_ruangan="$r->kode_ruangan" :lokasi="$r->lokasi"
                          :kapasitas="$r->kapasitas" :foto_ruangan="$r->foto_ruangan" :favorit="$r->is_favorit" />
     layout       = vertical (kecil 200px) | vertical_large (lebar penuh) | horizontal (list kecil)
     kode_ruangan = judul kartu (keputusan tim B2)
     lokasi       = baris pin (kolom nama departemen tidak ada di DDL, keputusan B1)
     kapasitas    = angka, tampil "40 Orang"
     foto_ruangan = path relatif di public, contoh images/rooms/si-4202.jpg
     favorit      = true bila ruangan ada di ruangan_favorit milik user login
     Tombol hati mengirim POST /rooms/{id}/favorite (toggleFavorite) (simpan / hapus favorit), lalu kembali ke halaman ini.
     Ukuran diukur dari gambar referensi: radius 12, gambar besar 202, gambar kecil 120, hati 24. --}}
@props([
  'layout' => 'vertical',
  'id' => null,
  'kode_ruangan' => '',
  'lokasi' => '',
  'kapasitas' => 0,
  'foto_ruangan' => null,
  'favorit' => false,
  'href' => null,
])
@php
$link  = $href ?? ($id ? url('/rooms/'.$id) : '#');                 // tujuan klik kartu
$heart = $favorit ? 'fill-current text-danger-500' : 'text-primary-500'; // hati merah bila favorit
$cap   = $kapasitas.' Orang';
$src   = $foto_ruangan ? asset($foto_ruangan) : asset('images/rooms/building.jpg'); // gambar cadangan
@endphp

@if($layout === 'horizontal')
  {{-- Gambar menempel penuh ke tepi kiri kartu (100x102), teks di tengah, hati di kanan --}}
  <div {{ $attributes->class(['flex items-center gap-4 overflow-hidden rounded-xl bg-white pr-4 shadow-card']) }}>
    <a href="{{ $link }}" class="flex min-w-0 flex-1 items-center gap-4">
      <img src="{{ $src }}" alt="{{ $kode_ruangan }}" class="h-[102px] w-[100px] shrink-0 object-cover">
      <div class="min-w-0 flex-1">
        <h4 class="truncate text-[18px] leading-7 font-semibold text-grey-800">{{ $kode_ruangan }}</h4>
        <p class="mt-0.5 flex items-center gap-1.5 text-[12px] leading-4 font-medium text-grey-800"><x-icon name="map-pin" :size="14" class="shrink-0" /><span class="truncate">{{ $lokasi }}</span></p>
        <p class="mt-0.5 flex items-center gap-1.5 text-[12px] leading-4 font-medium text-grey-800"><x-icon name="users" :size="14" class="shrink-0" />{{ $cap }}</p>
      </div>
    </a>
    @if($id)
      {{-- Tombol hati: POST /rooms/{id}/favorite (RuanganController@toggleFavorite) menyimpan atau menghapus favorit. --}}
      <form method="POST" action="{{ url('/rooms/'.$id.'/favorite') }}" class="shrink-0">
        @csrf
        <button type="submit" aria-label="Favorit" class="flex size-6 items-center justify-center rounded-full bg-primary-50/40">
          <x-icon name="heart" :size="14" class="{{ $heart }}" />
        </button>
      </form>
    @endif
  </div>
@else
  @php $large = $layout === 'vertical_large'; @endphp
  <div {{ $attributes->class(['flex flex-col overflow-hidden rounded-xl bg-white shadow-card', $large ? 'w-full' : 'w-[200px]']) }}>
    {{-- Gambar: selebar kartu, menempel ke atas --}}
    <a href="{{ $link }}" class="block shrink-0">
      <img src="{{ $src }}" alt="{{ $kode_ruangan }}" class="block w-full object-cover {{ $large ? 'h-[202px]' : 'h-[120px]' }}">
    </a>

    <div class="flex flex-1 flex-col p-3">
      <div class="flex items-center justify-between gap-2">
        <h4 @class([
          'min-w-0 truncate font-semibold text-grey-800',
          'text-[18px] leading-7' => $large,
          'text-[16px] leading-6' => ! $large,
        ])>{{ $kode_ruangan }}</h4>
        @if($id)
          {{-- Tombol hati: POST /rooms/{id}/favorite (RuanganController@toggleFavorite) menyimpan atau menghapus favorit. --}}
          <form method="POST" action="{{ url('/rooms/'.$id.'/favorite') }}" class="shrink-0">
            @csrf
            <button type="submit" aria-label="Favorit" class="flex size-6 items-center justify-center rounded-full bg-primary-50/40">
              <x-icon name="heart" :size="14" class="{{ $heart }}" />
            </button>
          </form>
        @endif
      </div>

      {{-- Lokasi menempel ke judul, kapasitas jarak 2px (sesuai gambar) --}}
      <p class="flex items-start gap-1.5 text-[12px] leading-4 font-medium text-grey-800"><x-icon name="map-pin" :size="14" class="shrink-0" />{{ $lokasi }}</p>
      <p class="mt-0.5 flex items-center gap-1.5 text-[12px] leading-4 font-medium text-grey-800"><x-icon name="users" :size="14" class="shrink-0" />{{ $cap }}</p>

      {{-- mt-auto: tombol selalu menempel di dasar kartu --}}
      <div class="mt-auto pt-3">
        <x-button :href="$link" size="tiny" class="w-full">Book Now</x-button>
      </div>
    </div>
  </div>
@endif
