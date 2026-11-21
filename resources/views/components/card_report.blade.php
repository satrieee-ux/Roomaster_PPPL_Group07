{{--
  Report card (Reports History). Source: laporan_kerusakan + data_ruangan.
  ONE issue chip only. Draft: Continue + Details. Other statuses: Details.
--}}
@props([
    'id',
    'judul',
    'kodeRuangan',
    'lokasi'  => null,
    'tanggal' => null,    // laporan_kerusakan.tanggal_lapor
    'kategori',           // ENUM: ac|proyektor_av|listrik|furnitur|jaringan|kebersihan|lainnya
    'status',             // ENUM: draft|pending|diproses|selesai
    'detailHref'   => null,
    'continueHref' => null,
])

@php
    $kode = str_pad($id, 6, '0', STR_PAD_LEFT);

    $kategoriLabel = [
        'ac' => 'Air Conditioning', 'proyektor_av' => 'Projector',
        'listrik' => 'Lighting', 'furnitur' => 'Desk/Seating',
        'jaringan' => 'Network', 'kebersihan' => 'Cleanliness', 'lainnya' => 'Others',
    ][$kategori] ?? 'Others';

    // SWAP: replace with <x-status_pill> once Thalia's version exists.
    [$statusLabel, $statusClass] = [
        'draft'    => ['Draft',       'bg-primary-50 text-primary-600 border-primary-100'],
        'pending'  => ['Pending',     'bg-warning-50 text-warning-500 border-warning-500'],
        'diproses' => ['In Progress', 'bg-secondary-50 text-secondary-600 border-secondary-100'],
        'selesai'  => ['Resolved',    'bg-success-50 text-success-800 border-success-300'],
    ][$status] ?? ['Unknown', 'bg-grey-50 text-grey-600 border-grey-100'];

    $tgl = $tanggal ? \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') : null;
    $detail   = $detailHref   ?? url('/qreporting/summary/' . $id);
    $continue = $continueHref ?? url('/qreporting/report?draft=' . $id);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-grey-100 bg-white p-4 shadow-card']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-[16px] leading-6 font-semibold text-grey-900">{{ $judul }}</h3>
            <p class="text-[12px] leading-4 font-normal text-grey-600">{{ $kodeRuangan }} · #{{ $kode }}</p>
        </div>
        <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] leading-4 font-semibold {{ $statusClass }}">
            {{ $statusLabel }}
        </span>
    </div>

    <div class="mt-2 flex flex-col gap-1 text-[12px] leading-4 font-normal text-grey-600">
        @if($tgl)
            <p class="flex items-center gap-1.5"><x-icon name="calendar" :size="16" /> {{ $tgl }}</p>
        @endif
        @if($lokasi)
            <p class="flex items-center gap-1.5"><x-icon name="map-pin" :size="16" /> <span class="truncate">{{ $lokasi }}</span></p>
        @endif
    </div>

    {{-- ONE issue chip --}}
    <div class="mt-3">
        <span class="inline-flex items-center rounded-full bg-grey-50 px-3 py-1 text-[12px] leading-4 font-medium text-grey-600">
            {{ $kategoriLabel }}
        </span>
    </div>

    <div class="mt-4 flex gap-2">
        @if($status === 'draft')
            <x-button :href="$continue" variant="primary" size="small" class="flex-1">Continue</x-button>
        @endif
        <x-button :href="$detail" variant="soft" size="small" class="flex-1">Details</x-button>
    </div>
</div>
