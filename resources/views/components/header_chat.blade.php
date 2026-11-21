{{-- Header ruang chat: back + avatar + nama + menu. Sticky, tinggi 124px.
     response = teks balasan rata-rata, contoh "Average Response: 2h" (opsional; dari informasi_tu.rata_respon_menit).
     Titik hijau online statis (sesuai Figma). --}}
@props(['name', 'avatar' => null, 'back' => null, 'response' => null])

<header {{ $attributes->class(['sticky top-0 z-20 flex h-[124px] shrink-0 items-center gap-3 rounded-b-3xl bg-white px-10 pb-5 pt-16 shadow-card']) }}>
  <a href="{{ $back ?? 'javascript:history.back()' }}" aria-label="Kembali"
     class="mr-0.5 flex size-6 shrink-0 items-center justify-center text-grey-800">
    <x-icon name="arrow-left" :size="24" />
  </a>

  <x-avatar :src="$avatar" :name="$name" size="size-10" online />

  <div class="flex min-w-0 flex-1 flex-col text-center">
    <span class="truncate text-[16px] leading-6 font-semibold text-grey-800">{{ $name }}</span>
    @if($response)
      <span class="text-[10px] leading-[14px] font-medium text-grey-800">{{ $response }}</span>
    @endif
  </div>

  <button type="button" aria-label="Menu" class="flex size-6 shrink-0 items-center justify-center text-grey-800">
    <x-icon name="ellipsis-vertical" :size="24" />
  </button>
</header>
