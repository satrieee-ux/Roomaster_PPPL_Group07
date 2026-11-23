{{-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) --}}
{{-- UC-08 Mengisi Form Peminjaman --}}
{{-- Popup "Draft Found": tampil saat user menekan Book Now di ruangan yang sudah punya draft.
     Contoh: <x-popup_booking_draft :open="true" :lanjutUrl="route('booking.form', ['id' => 1, 'lanjut' => 1])"
                                    :timpaUrl="route('booking.timpa', 1)" />
     lanjutUrl = buka form terisi draft yang ada.
     timpaUrl  = tujuan POST: hapus draft lama (beserta dokumennya), lalu buka form kosong. --}}
@props(['id' => 'popup-draft', 'open' => false, 'lanjutUrl' => null, 'timpaUrl' => null])
<x-popup :id="$id" :open="$open" icon="alert-circle" tone="primary" title="Draft Found"
         message="You already have a draft booking for this room. Continue it, or start over?" :stack="true">
  <x-slot:actions>
    <x-button :href="$lanjutUrl" variant="primary" size="large" class="w-full">Continue Draft</x-button>
    <form method="POST" action="{{ $timpaUrl }}" class="w-full">
      @csrf
      <x-button type="submit" variant="outline" size="large" class="w-full">Overwrite with New</x-button>
    </form>
  </x-slot:actions>
</x-popup>
