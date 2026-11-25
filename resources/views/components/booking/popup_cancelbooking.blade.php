{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-11 Memesan Ruangan; juga dipakai di UC-08 --}}
{{-- Popup "Cancel Booking?". Dibuka dengan data-popup-open="popup-cancelbooking".
     Contoh: <x-popup_cancelbooking :open="false" :yesUrl="route('rooms.detail', $id_ruangan)" />
     yesUrl = tujuan tombol Yes. Di form booking: kembali ke halaman detail ruangan.
     Tombol No hanya menutup popup.
     Popup ini tidak menulis ke database. TODO [TIM] no. 3: jika baris peminjaman 'draft' sudah ada,
     apakah statusnya diubah menjadi 'dibatalkan' atau barisnya dihapus? (dikerjakan di controller) --}}
@props(['id' => 'popup-cancelbooking', 'open' => true, 'yesUrl' => null, 'confirmUrl' => '#', 'room' => null, 'closeUrl' => null])
<x-popup :id="$id" :open="$open" title="Cancel Booking?" :stack="true">
  <x-slot:actions>
    <x-button :href="$yesUrl ?? $confirmUrl" variant="primary" size="large" class="w-full">Yes</x-button>
    <x-button href="#" variant="outline" size="large" class="w-full" data-popup-close>No</x-button>
  </x-slot:actions>
</x-popup>
