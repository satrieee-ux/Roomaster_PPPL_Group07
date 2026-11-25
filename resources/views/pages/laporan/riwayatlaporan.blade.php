{{-- Dibuat oleh: Anastasia Azzahra Rafif (5026241036) --}}
{{-- UC-20 Melihat Ringkasan & History Laporan --}}
@extends('layouts.app')

@section('content')
@php
  // Tab mengikuti status laporan di DDL. "Approved" tidak punya padanan [TIM].
  $tabs = [
    ['label' => 'All',         'href' => url('/qreporting/history')],
    ['label' => 'Pending',     'href' => url('/qreporting/history?filter=pending')],
    ['label' => 'In Progress', 'href' => url('/qreporting/history?filter=diproses')],
    ['label' => 'Resolved',    'href' => url('/qreporting/history?filter=selesai')],
  ];
@endphp

<div class="flex min-h-full flex-col bg-grey-100">
  {{-- Header + tab sticky; hanya kartu yang menggulir --}}
  <div class="sticky top-0 z-20 bg-grey-100">
    <x-header_topappbar title="Reports History" :back="url('/qreporting')" />
    <x-tabs :items="$tabs" :active="$active" class="mt-5 px-6" />
  </div>

  <main class="flex flex-1 flex-col pb-5">
    <div class="mt-4 flex flex-col gap-4 px-6">
      @forelse($reports as $r)
        <x-card_report
            :id="$r['id']"
            :judul="$r['judul']"
            :kode-ruangan="$r['kode_ruangan']"
            :lokasi="$r['lokasi']"
            :tanggal="$r['tanggal']"
            :kategori="$r['kategori']"
            :status="$r['status']"
            :detail-href="url('/qreporting/summary/'.$r['id'])"
            :continue-href="url('/qreporting/report?draft='.$r['id'])" />
      @empty
        <p class="py-10 text-center text-[14px] leading-5 font-normal text-grey-500">Belum ada laporan.</p>
      @endforelse
    </div>
  </main>
</div>
@endsection
