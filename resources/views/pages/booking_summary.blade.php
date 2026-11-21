
@extends('layouts.app')

@section('content')
@php
    // Data $booking nantinya disiapkan oleh controller UC-07.
    $booking = $booking ?? [];

    $status = data_get($booking, 'status', 'pending');
    $updated = data_get($booking, 'updated');

    $facilities = collect(data_get($booking, 'facilities', []));
    $documents = collect(data_get($booking, 'documents', []));

    $riwayat = $riwayat ?? null;
    $activity = collect(data_get($booking, 'activity', []))
        ->map(function ($item) {
            return [
                'icon' => data_get($item, 'icon', 'clock'),
                'state' => data_get($item, 'done', false)
                    ? 'done'
                    : 'todo',
                'title' => data_get($item, 'title', ''),
                'desc' => data_get($item, 'desc', ''),
            ];
        })
        ->all();

    // Sementara mengikuti URL dari desain lama.
    // Perlu diganti ketika route UC-06 tersedia.
    $backUrl = url('/bookings');
@endphp

<div class="flex min-h-full flex-col bg-grey-100">

    <x-header_topappbar
        title="Booking Summary"
        :back="$backUrl"
    />

    <main class="flex flex-1 flex-col gap-4 px-6 pb-5 pt-5">

        <x-status_banner
            :status="$status"
            :updated="$updated"
            :is-upcoming="data_get($booking, 'isUpcoming', false)"
        />

        {{-- Facility Information --}}
        <section class="rounded-xl border border-grey-200 bg-grey-50 p-5">

            <div class="flex items-center justify-between gap-3">
                <h2 class="text-[16px] leading-6 font-semibold text-grey-800">
                    Facility Information
                </h2>

                <span class="text-[10px] font-medium text-grey-500">
                    Booking #{{ data_get($booking, 'code', '-') }}
                </span>
            </div>

            <div class="mt-4 flex flex-col gap-5">

                <div class="flex flex-col gap-1">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Room Name
                    </span>

                    <span class="text-[18px] font-semibold text-grey-800">
                        {{ data_get($booking, 'room', '-') }}
                    </span>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Location
                    </span>

                    <span class="text-[14px] text-grey-800">
                        {{ data_get($booking, 'location', '-') }}
                    </span>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Capacity
                    </span>

                    <span class="flex items-center gap-2 text-[14px] text-grey-800">
                        <x-icon name="users" :size="20" />

                        {{ data_get($booking, 'capacity', '-') }}
                    </span>
                </div>

                <div class="flex flex-col gap-2">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Facilities Included
                    </span>

                    @forelse($facilities as $facility)
                        <div class="flex items-center gap-2 text-[14px] text-grey-800">
                            <x-icon
                                :name="data_get($facility, 'icon', 'check')"
                                :size="20"
                            />

                            {{ data_get($facility, 'label', '-') }}
                        </div>
                    @empty
                        <span class="text-[12px] text-grey-500">
                            Informasi fasilitas belum tersedia.
                        </span>
                    @endforelse
                </div>

            </div>
        </section>

        {{-- Schedule --}}
        <section class="rounded-xl border border-grey-200 bg-grey-50 p-5">

            <h2 class="text-[16px] font-semibold text-grey-800">
                Schedule
            </h2>

            <div class="mt-4 flex flex-col gap-5">

                <div class="flex flex-col gap-1">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Date
                    </span>

                    <span class="text-[16px] text-grey-800">
                        {{ data_get($booking, 'date', '-') }}
                    </span>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Time
                    </span>

                    <span class="text-[16px] text-grey-800">
                        {{ data_get($booking, 'time', '-') }}
                    </span>
                </div>

            </div>
        </section>

        {{-- Recent Activity --}}
        <section class="rounded-xl border border-grey-200 bg-grey-50 p-5">

            <h2 class="text-[18px] font-semibold text-grey-800">
                Recent Activity
            </h2>

            <div class="mt-4">

                @if($riwayat !== null)
                    @if(collect($riwayat)->isNotEmpty())
                        <x-activity_timeline :riwayat="$riwayat" />
                    @else
                        <p class="text-[12px] text-grey-500">
                            Belum ada riwayat aktivitas.
                        </p>
                    @endif
                @elseif(count($activity) > 0)
                    <x-activity_timeline :items="$activity" />
                @else
                    <p class="text-[12px] text-grey-500">
                        Belum ada riwayat aktivitas.
                    </p>
                @endif

            </div>
        </section>

        {{-- Activity Details --}}
        <section class="rounded-xl border border-grey-200 bg-grey-50 p-5">

            <h2 class="text-[16px] font-semibold text-grey-800">
                Activity Details
            </h2>

            <div class="mt-4 flex flex-col gap-5">

                <div class="flex flex-col gap-1">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Activity Name
                    </span>

                    <span class="text-[16px] font-semibold text-grey-800">
                        {{ data_get($booking, 'activity_name', '-') }}
                    </span>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="text-[14px] font-semibold text-grey-500">
                        Description
                    </span>

                    <p class="text-[14px] leading-5 text-grey-800">
                        {{ data_get($booking, 'description', '-') }}
                    </p>
                </div>

            </div>
        </section>

        {{-- Supporting Documents --}}
        <section class="rounded-xl border border-grey-200 bg-grey-50 p-5">

            <h2 class="text-[16px] font-semibold text-grey-800">
                Supporting Documents
            </h2>

            <div class="mt-4 flex flex-col gap-3">

                @forelse($documents as $document)
                    <div class="flex items-center gap-3 rounded-xl border border-grey-300 bg-grey-50 p-3">

                        <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-grey-100 text-grey-500">
                            <x-icon name="file-text" :size="24" />
                        </span>

                        <div class="min-w-0 flex flex-col">
                            <span class="truncate text-[14px] font-medium text-grey-800">
                                {{ data_get($document, 'name', '-') }}
                            </span>

                            <span class="text-[12px] text-grey-500">
                                {{ data_get($document, 'size', '') }}
                            </span>
                        </div>

                    </div>
                @empty
                    <p class="text-[12px] text-grey-500">
                        Tidak ada dokumen pendukung.
                    </p>
                @endforelse

            </div>
        </section>

    </main>

    {{-- Bottom Action --}}
    <div class="sticky bottom-0 z-30 flex flex-col gap-3 border-t border-grey-200 bg-grey-50 px-6 pb-5 pt-4">

        <x-button
            :href="$backUrl"
            variant="primary"
            size="large"
            class="w-full"
        >
            Done
        </x-button>

        {{-- Review Room menunggu route UC-14 yang sesuai. --}}

        <p class="text-center text-[12px] leading-4 text-grey-500">
            All Bookings are managed according to the facility usage terms and conditions.
        </p>

    </div>

</div>
@endsection
