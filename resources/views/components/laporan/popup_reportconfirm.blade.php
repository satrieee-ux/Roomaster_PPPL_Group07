{{-- Dibuat oleh: Anastasia Azzahra Rafif (5026241036) --}}
{{-- UC-19 Melaporkan Informasi Kerusakan Fasilitas --}}
{{-- Pop-up "Save as Draft?" (Cancel on the report form). Yes = submit form as draft, No = discard. --}}
@props(['id' => 'popup-reportconfirm', 'open' => false, 'formId' => 'report-form', 'noUrl' => '#'])
<x-popup :id="$id" :open="$open" title="Save as Draft?" :stack="true">
  <x-slot:body>Draft your reports before cancelling to continue right where you left off.</x-slot:body>
  <x-slot:actions>
    <x-button type="submit" :form="$formId" name="aksi" value="draft" variant="primary" size="large" class="w-full">Yes</x-button>
    <x-button :href="$noUrl" variant="outline" size="large" class="w-full">No</x-button>
  </x-slot:actions>
</x-popup>
