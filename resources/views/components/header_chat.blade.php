{{-- Header ruang chat: back + avatar + nama + menu. Sticky, tinggi 124px.
     Tanpa titik online dan tanpa "Average Response" (guide 5.7). --}}
@props(['name', 'avatar' => null, 'back' => null])

<header {{ $attributes->class(['sticky top-0 z-20 flex h-[124px] shrink-0 items-center gap-3 rounded-b-3xl bg-white px-10 pb-5 pt-16 shadow-card']) }}>
  <a href="{{ $back ?? 'javascript:history.back()' }}" aria-label="Kembali"
     class="mr-0.5 flex size-6 shrink-0 items-center justify-center text-grey-800">
    <x-icon name="arrow-left" :size="24" />
  </a>

  <x-avatar :src="$avatar" :name="$name" size="size-10" />

  <div class="flex min-w-0 flex-1 flex-col text-center">
    <span class="truncate text-[16px] leading-6 font-semibold text-grey-800">{{ $name }}</span>
  </div>

  <button type="button" aria-label="Menu" class="flex size-6 shrink-0 items-center justify-center text-grey-800">
    <x-icon name="ellipsis-vertical" :size="24" />
  </button>
</header>
