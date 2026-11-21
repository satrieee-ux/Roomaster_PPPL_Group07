{{-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) --}}
{{-- Kepala halaman ruangan, dipakai bersama oleh room_detail (tab About) dan room_reviews (tab Reviews):
     tombol back/share, foto, label + rating, judul, lokasi, pill ketersediaan, dan tab.
     Tanpa pembungkus: tombol melayang harus anak langsung kolom halaman supaya sticky.
     Pakai: <x-room_header :ruangan="$ruangan" :rataRating="$rataRating" :jumlahUlasan="$jumlahUlasan"
                            :tersedia="$tersedia" :active="0" />
     Judul = kode_ruangan (keputusan B2, sesuai gambar). Label kecil di atasnya = nama_ruangan + kategori. --}}
@props(['ruangan', 'rataRating' => 0, 'jumlahUlasan' => 0, 'tersedia' => false, 'active' => 0, 'back' => null])
@php
  $tabs = [
    ['label' => 'About',   'href' => route('rooms.detail', ['id' => $ruangan->id])],
    ['label' => 'Reviews', 'href' => route('rooms.reviews', ['id' => $ruangan->id])],
  ];
  $foto    = $ruangan->foto_ruangan ? asset($ruangan->foto_ruangan) : asset('images/rooms/building.jpg');
  $petaUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode($ruangan->lokasi.' ITS Surabaya');
  $back  ??= url()->previous();
@endphp

{{-- Tombol back & share (sticky, melayang di atas foto) --}}
<div class="sticky top-0 z-20 h-0">
  <div class="flex items-center justify-between px-6 pt-14">
    <a href="{{ $back }}" aria-label="Kembali"
       class="flex size-8 items-center justify-center rounded-full bg-white text-grey-800 shadow-card">
      <x-icon name="arrow-left" :size="20" />
    </a>
    {{-- Bagikan: pakai fitur share bawaan HP; kalau tidak ada, salin link --}}
    <button type="button" aria-label="Bagikan"
       onclick="navigator.share ? navigator.share({ title: document.title, url: location.href }) : navigator.clipboard.writeText(location.href)"
       class="flex size-8 items-center justify-center rounded-full bg-white text-grey-800 shadow-card">
      <x-icon name="share-2" :size="20" />
    </button>
  </div>
</div>

{{-- Foto ruangan (DDL hanya punya satu foto, galeri dan "+10" dihapus) --}}
<div class="relative h-[359px] shrink-0">
  <img src="{{ $foto }}" alt="{{ $ruangan->kode_ruangan }}" class="size-full rounded-b-[32px] object-cover">
</div>

<div class="flex flex-col px-6 pt-6">
  <div class="flex items-center justify-between gap-3">
    <span class="min-w-0 truncate text-[10px] leading-[14px] font-medium text-primary-500">
      {{ $ruangan->nama_ruangan }} · {{ $ruangan->kategori === 'skpb' ? 'SKPB' : 'Departemen' }}
    </span>
    <span class="flex shrink-0 items-center gap-2 text-[12px] leading-4 font-medium text-grey-800">
      <x-icon name="star" :size="20" class="fill-current text-secondary-300" />
      {{ number_format($rataRating, 1) }} ({{ $jumlahUlasan }} Reviews)
    </span>
  </div>

  <h1 class="mt-4 text-[24px] leading-7 font-extrabold text-grey-800">{{ $ruangan->kode_ruangan }}</h1>

  <div class="mt-1 flex items-center justify-between gap-3">
    <div class="flex items-start gap-1 text-[12px] leading-[18px] font-normal text-grey-800">
      <x-icon name="map-pin" :size="12" class="mt-[3px] shrink-0" />
      <span>{{ $ruangan->lokasi }}</span>
    </div>
    {{-- Petunjuk arah: buka Google Maps dengan kata kunci lokasi --}}
    <a href="{{ $petaUrl }}" target="_blank" rel="noopener" aria-label="Petunjuk arah"
       class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-900 text-white">
      <x-icon name="navigation" :size="20" />
    </a>
  </div>

  {{-- Pill ketersediaan dari view v_ketersediaan_ruangan, memakai status_pill (sesuai Design System) --}}
  <div class="mt-2">
    @if($tersedia)
      <x-status_pill status="disetujui">Now Available</x-status_pill>
    @else
      <x-status_pill status="ditolak">In Use Now</x-status_pill>
    @endif
  </div>
</div>

{{-- Garis tab selebar layar; tab di dalamnya tetap berpadding 24 --}}
<div class="mt-5 border-b border-grey-300">
  <x-tabs :items="$tabs" :active="$active" class="border-b-0! px-6" />
</div>
