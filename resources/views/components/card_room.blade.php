{{-- Kartu ruangan, isi dari satu baris data_ruangan.
     Contoh: <x-card_room :id="$r->id" :kode_ruangan="$r->kode_ruangan" :lokasi="$r->lokasi"
                          :kapasitas="$r->kapasitas" :foto_ruangan="$r->foto_ruangan" :favorit="$r->is_favorit" />
     layout       = vertical (kecil 200px) | vertical_large (lebar penuh) | horizontal (list kecil)
     kode_ruangan = judul kartu (keputusan tim B2)
     lokasi       = baris pin (kolom nama departemen tidak ada di DDL, keputusan B1)
     kapasitas    = angka, tampil "40 Orang"
     foto_ruangan = path relatif di public, contoh images/rooms/si-4202.jpg
     favorit      = true bila ruangan ada di ruangan_favorit milik user login
     Tombol hati mengirim POST /rooms/{id}/favorite (toggleFavorite) (simpan / hapus favorit), lalu kembali ke halaman ini. --}}
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
  <div {{ $attributes->class(['flex items-center gap-4 rounded-3xl bg-white p-3 shadow-card']) }}>
    <a href="{{ $link }}" class="flex min-w-0 flex-1 items-center gap-4">
      <img src="{{ $src }}" alt="{{ $kode_ruangan }}" class="size-[100px] shrink-0 rounded-2xl object-cover">
      <div class="min-w-0 flex-1">
        <h4 class="truncate text-[18px] leading-7 font-semibold text-grey-800">{{ $kode_ruangan }}</h4>
        <p class="mt-1 flex items-center gap-1.5 text-[12px] leading-4 font-medium text-grey-800"><x-icon name="map-pin" :size="14" /><span class="truncate">{{ $lokasi }}</span></p>
        <p class="mt-0.5 flex items-center gap-1.5 text-[12px] leading-4 font-medium text-grey-800"><x-icon name="users" :size="14" />{{ $cap }}</p>
      </div>
    </a>
    @if($id)
      <form method="POST" action="{{ url('/rooms/'.$id.'/favorite') }}" class="shrink-0">
        @csrf
        <button type="submit" aria-label="Favorit" class="flex size-8 items-center justify-center rounded-full bg-primary-50/40">
          <x-icon name="heart" :size="18" class="{{ $heart }}" />
        </button>
      </form>
    @endif
  </div>
@else
  @php $large = $layout === 'vertical_large'; @endphp
  <div {{ $attributes->class(['flex flex-col overflow-hidden rounded-3xl bg-white shadow-card', $large ? 'w-full' : 'w-[200px]']) }}>
    {{-- Gambar: selebar kartu, menempel ke atas --}}
    <a href="{{ $link }}" class="block shrink-0">
      <img src="{{ $src }}" alt="{{ $kode_ruangan }}" class="block w-full object-cover {{ $large ? 'h-[208px]' : 'h-[120px]' }}">
    </a>

    <div class="flex flex-1 flex-col p-3">
      <div class="flex items-center justify-between gap-2">
        <h4 @class([
          'min-w-0 truncate font-semibold text-grey-800',
          'text-[18px] leading-7' => $large,
          'text-[16px] leading-6' => ! $large,
        ])>{{ $kode_ruangan }}</h4>
        @if($id)
          <form method="POST" action="{{ url('/rooms/'.$id.'/favorite') }}" class="shrink-0">
            @csrf
            <button type="submit" aria-label="Favorit" @class([
              'flex items-center justify-center rounded-full bg-primary-50/40',
              'size-7' => $large,
              'size-6' => ! $large,
            ])>
              <x-icon name="heart" :size="$large ? 16 : 14" class="{{ $heart }}" />
            </button>
          </form>
        @endif
      </div>

      <p @class([
        'flex items-start gap-1.5 text-[12px] leading-4 font-medium text-grey-800',
        'mt-2' => $large,
        'mt-1' => ! $large,
      ])><x-icon name="map-pin" :size="14" class="shrink-0" />{{ $lokasi }}</p>
      <p class="flex items-center gap-1.5 text-[12px] leading-4 font-medium text-grey-800"><x-icon name="users" :size="14" class="shrink-0" />{{ $cap }}</p>

      {{-- mt-auto: tombol selalu menempel di dasar kartu --}}
      <div class="mt-auto pt-3">
        <x-button :href="$link" size="tiny" class="w-full">Book Now</x-button>
      </div>
    </div>
  </div>
@endif
