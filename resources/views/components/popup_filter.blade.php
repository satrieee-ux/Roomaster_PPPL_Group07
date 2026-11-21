{{-- Modal Filter (bottom sheet): kapasitas, fasilitas, tipe ruangan. Dikirim GET ke halaman hasil.
     Contoh: <x-popup_filter :action="url('/rooms/search/filter')" :fasilitas="$fasilitas" />
     fasilitas = koleksi model Fasilitas (tabel fasilitas), tidak di-hardcode lagi.
     Pilihan yang sedang aktif (dari URL) otomatis tercentang. Kata kunci dan tanggal ikut dikirim ulang. --}}
@props(['id' => 'popup-filter', 'open' => false, 'action' => '#', 'fasilitas' => []])
@php
$kapasitasAktif = (array) request('capacity', []);
$fasilitasAktif = (array) request('facilities', []);
$tipeAktif      = (array) request('type', []);
@endphp
<x-popup :id="$id" :open="$open" :sheet="true">
  <form action="{{ $action }}" method="GET">
    {{-- Bawa kata kunci dan tanggal yang sudah dipilih --}}
    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    @if(request('date'))<input type="hidden" name="date" value="{{ request('date') }}">@endif

    <div class="flex items-center justify-between text-primary-500">
      <x-icon name="list-filter" :size="24" />
      <span class="text-[16px] leading-6 font-semibold text-grey-800">Filter</span>
      <button type="button" data-popup-close aria-label="Tutup"><x-icon name="x" :size="24" /></button>
    </div>

    {{-- Kapasitas -> data_ruangan.kapasitas (rentang) --}}
    <section class="mt-3.5">
      <h3 class="text-[16px] leading-6 font-semibold text-grey-800">Select Capacity</h3>
      @foreach(['0-19', '20-49', '50-99', '100+'] as $c)
        <x-checkbox name="capacity[]" :value="$c" :label="$c" :checked="in_array($c, $kapasitasAktif)" class="h-10" />
      @endforeach
    </section>

    {{-- Fasilitas -> tabel fasilitas, nilai = fasilitas.id --}}
    <section class="mt-4 border-t border-grey-200 pt-6">
      <h3 class="text-[16px] leading-6 font-semibold text-grey-800">Facilities</h3>
      <div class="grid grid-cols-2">
        @foreach($fasilitas as $f)
          <x-checkbox name="facilities[]" :value="$f->id" :label="$f->nama_fasilitas"
                      :checked="in_array($f->id, $fasilitasAktif)" class="h-10" />
        @endforeach
      </div>
    </section>

    {{-- Tipe ruangan -> data_ruangan.kategori --}}
    <section class="mt-4 border-t border-grey-200 pt-6">
      <h3 class="text-[16px] leading-6 font-semibold text-grey-800">Room Type</h3>
      <div class="mt-3 flex gap-3">
        @foreach(['skpb' => 'SKPB', 'departemen' => 'Departemen'] as $v => $l)
          <label class="flex-1 cursor-pointer">
            <input type="checkbox" name="type[]" value="{{ $v }}" @checked(in_array($v, $tipeAktif)) class="peer sr-only">
            <span class="flex h-8 items-center justify-center rounded-full bg-grey-200 text-[12px] leading-4 font-medium text-primary-500 peer-checked:bg-primary-800 peer-checked:text-white">{{ $l }}</span>
          </label>
        @endforeach
      </div>
    </section>

    <div class="mt-4 border-t border-grey-200 pt-4">
      <x-button type="submit" variant="primary" size="large" class="w-full">Apply</x-button>
    </div>
  </form>
</x-popup>
