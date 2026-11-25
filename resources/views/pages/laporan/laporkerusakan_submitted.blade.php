{{-- Dibuat oleh: Anastasia Azzahra Rafif (5026241036) --}}
{{-- UC-19 Melaporkan Informasi Kerusakan Fasilitas --}}
@extends('layouts.app')

@section('content')
<div class="flex min-h-full flex-col bg-grey-100 px-6 pb-5 pt-[91px]">
  <svg viewBox="0 0 128 128" class="mx-auto size-[128px]" aria-hidden="true">
    <circle cx="64" cy="64" r="64" fill="#43B75D"/>
    <circle cx="64" cy="64" r="30" fill="none" stroke="#fff" stroke-width="7"/>
    <path d="M52 65l9 9 17-20" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>

  <h2 class="mt-6 text-center text-[24px] leading-7 font-extrabold text-grey-800">Report Submitted</h2>
  <p class="mt-1 text-center text-[14px] leading-5 font-semibold text-grey-500">Report #{{ $kode }}</p>
  <p class="mt-4 text-center text-[16px] leading-6 font-normal text-grey-600">
    Your facility report has been successfully logged into the system. A maintenance team will review the details shortly.
  </p>

  {{-- Timeline statis (guide: timeline laporan tidak membaca DB) --}}
  <x-activity_timeline class="mt-8 px-3" :items="[
    ['icon' => 'check',          'state' => 'done',    'title' => 'Report Submitted', 'desc' => 'Just Now'],
    ['icon' => 'hourglass',      'state' => 'current', 'title' => 'Pending Review',   'desc' => 'Awaiting TU/Sarpras Admin approval.'],
    ['icon' => 'calendar-check', 'state' => 'todo',    'title' => 'Confirmation',     'desc' => 'Taken action for report'],
  ]" />

  <div class="mt-8 flex flex-col gap-2">
    <x-button :href="route('report.summary', $laporan->id)" variant="secondary" size="large" iconRight="arrow-right" class="w-full">View Report Details</x-button>
    <x-button :href="url('/menu-utama')" variant="primary" size="large" iconLeft="home" class="w-full">Back to Home</x-button>
  </div>
</div>
@endsection
