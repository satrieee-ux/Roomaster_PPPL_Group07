{{-- Kartu notifikasi. type = notifikasi.kategori: peminjaman | pesan | laporan | sistem.
     actions: [['label'=>'View Bookings','href'=>'/bookings']] (tanpa id entitas, guide 5.9).
     read=false -> kartu ditandai belum dibaca. --}}
@props(['type' => 'sistem', 'title', 'time', 'message', 'actions' => [], 'read' => true])
@php
$map = [
  'peminjaman' => ['calendar-check',  'bg-primary-100 text-primary-500'],
  'pesan'      => ['messages-square', 'bg-secondary-100 text-secondary-500'],
  'laporan'    => ['alert-circle',    'bg-warning-100 text-warning-500'],
  'sistem'     => ['bell',            'bg-grey-200 text-grey-500'],
];
[$icon, $cls] = $map[$type] ?? $map['sistem'];
@endphp

<div {{ $attributes->class(['flex gap-2.5 rounded-xl bg-grey-50 px-5 py-4 shadow-card']) }}>
  <span class="flex size-12 shrink-0 items-center justify-center rounded-full {{ $cls }}">
    <x-icon :name="$icon" :size="24" />
  </span>

  <div class="flex min-w-0 flex-1 flex-col">
    <div class="flex items-center justify-between gap-2">
      <h3 class="flex min-w-0 items-center gap-2 text-[16px] leading-6 font-semibold text-grey-800">
        <span class="truncate">{{ $title }}</span>
        @unless($read)<span class="size-2 shrink-0 rounded-full bg-secondary-500" aria-label="Belum dibaca"></span>@endunless
      </h3>
      <span class="shrink-0 text-[12px] leading-4 font-normal text-grey-800/80">{{ $time }}</span>
    </div>
    <p class="mt-0.5 text-[12px] leading-4 font-normal text-grey-800/80">{{ $message }}</p>

    @if(count($actions))
      <div class="mt-4 flex gap-3">
        @foreach($actions as $a)
          <x-button :href="$a['href'] ?? '#'" :variant="$loop->first ? 'primary' : 'soft'" size="tiny" class="flex-1">{{ $a['label'] }}</x-button>
        @endforeach
      </div>
    @endif
  </div>
</div>
