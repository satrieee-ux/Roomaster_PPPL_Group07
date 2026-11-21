{{-- Diperbaiki oleh: Muhammad Satrio Adjie (5026241065), disesuaikan dengan gambar "Melihat history laporan" --}}
{{--
  Kartu laporan (Reports History). Sumber: laporan_kerusakan + data_ruangan.
  Satu baris masalah (ikon + kategori). Draft: Continue + Details. Status lain: Details.
  Judul kartu = kode ruangan (sesuai gambar). Prop judul tetap diterima tetapi tidak ditampilkan.
--}}
@props([
    'id',
    'judul' => null,
    'kodeRuangan',
    'lokasi'  => null,
    'tanggal' => null,    // laporan_kerusakan.tanggal_lapor
    'kategori',           // ENUM: ac|proyektor_av|listrik|furnitur|jaringan|kebersihan|lainnya
    'status',             // ENUM: draft|pending|diproses|selesai
    'detailHref'   => null,
    'continueHref' => null,
])

@php
    // [label, ikon] per kategori. Nama ikon harus ada di components/icon.blade.php.
    [$kategoriLabel, $kategoriIkon] = [
        'ac'           => ['Air Conditioning', 'air-vent'],
        'proyektor_av' => ['Projector',        'projector'],
        'listrik'      => ['Lighting',         'lightbulb'],
        'furnitur'     => ['Desk/Seating',     'armchair'],
        'jaringan'     => ['Network',          'wifi'],
        'kebersihan'   => ['Cleanliness',      'alert-circle'],
        'lainnya'      => ['Others',           'alert-circle'],
    ][$kategori] ?? ['Others', 'alert-circle'];

    // Bulan berbahasa Indonesia seperti di gambar: "24 Okt 2023"
    $tgl = $tanggal ? \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d M Y') : null;

    $detail   = $detailHref   ?? route('report.summary', $id);
    $continue = $continueHref ?? route('report.create', ['draft' => $id]);
@endphp

<div {{ $attributes->class(['rounded-2xl bg-grey-50 px-5 py-4 shadow-card']) }}>
    <div class="flex items-center justify-between gap-3">
        <h3 class="min-w-0 truncate text-[18px] leading-7 font-medium text-grey-800">{{ $kodeRuangan }}</h3>
        @if($status === 'selesai')
            <x-status_pill status="selesai">Resolved</x-status_pill>
        @else
            <x-status_pill :status="$status" />
        @endif
    </div>

    <div class="mt-1 flex flex-col gap-1 text-[10px] leading-[14px] font-medium text-grey-800">
        @if($tgl)
            <p class="flex items-center gap-1"><x-icon name="calendar" :size="12" /> {{ $tgl }}</p>
        @endif
        @if($lokasi)
            <p class="flex items-center gap-1"><x-icon name="map-pin" :size="12" /> <span class="truncate">{{ $lokasi }}</span></p>
        @endif
    </div>

    {{-- Satu masalah: ikon kategori + nama kategori --}}
    <div class="mt-3 flex items-center gap-2.5 text-[16px] leading-6 font-normal text-grey-800">
        <x-icon :name="$kategoriIkon" :size="20" class="shrink-0" /> {{ $kategoriLabel }}
    </div>

    <div class="mt-4 flex gap-4">
        @if($status === 'draft')
            <x-button :href="$continue" variant="primary" size="tiny" class="flex-1">Continue</x-button>
        @endif
        <x-button :href="$detail" variant="soft" size="tiny" class="flex-1">Details</x-button>
    </div>
</div>
