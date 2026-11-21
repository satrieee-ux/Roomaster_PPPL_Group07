{{-- Dibuat oleh: Anastasia Azzahra Rafif (5026241036) --}}
{{-- UC-15 Mengirim Pesan --}}
@extends('layouts.app')

@section('content')
<div class="flex min-h-full flex-col bg-grey-50">
  <x-header_chat :name="$admin['name']" :avatar="$admin['avatar'] ?? null" :back="url('/chat')" />

  <main class="flex flex-1 flex-col gap-6 pb-5 pt-4">
    @if(!empty($date))
      <span class="self-center rounded-full bg-grey-800 px-3 py-1 text-[12px] leading-4 font-medium text-white">{{ $date }}</span>
    @endif

    @forelse($messages as $m)
      <x-chat_bubble :side="$m['side']" :text="$m['text']" :time="$m['time']" :avatar="$m['avatar'] ?? null" />
    @empty
      <p class="py-10 text-center text-[14px] leading-5 font-normal text-grey-500">Belum ada pesan. Mulai percakapan di bawah.</p>
    @endforelse
  </main>

  <x-component_chatbar :action="route('chat.send', $admin['id'])" />
</div>

<script>
  // buka percakapan di pesan terbaru
  window.addEventListener('load', function () { window.scrollTo(0, document.body.scrollHeight); });
</script>
@endsection
