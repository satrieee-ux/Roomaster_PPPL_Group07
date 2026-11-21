{{--
  Report card (Reports History). Source: laporan_kerusakan + data_ruangan.
  One issue chip only. Draft cards show Continue + Details; others show Details.
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
        'pending'  => ['Pending',     'bg-yellow-50 text-yellow-500 border-yellow-500'],
        'diproses' => ['In Progress', 'bg-orange-50 text-orange-600 border-orange-200'],
        'selesai'  => ['Resolved',    'bg-green-50 text-green-600 border-green-200'],
    ][$status] ?? ['Unknown', 'bg-gray-50 text-gray-600 border-gray-100'];

    $tgl = $tanggal ? \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') : null;
    $detail   = $detailHref   ?? url('/reports/' . $id);                 // ASSUMPTION: route
    $continue = $continueHref ?? url('/report/create?draft=' . $id);     // ASSUMPTION: route
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-100 bg-white p-4 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-base font-bold text-gray-900">{{ $judul }}</h3>
            <p class="text-xs text-gray-600">{{ $kodeRuangan }} · #{{ $kode }}</p>
        </div>
        <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusClass }}">
            {{ $statusLabel }}
        </span>
    </div>

    <div class="mt-2 space-y-1 text-xs text-gray-600">
        @if($tgl)
            <p class="flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>
                {{ $tgl }}
            </p>
        @endif
        @if($lokasi)
            <p class="flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <span class="truncate">{{ $lokasi }}</span>
            </p>
        @endif
    </div>

    {{-- ONE issue chip --}}
    <div class="mt-3">
        <span class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600">
            {{ $kategoriLabel }}
        </span>
    </div>

    <div class="mt-4 flex gap-2">
        @if($status === 'draft')
            <a href="{{ $continue }}" class="flex-1 rounded-lg bg-[#153343] py-2 text-center text-xs font-semibold text-white">Continue</a>
        @endif
        <a href="{{ $detail }}" class="flex-1 rounded-lg bg-gray-100 py-2 text-center text-xs font-semibold text-[#153343]">Details</a>
    </div>
</div>
