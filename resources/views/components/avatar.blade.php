{{-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) --}}
{{-- Avatar bulat. src = path relatif (foto_profil), fallback images/avatar.jpg.
     Tanpa titik online (tidak ada di DDL). Ukuran lewat prop size (class Tailwind). --}}
@props(['src' => null, 'name' => '', 'size' => 'size-10'])

<img src="{{ asset($src ?: 'images/avatar.jpg') }}" alt="{{ $name }}"
     {{ $attributes->class(['shrink-0 rounded-full bg-primary-100 object-cover', $size]) }}>
