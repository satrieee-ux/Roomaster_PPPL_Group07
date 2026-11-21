{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- Pop-up Check-In: "Are you sure?". Dibuka dengan data-popup-open="popup-checkin".
     Pakai:
       <x-popup_checkin form="form-checkin" />           → Proceed mengirim <form id="form-checkin"> (POST)
       <x-popup_checkin :confirmUrl="url('/checkin/1')" /> → Proceed berupa link (cara lama)
     form = id form check-in di halaman. Atribut HTML form="..." membuat tombol di popup
            bisa mengirim form walaupun letaknya di luar form tersebut.
     Proceed → controller mengisi waktu_checkin = NOW(), status = 'berlangsung', insert riwayat_peminjaman. --}}
@props(['id' => 'popup-checkin', 'open' => false, 'confirmUrl' => '#', 'form' => null])
<x-popup :id="$id" :open="$open" title="Are you sure?" :stack="true">
  <x-slot:body>
    Before proceeding, make sure that your check-in is aligned with Roomasters’
    <a href="#" class="font-semibold text-primary-500 underline">Terms and Conditions.</a>
  </x-slot:body>
  <x-slot:actions>
    @if($form)
      <x-button type="submit" :form="$form" variant="primary" size="large" class="w-full">Proceed</x-button>
    @else
      <x-button :href="$confirmUrl" variant="primary" size="large" class="w-full">Proceed</x-button>
    @endif
    <x-button href="#" variant="outline" size="large" class="w-full" data-popup-close>Cancel</x-button>
  </x-slot:actions>
</x-popup>
