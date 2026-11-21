{{-- Item daftar percakapan: avatar, nama, cuplikan 2 baris, jam, badge belum dibaca.
     Tanpa titik online (guide 5.7). --}}
@props(['name', 'preview' => '', 'time' => '', 'unread' => 0, 'href' => '#', 'avatar' => null])

<a href="{{ $href }}" {{ $attributes->class(['flex items-center gap-3 rounded-xl bg-grey-100 px-4 py-3 shadow-card']) }}>
  <x-avatar :src="$avatar" :name="$name" size="size-12" />

  <div class="flex min-w-0 flex-1 flex-col">
    <span class="truncate text-[16px] leading-6 font-semibold text-grey-800">{{ $name }}</span>
    <span class="mt-1 line-clamp-2 text-[12px] leading-4 font-normal text-grey-800/80">{{ $preview }}</span>
  </div>

  <div class="flex shrink-0 flex-col items-end justify-between gap-3 self-stretch">
    <span class="text-[10px] leading-[14px] font-medium text-grey-500">{{ $time }}</span>
    @if($unread > 0)
      <span class="flex size-6 items-center justify-center rounded-full bg-primary-900 text-[12px] leading-4 font-semibold text-white">{{ $unread }}</span>
    @endif
  </div>
</a>
