@extends('layouts.app')

@section('content')
{{-- UC-06 Booking Summary. Data dari PeminjamanController@riwayat:
     $booking  = array (id, code, status, isUpcoming, updated, room, location, capacity, facilities[icon,label],
                 date, time, activity_name, description, documents[nama_berkas, ukuran_berkas, status])
     $riwayat  = baris riwayat_peminjaman (opsional, untuk Recent Activity)
     Diperbaiki oleh: Muhammad Satrio Adjie (5026241065), disamakan dengan gambar referensi. --}}
@php
    $booking = $booking ?? [];
    $status  = data_get($booking, 'status', 'pending');
    $updated = data_get($booking, 'updated');

    $facilities = collect(data_get($booking, 'facilities', []));
    $documents  = collect(data_get($booking, 'documents', []));

    $riwayat  = $riwayat ?? null;
    $activity = collect(data_get($booking, 'activity', []))
        ->map(fn ($item) => [
            'icon'  => data_get($item, 'icon', 'clock'),
            'state' => data_get($item, 'done', false) ? 'done' : 'todo',
            'title' => data_get($item, 'title', ''),
            'desc'  => data_get($item, 'desc', ''),
        ])
        ->all();

    $backUrl = route('bookings');

    // Gaya kartu sama dengan halaman Ringkasan Peminjaman
    $card = 'rounded-xl border border-grey-300 bg-grey-50 px-5 pb-5 pt-4 shadow-card';
    $k    = 'text-[14px] leading-5 font-medium text-grey-500';
@endphp

<div class="flex min-h-full flex-col bg-grey-100">

    <x-header_topappbar title="Booking Summary" :back="$backUrl" />

    <main class="flex flex-1 flex-col gap-4 px-6 pb-5 pt-5">

        <x-status_banner :status="$status" :updated="$updated" :is-upcoming="data_get($booking, 'isUpcoming', false)" />

        {{-- Facility Information --}}
        <section class="{{ $card }}">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-[16px] leading-6 font-medium text-grey-800">Facility Information</h2>
                <span class="text-[10px] leading-[14px] font-medium text-grey-500">Booking #{{ data_get($booking, 'code', '-') }}</span>
            </div>

            <div class="mt-4 flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Room Name</span>
                    <span class="text-[16px] leading-6 font-semibold text-grey-800">{{ data_get($booking, 'room', '-') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Location</span>
                    <span class="text-[14px] leading-5 font-medium text-grey-800">{{ data_get($booking, 'location', '-') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Capacity</span>
                    <span class="flex items-center gap-2 text-[14px] leading-5 font-medium text-grey-800">
                        <x-icon name="users" :size="20" /> {{ data_get($booking, 'capacity', '-') }}
                    </span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Facilities Included</span>
                    @if($facilities->isEmpty())
                        <span class="text-[12px] leading-4 font-medium text-grey-500">Informasi fasilitas belum tersedia.</span>
                    @else
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($facilities as $facility)
                                <span class="flex items-center gap-2 text-[14px] leading-5 font-medium text-grey-800">
                                    <x-icon :name="data_get($facility, 'icon', 'check')" :size="20" /> {{ data_get($facility, 'label', '-') }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Schedule --}}
        <section class="{{ $card }}">
            <h2 class="text-[18px] leading-7 font-medium text-grey-800">Schedule</h2>
            <div class="mt-4 flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Date</span>
                    <span class="text-[16px] leading-6 font-semibold text-grey-800">{{ data_get($booking, 'date', '-') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Time</span>
                    <span class="text-[16px] leading-6 font-semibold text-grey-800">{{ data_get($booking, 'time', '-') }}</span>
                </div>
            </div>
        </section>

        {{-- Recent Activity (dari riwayat_peminjaman) --}}
        <section class="rounded-xl border border-grey-300 bg-grey-50 p-6 shadow-card">
            <h2 class="text-[18px] leading-7 font-medium text-grey-800">Recent Activity</h2>
            <div class="mt-6">
                @if($riwayat !== null)
                    @if(collect($riwayat)->isNotEmpty())
                        <x-activity_timeline :riwayat="$riwayat" />
                    @else
                        <p class="text-[12px] leading-4 font-medium text-grey-500">Belum ada riwayat aktivitas.</p>
                    @endif
                @elseif(count($activity) > 0)
                    <x-activity_timeline :items="$activity" />
                @else
                    <p class="text-[12px] leading-4 font-medium text-grey-500">Belum ada riwayat aktivitas.</p>
                @endif
            </div>
        </section>

        {{-- Activity Details --}}
        <section class="{{ $card }}">
            <h2 class="text-[18px] leading-7 font-medium text-grey-800">Activity Details</h2>
            <div class="mt-4 flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Activity Name</span>
                    <span class="text-[16px] leading-6 font-semibold text-grey-800">{{ data_get($booking, 'activity_name', '-') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="{{ $k }}">Description</span>
                    <p class="text-[14px] leading-5 font-normal text-grey-800">{{ data_get($booking, 'description', '-') }}</p>
                </div>
            </div>
        </section>

        {{-- Supporting Documents (hanya tampilan, tanpa tombol hapus) --}}
        <section class="{{ $card }}">
            <h2 class="text-[18px] leading-7 font-medium text-grey-800">Supporting Documents</h2>
            <div class="mt-4 flex flex-col gap-4">
                @forelse($documents as $doc)
                    <x-card_file :nama_berkas="data_get($doc, 'nama_berkas', '-')"
                                 :ukuran_berkas="data_get($doc, 'ukuran_berkas')"
                                 :status="data_get($doc, 'status', 'draft')" :removable="false" />
                @empty
                    <p class="text-[12px] leading-4 font-medium text-grey-500">Tidak ada dokumen pendukung.</p>
                @endforelse
            </div>
        </section>

    </main>

    {{-- Bar bawah sticky: Done, dan Review Room hanya untuk booking yang sudah selesai (keputusan DDL no. 9) --}}
    <div class="sticky bottom-0 z-30 bg-grey-100 px-6 pb-5">
        <div class="flex flex-col gap-2 border-t border-grey-300 pt-4">
            <x-button :href="$backUrl" variant="primary" size="large" class="w-full">Done</x-button>
            @if($status === 'selesai')
                <x-button type="button" variant="secondary" size="large" class="w-full" data-popup-open="popup-buatreview">Review Room</x-button>
            @endif
            <p class="mt-1 text-center text-[12px] leading-4 font-medium text-grey-500">
                All Bookings are managed according to the facility usage terms and conditions.
            </p>
        </div>
    </div>

    @if($status === 'selesai' && data_get($booking, 'id'))
        <x-popup_buatreview :submitUrl="route('ulasan.store', data_get($booking, 'id'))" />
    @endif

</div>
@endsection
