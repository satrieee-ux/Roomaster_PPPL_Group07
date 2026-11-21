{{-- ChatBar: bar pengetik sticky bawah. Tanpa ikon klip (guide 5.7).
     Input bernama isi_pesan = kolom pesan.isi_pesan. --}}
@props(['action' => '#', 'placeholder' => 'Type a message...'])

<form action="{{ $action }}" method="POST"
      {{ $attributes->class(['sticky bottom-0 z-30 flex h-[100px] w-full shrink-0 items-start gap-4 rounded-t-[32px] bg-primary-900 px-6 pt-5']) }}>
  @csrf
  <label class="flex h-10 min-w-0 flex-1 items-center rounded-full bg-grey-200 px-4">
    <input type="text" name="isi_pesan" placeholder="{{ $placeholder }}" autocomplete="off" maxlength="1000" required
           class="min-w-0 flex-1 bg-transparent text-[14px] leading-5 font-normal text-grey-800 outline-none placeholder:text-grey-400">
  </label>
  <button type="submit" aria-label="Kirim"
          class="flex h-10 w-14 shrink-0 items-center justify-center rounded-full bg-secondary-500 text-white">
    <x-icon name="send" :size="24" />
  </button>
</form>
