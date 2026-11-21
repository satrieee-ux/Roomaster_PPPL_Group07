
@extends('layouts.app')

@section('content')
@php
    $activeTab = $activeTab ?? 'all';

    $tabNames = [
        'all' => 0,
        'active' => 1,
        'pending' => 2,
        'history' => 3,
    ];

    $tabs = [
        ['label' => 'All', 'href' => url('/bookings')],
        ['label' => 'Active', 'href' => url('/bookings/active')],
        ['label' => 'Pending', 'href' => url('/bookings/pending')],
        ['label' => 'History', 'href' => url('/bookings/history')],
    ];

    $activeIndex = $tabNames[$activeTab] ?? 0;
    $bookings = collect($bookings ?? []);
@endphp

<div class="flex min-h-full flex-col bg-grey-100">

    <div class="sticky top-0 z-20 bg-grey-100">
        <x-header_compact />

        <div class="flex items-center justify-between px-6 pt-5">
            <h1 class="text-[24px] leading-7 font-extrabold text-primary-800">
                Your Bookings
            </h1>
        </div>

        <x-tabs
            :items="$tabs"
            :active="$activeIndex"
            class="mt-3 px-6"
        />
    </div>

    <main class="flex flex-1 flex-col pb-5">
        <div class="mt-4 flex flex-col gap-4 px-6">

            @forelse($bookings as $booking)
                <x-card_booking
                    :id="data_get($booking, 'id')"
                    :title="data_get($booking, 'title', '')"
                    :date="data_get($booking, 'date', '')"
                    :time="data_get($booking, 'time', '')"
                    :location="data_get($booking, 'location', '')"
                    :image="data_get($booking, 'image')"
                    :status="data_get($booking, 'status', 'pending')"
                    :is-upcoming="data_get($booking, 'isUpcoming', false)"
                    :href="data_get($booking, 'href', '#')"
                    :primary-href="data_get($booking, 'primaryHref', '#')"
                />
            @empty
                <div class="flex flex-col items-center gap-3 py-16 text-center">
                    <x-icon name="calendar" :size="36" class="text-grey-400" />

                    <h2 class="text-[16px] font-semibold text-grey-800">
                        Belum ada peminjaman
                    </h2>

                    <p class="text-[12px] leading-5 text-grey-500">
                        Daftar peminjaman kamu akan muncul di sini.
                    </p>
                </div>
            @endforelse

        </div>
    </main>

    <x-component_navbar active="scheduled" />

</div>
@endsection
