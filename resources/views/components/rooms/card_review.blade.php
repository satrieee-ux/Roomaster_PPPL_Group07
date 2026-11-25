{{-- Dibuat oleh: Thalia Harnum Fathina (5026241201) --}}
{{-- UC-05 Membaca Ulasan Pengguna Ruangan --}}
{{-- Kartu ulasan: avatar + nama + tanggal + bintang, lalu komentar.
     Pakai: <x-card_review name="Nabila Putri" date="23 June 2026" :rating="5" comment="..." /> --}}
@props(['name', 'date', 'comment', 'rating' => 5, 'avatar' => null])

<div {{ $attributes->class(['flex flex-col gap-2 rounded-xl bg-white px-4 py-3 shadow-card']) }}>
  <div class="flex items-center gap-3">
    <x-avatar :src="$avatar" :name="$name" size="size-10" online />

    <div class="flex min-w-0 flex-1 flex-col">
      <span class="truncate text-[14px] leading-5 font-semibold text-grey-800">{{ $name }}</span>
      <span class="text-[10px] leading-[14px] font-medium text-grey-500">{{ $date }}</span>
    </div>

    <div class="flex shrink-0 gap-px self-start pt-1">
      @for($i = 1; $i <= 5; $i++)
        <x-icon name="star" :size="12"
          :class="$i <= $rating ? 'fill-current text-secondary-300' : 'text-grey-300'" />
      @endfor
    </div>
  </div>

  <p class="text-[10px] leading-[14px] font-medium text-grey-800">{{ $comment }}</p>
</div>
