{{-- Gelembung chat, teks saja (tanpa gambar, guide 5.7). side: in (masuk, abu) | out (keluar, gelap).
     Teks 14/20 dan padding 16 sesuai gambar referensi. Titik hijau online statis (sesuai Figma). --}}
@props(['side' => 'in', 'text' => '', 'time' => '', 'avatar' => null])
@php $out = $side === 'out'; @endphp

<div @class(['flex items-end gap-3 px-5', 'flex-row-reverse' => $out])>
  <x-avatar :src="$avatar" size="size-8" online />

  <div @class(['flex min-w-0 flex-col gap-1', 'items-end' => $out, 'items-start' => ! $out])>
    <p @class([
      'max-w-[220px] rounded-2xl p-4 text-[14px] leading-5 font-normal break-words',
      'bg-grey-200 text-grey-800' => ! $out,
      'bg-primary-900 text-white' => $out,
    ])>{{ $text }}</p>
    <span class="text-[12px] leading-4 font-medium text-grey-800/70">{{ $time }}</span>
  </div>
</div>
