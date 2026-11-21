
{{-- Card booking sesuai DDL Roomaster.
     Data disiapkan oleh controller dari v_peminjaman_tampil
     dan relasi data_ruangan.
--}}

@props([
    'id' => null,
    'title' => '',
    'date' => '',
    'time' => '',
    'department' => '',
    'location' => null,
    'image' => null,
    'status' => 'pending',
    'isUpcoming' => false,
    'href' => '#',
    'primaryHref' => '#',
])

@php
    $isUpcoming = filter_var($isUpcoming, FILTER_VALIDATE_BOOLEAN);

    $displayLocation = $location ?? $department;

    $bookingCode = $id !== null
        ? str_pad((string) $id, 6, '0', STR_PAD_LEFT)
        : null;

    $primary = null;

    if ($status === 'disetujui' && $isUpcoming) {
        $primary = 'Check-In';
    } elseif ($status === 'draft') {
        $primary = 'Continue';
    }
@endphp

<div {{ $attributes->class([
    'rounded-xl bg-white px-4 py-3 shadow-card'
]) }}>

    <div class="flex min-h-[100px] gap-3">

        @if($image)
            <img
                src="{{ asset($image) }}"
                alt="{{ $title }}"
                class="size-[72px] shrink-0 rounded-lg bg-grey-200 object-cover"
            >
        @else
            <div class="size-[72px] shrink-0 rounded-lg bg-grey-200"></div>
        @endif

        <div class="min-w-0 flex-1">

            <div class="flex items-center justify-between gap-2.5">

                <h4 class="truncate text-[16px] leading-6 font-semibold text-grey-800">
                    {{ $title }}
                </h4>

                <x-status_pill
                    :status="$status"
                    :is-upcoming="$isUpcoming"
                    class="shrink-0"
                />

            </div>

            @if($bookingCode)
                <p class="mt-1 text-[10px] font-medium text-grey-500">
                    Booking #{{ $bookingCode }}
                </p>
            @endif

            <ul class="mt-1 space-y-1 text-[10px] leading-[14px] font-medium text-grey-800">

                <li class="flex items-start gap-1">
                    <x-icon name="calendar" :size="12" class="mt-px" />
                    <span class="min-w-0 flex-1">{{ $date }}</span>
                </li>

                <li class="flex items-start gap-1">
                    <x-icon name="clock" :size="12" class="mt-px" />
                    <span class="min-w-0 flex-1">{{ $time }}</span>
                </li>

                <li class="flex items-start gap-1">
                    <x-icon name="map-pin" :size="12" class="mt-px" />
                    <span class="min-w-0 flex-1">{{ $displayLocation }}</span>
                </li>

            </ul>

        </div>
    </div>

    <div class="mt-2 flex gap-2.5">

        @if($primary && $primaryHref !== '#')
            <x-button
                :href="$primaryHref"
                size="tiny"
                class="flex-1"
            >
                {{ $primary }}
            </x-button>
        @endif

        <x-button
            :href="$href"
            variant="soft"
            size="tiny"
            class="flex-1"
        >
            Details
        </x-button>

    </div>
</div>
