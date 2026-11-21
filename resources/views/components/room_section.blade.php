{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-02 Melihat Dashboard (Menu Utama); juga dipakai di UC-03 --}}
{{-- Satu bagian berjudul berisi kartu ruangan.
     Contoh: <x-room_section title="Recommended" :rooms="$rekomendasi" layout="stack" />
     rooms     = koleksi model Ruangan (data_ruangan). Tiap ruangan sebaiknya sudah diberi
                 is_favorit lewat Ruangan::tandaiFavorit($rooms) di controller.
     layout    = row (geser ke samping) | stack (kartu besar bertumpuk) | list (kartu kecil mendatar)
     seeAllUrl = tujuan tombol "See All". Kosong = tombol tidak ditampilkan.
     empty     = teks bila tidak ada ruangan. --}}
@props([
  'title',
  'rooms' => [],
  'layout' => 'row',
  'seeAllUrl' => null,
  'empty' => 'No rooms found.',
])
@php
// Pilih bentuk kartu sesuai layout bagian
$bentukKartu = ['stack' => 'vertical_large', 'list' => 'horizontal', 'row' => 'vertical'][$layout] ?? 'vertical';
@endphp

<section class="flex flex-col gap-3">
  <div class="flex h-7 items-center justify-between px-6">
    <h2 class="text-[18px] leading-7 font-semibold text-primary-800">{{ $title }}</h2>
    @if($seeAllUrl)
      <x-button :href="$seeAllUrl" variant="soft" size="tiny" iconRight="chevron-right">See All</x-button>
    @endif
  </div>

  @if(count($rooms) === 0)
    <p class="px-6 text-[14px] leading-5 text-grey-500">{{ $empty }}</p>
  @else
    <div @class([
      'flex flex-col gap-3 px-6' => $layout !== 'row',
      'no-scrollbar -mb-4 flex items-stretch gap-3 overflow-x-auto px-6 pb-4' => $layout === 'row',
    ])>
      @foreach($rooms as $r)
        <x-card_room :layout="$bentukKartu" :class="$layout === 'row' ? 'shrink-0' : ''"
          :id="$r->id" :kode_ruangan="$r->kode_ruangan" :lokasi="$r->lokasi"
          :kapasitas="$r->kapasitas" :foto_ruangan="$r->foto_ruangan" :favorit="$r->is_favorit ?? false" />
      @endforeach
    </div>
  @endif
</section>
