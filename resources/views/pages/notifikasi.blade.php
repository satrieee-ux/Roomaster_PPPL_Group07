@extends('layouts.app')

@section('content')
@php
  // Tab mengikuti notifikasi.kategori (guide 5.9)
  $tabs = [
    ['label' => 'All',      'href' => url('/notifikasi')],
    ['label' => 'Bookings', 'href' => url('/notifikasi?filter=peminjaman')],
    ['label' => 'Messages', 'href' => url('/notifikasi?filter=pesan')],
    ['label' => 'Reports',  'href' => url('/notifikasi?filter=laporan')],
    ['label' => 'System',   'href' => url('/notifikasi?filter=sistem')],
  ];
@endphp

<div class="flex min-h-full flex-col bg-grey-100">
  <x-header_topappbar title="Notifications" :back="route('home')" />

  <main class="flex flex-1 flex-col pb-5 pt-5">
    <x-tabs variant="pill" :items="$tabs" :active="$active" />

    <div class="mt-6 flex flex-col gap-4 px-6">
      @forelse($notifications as $n)
        <x-card_notification :type="$n['type']" :title="$n['title']" :time="$n['time']"
                             :message="$n['message']" :actions="$n['actions']" :read="$n['read']" />
      @empty
        <p class="py-10 text-center text-[14px] leading-5 font-normal text-grey-500">Belum ada notifikasi.</p>
      @endforelse
    </div>
  </main>
</div>
@endsection
