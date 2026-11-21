{{-- Header gelap ringkas (tanpa sapaan & search). Dipakai: Your Bookings. Sticky.
     Avatar otomatis dari mahasiswa_its.foto_profil akun yang login. Titik "online" dihapus. --}}
@props(['name' => null, 'avatar' => null, 'searchUrl' => '/rooms/search', 'chatUrl' => '/chat', 'notifUrl' => '/notifikasi'])
@php
$mahasiswa = auth()->user()?->mahasiswa;
$name   ??= $mahasiswa?->nama ?? 'Guest';
$avatar ??= $mahasiswa?->foto_profil;
@endphp

<header {{ $attributes->class(['sticky top-0 z-20 shrink-0 rounded-b-[32px] bg-primary-900 text-white']) }}>
  <div class="flex h-[108px] items-end justify-between px-2 pb-4">
    <img src="{{ asset($avatar ?: 'images/avatar.jpg') }}" alt="{{ $name }}" class="mb-1.5 ml-4 size-8 rounded-full bg-primary-100 object-cover">
    <div class="flex items-center">
      <a href="{{ url($searchUrl) }}" aria-label="Cari" class="flex h-11 w-12 items-center justify-center"><x-icon name="search" :size="24" /></a>
      <a href="{{ url($chatUrl) }}" aria-label="Chat" class="flex h-11 w-12 items-center justify-center"><x-icon name="message-square" :size="24" /></a>
      <a href="{{ url($notifUrl) }}" aria-label="Notifikasi" class="flex h-11 w-12 items-center justify-center"><x-icon name="bell" :size="24" /></a>
    </div>
  </div>
</header>
