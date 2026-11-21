@extends('layouts.app')

@section('content')
{{-- Check-Out berhasil. Review Room membuka popup ulasan (UC-14).
     $id = id peminjaman yang baru di-check-out, dikirim controller (redirect ke halaman ini).
     Tanpa $id, tombol Post di popup tetap nonaktif. --}}
<div class="flex min-h-full flex-col bg-grey-100 px-6 pb-5 pt-[131px]">
  <svg viewBox="0 0 128 128" class="mx-auto size-[128px]" aria-hidden="true">
    <circle cx="64" cy="64" r="64" fill="#43B75D"/>
    <g transform="translate(32 32) scale(2.6667)" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>
    </g>
  </svg>

  <h2 class="mt-4 text-center text-[24px] leading-7 font-extrabold text-grey-800">Check-Out Success</h2>
  <p class="mt-5 text-center text-[16px] leading-6 font-normal text-grey-600">
    Your room booking check-out process has been completed. If there are any faulty equipment, please report accordingly as stated in Roomaster’s
    <a href="#" class="font-semibold text-primary-500 underline">user Terms &amp; Conditions.</a>
  </p>

  <div class="mt-24 flex flex-col gap-2">
    <x-button :href="route('home')" variant="primary" size="large" iconLeft="home" class="h-12! w-full">Back to Home</x-button>
    <x-button type="button" variant="secondary" size="large" class="h-12! w-full" data-popup-open="popup-buatreview">Review Room</x-button>
  </div>

  <x-popup_buatreview :submitUrl="isset($id) ? route('ulasan.store', $id) : null" />
</div>
@endsection
