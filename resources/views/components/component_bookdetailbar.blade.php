{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Mengisi Form Peminjaman --}}
{{-- BookDetailBar: bar bawah pada form booking langkah Details.
     Contoh: <x-component_bookdetailbar :active="true" />
     active = false → tombol Check Availability abu-abu (belum bisa diklik).
     active = true  → tombol Check Availability aktif dan membuka popup pilih waktu.
     Kedua versi tombol selalu ada di HTML; yang tidak dipakai disembunyikan dengan kelas "hidden".
     Popup pilih tanggal menukar tombol ini lewat atribut data-ca-on / data-ca-off. --}}
@props(['active' => false])

<div {{ $attributes->class(['sticky bottom-0 z-30 flex h-[100px] w-full shrink-0 items-start gap-4 rounded-t-[32px] bg-primary-900 px-6 pt-5']) }}>
  {{-- Ikon keranjang (hiasan) --}}
  <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white">
    <x-icon name="shopping-cart" :size="20" />
  </span>

  {{-- Tombol Cancel: membuka popup konfirmasi batal booking --}}
  <x-button type="button" variant="soft" size="medium" class="w-[101px] shrink-0" data-popup-open="popup-cancelbooking">Cancel</x-button>

  {{-- Versi aktif: tampil jika $active = true --}}
  <x-button type="button" variant="secondary" size="medium" :class="'flex-1 '.($active ? '' : 'hidden')"
            data-ca-on data-popup-open="popup-waktu">Check Availability</x-button>

  {{-- Versi nonaktif: tampil jika $active = false --}}
  <x-button type="button" variant="disabled" size="medium" :class="'flex-1 '.($active ? 'hidden' : '')"
            data-ca-off>Check Availability</x-button>
</div>
