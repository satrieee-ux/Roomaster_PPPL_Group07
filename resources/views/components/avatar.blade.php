{{-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) --}}
{{-- Avatar bulat. src = path relatif (foto_profil), fallback images/avatar.jpg.
     online = titik hijau di pojok kanan bawah, sesuai gambar referensi. --}}
@props(['src' => null, 'name' => '', 'size' => 'size-10', 'online' => false])

<span {{ $attributes->class(['relative inline-flex shrink-0']) }}>
  <img src="{{ asset($src ?: 'images/avatar.jpg') }}" alt="{{ $name }}"
       class="{{ $size }} rounded-full bg-primary-100 object-cover">
  @if($online)
    <span class="absolute bottom-0 right-0 size-2.5 rounded-full border-2 border-white bg-success-500"></span>
  @endif
</span>
