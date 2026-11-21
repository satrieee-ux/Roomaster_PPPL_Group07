{{-- Dibuat oleh: Thalia Harnum Fathina (5026241201) --}}
{{-- UC-13 Check-Out Ruangan --}}
{{-- Diperbaiki oleh: Muhammad Satrio Adjie (5026241065), disamakan dengan gambar referensi dan URL submit check-out --}}

@extends('layouts.app')

@section('content')
@php
    // Daftar checklist (tidak ada di DDL, hanya tampilan). Controller boleh menimpanya lewat $checklist.
    $checklist = collect($checklist ?? [])->whenEmpty(fn () => collect([
        'Trash bins emptied',
        'All surfaces wiped down',
        'Floors swept and vacuumed',
        'Personal belongings removed',
        'Lights and electronics turned off',
    ]));

    // POST check-out: dari controller, atau dari route bila halaman dikirimi $booking
    $checkoutSubmitUrl = $checkoutSubmitUrl
        ?? (isset($booking) ? route('checkout.store', $booking->id) : null);
@endphp

<div class="flex min-h-full flex-col bg-grey-100">

    <x-header_topappbar
        title="Check-Out"
        :back="route('home')"
        :showHome="false"
    />

    {{-- Form check-out: POST ke $checkoutSubmitUrl. Field: checklist[] (item yang dicentang) dan notes (catatan). --}}
    <form
        id="checkout-form"
        method="POST"
        action="{{ $checkoutSubmitUrl ?? '#' }}"
        enctype="multipart/form-data"
        class="flex flex-1 flex-col"
    >
        @csrf

        <main class="flex flex-1 flex-col gap-4 px-6 pb-5 pt-5">

            <div class="flex flex-col gap-1">
                <h2 class="text-[24px] leading-7 font-extrabold text-grey-800">
                    Check-Out Process
                </h2>

                <p class="text-[14px] leading-5 text-grey-600">
                    Please complete the final cleanliness verification
                    and submit a photo of the room condition before
                    concluding your session.
                </p>
            </div>

            {{-- Cleanliness Checklist --}}
            <section class="rounded-xl border border-grey-300 bg-grey-50 px-5 pb-1 pt-4 shadow-card">

                <h3 class="text-[14px] leading-5 font-semibold text-grey-800">
                    Cleanliness Checklist
                </h3>

                <div class="mt-1 flex flex-col">
                    @forelse($checklist as $item)
                        <x-checkbox
                            name="checklist[]"
                            :value="$item"
                            :label="$item"
                            side="right"
                            :muted="true"
                            :class="$loop->first ? 'h-14' : 'h-14 border-t border-grey-200'"
                        />
                    @empty
                        <p class="py-4 text-[12px] text-grey-500">
                            Checklist belum tersedia.
                        </p>
                    @endforelse
                </div>
            </section>

            <hr class="border-grey-300">

            {{-- Final Condition Documentation --}}
            <div class="flex flex-col gap-1">
                <h3 class="text-[18px] leading-7 font-semibold text-grey-800">
                    Final Condition Documentation
                </h3>

                <p class="text-[14px] leading-5 text-grey-600">
                    Capture the state of the room to ensure compliance
                    with facility standards.
                </p>
            </div>

            {{-- Komponen upload milik Thoriq --}}
            <x-media_upload />

            <hr class="border-grey-300">

            {{-- Additional Notes --}}
            <div class="flex flex-col gap-2">
                <label
                    for="notes"
                    class="text-[14px] leading-5 font-semibold text-grey-800"
                >
                    Additional Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    placeholder="Note any existing damages, odors, or missing items here..."
                    class="h-[120px] w-full resize-none rounded-lg border border-grey-600 bg-white px-4 py-3 text-[14px] leading-5 text-grey-800 outline-hidden placeholder:text-grey-400"
                >{{ old('notes') }}</textarea>
            </div>

        </main>
    </form>

    {{-- Tombol membuka popup konfirmasi --}}
    <div class="sticky bottom-0 z-30 bg-grey-100 px-6 pb-5">

        <div class="flex flex-col gap-3 border-t border-grey-300 pt-4">

            <x-button
                type="button"
                variant="primary"
                size="large"
                class="w-full"
                data-popup-open="popup-checkout"
            >
                Check Out
            </x-button>

            <p class="text-center text-[12px] leading-4 text-grey-600">
                By checking out, you agree to the facility usage
                terms and conditions.
            </p>

        </div>
    </div>

    {{-- Popup mengirim form yang sama --}}
    <x-popup_checkout
        form-id="checkout-form"
        :can-submit="filled($checkoutSubmitUrl)"
    />

</div>
@endsection
