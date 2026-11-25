{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-12 Check-In berhasil.
     Variabel yang diharapkan dari controller: tidak ada.
     Tombol "Review Room" sengaja TIDAK ada: ulasan hanya boleh dibuat setelah booking selesai
     (keputusan DDL no. 9, panduan 3.6). --}}
@extends('layouts.app')

@section('content')
<div class="flex min-h-full flex-col bg-grey-100 px-6 pb-5 pt-[131px]">
  <svg viewBox="0 0 128 128" class="mx-auto size-[128px]" aria-hidden="true">
    <circle cx="64" cy="64" r="64" fill="#43B75D"/>
    <g transform="translate(32 32) scale(2.6667)" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>
    </g>
  </svg>

  <h2 class="mt-12 text-center text-[24px] leading-7 font-extrabold text-grey-800">Check-In Success</h2>
  <p class="mt-5 text-center text-[16px] leading-6 font-normal text-grey-600">
    Your room booking check-in process has been completed. Use the room accordingly as stated in Roomaster’s
    <a href="#" class="font-semibold text-primary-500 underline">user Terms &amp; Conditions.</a>
  </p>

  <x-button :href="route('home')" variant="primary" size="large" iconLeft="home" class="mt-28 h-12! w-full">Back to Home</x-button>
</div>
@endsection
