
@extends('layouts.app')

@section('content')
@php
    $roomId = $ruangan->id;

    $tabs = [
        [
            'label' => 'About',
            'href' => route('rooms.detail', ['id' => $roomId]),
        ],
        [
            'label' => 'Reviews',
            'href' => route('rooms.reviews', ['id' => $roomId]),
        ],
    ];

    $topReviews = collect($topUlasan);
    $allReviews = collect($semuaUlasan);
@endphp

<div class="flex min-h-full flex-col bg-grey-100">

    <header class="flex items-center gap-3 px-6 pb-4 pt-14">
        <a
            href="{{ $roomId ? url('/rooms/'.$roomId) : '#' }}"
            aria-label="Kembali ke detail ruangan"
            class="flex size-8 items-center justify-center rounded-full bg-white text-grey-800 shadow-card"
        >
            <x-icon name="arrow-left" :size="20" />
        </a>

        <h1 class="text-[20px] font-bold text-grey-800">
            Room Reviews
        </h1>
    </header>

    <x-tabs
        :items="$tabs"
        :active="1"
        class="mt-3 px-6"
    />

    <main class="flex flex-1 flex-col gap-6 pb-8 pt-5">

        <section class="flex flex-col gap-2">
            <h2 class="px-6 text-[18px] leading-7 font-semibold text-primary-800">
                Top Reviews
            </h2>

            @if($topReviews->isEmpty())
                <p class="px-6 text-[12px] text-grey-500">
                    Belum ada ulasan untuk ruangan ini.
                </p>
            @else
                <div class="no-scrollbar -mb-4 flex gap-2 overflow-x-auto px-6 pb-4">
                    @foreach($topReviews as $review)
                        <x-card_review
                            class="w-[240px] shrink-0"
                            :name="data_get($review, 'nama_pengulas', '')"
                            :date="data_get($review, 'tanggal_ulasan') ? \Illuminate\Support\Carbon::parse(data_get($review, 'tanggal_ulasan'))->format('d M Y') : ''"
                            :rating="data_get($review, 'rating', 0)"
                            :comment="data_get($review, 'komentar', '')"
                            :avatar="data_get($review, 'foto_profil')"
                        />
                    @endforeach
                </div>
            @endif
        </section>

        <section class="flex flex-col gap-2 px-6">
            <h2 class="text-[18px] leading-7 font-semibold text-primary-800">
                Reviews
            </h2>

            @if($allReviews->isEmpty())
                <p class="text-[12px] text-grey-500">
                    Belum ada ulasan yang tersedia.
                </p>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($allReviews as $review)
                        <x-card_review
                            :name="data_get($review, 'nama_pengulas', '')"
                            :date="data_get($review, 'tanggal_ulasan', '')"
                            :rating="data_get($review, 'rating', 0)"
                            :comment="data_get($review, 'komentar', '')"
                            :avatar="data_get($review, 'foto_profil')"
                        />
                    @endforeach
                </div>
            @endif
        </section>

    </main>
</div>
@endsection
