{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-09 Mengunggah Dokumen Surat Peminjaman; juga dipakai di UC-08 --}}
{{-- BookDocBar: bar bawah dengan satu tombol Continue.
     Contoh: <x-component_bookdocbar :active="true" :href="url('/booking/1/ringkasan')" />
     active = false → tombol abu-abu (belum bisa diklik).
     submit = true  → tombol mengirim <form> tempat bar ini berada.
     submit = false → tombol berupa link ke $href.
     doc_upload menukar tombol otomatis lewat atribut data-docbar-on / data-docbar-off. --}}
@props(['active' => false, 'href' => '#', 'label' => 'Continue', 'submit' => false])

<div {{ $attributes->class(['sticky bottom-0 z-30 flex h-[100px] w-full shrink-0 items-start rounded-t-[32px] bg-primary-900 px-6 pt-5']) }}>
  {{-- Versi aktif: tombol submit atau link, tergantung $submit --}}
  @if($submit)
    <x-button type="submit" variant="secondary" size="medium" :class="'w-full '.($active ? '' : 'hidden')" data-docbar-on>{{ $label }}</x-button>
  @else
    <x-button :href="$href" variant="secondary" size="medium" :class="'w-full '.($active ? '' : 'hidden')" data-docbar-on>{{ $label }}</x-button>
  @endif

  {{-- Versi nonaktif: tampil jika $active = false --}}
  <x-button type="button" variant="disabled" size="medium" :class="'w-full '.($active ? 'hidden' : '')" data-docbar-off>{{ $label }}</x-button>
</div>
