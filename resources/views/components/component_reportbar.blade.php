{{-- Dibuat oleh: Anastasia Azzahra Rafif (5026241036) --}}
{{-- UC-20 Melihat Ringkasan & History Laporan; juga dipakai di UC-19 --}}
{{--
  Bar bawah laporkerusakan. Hanya logika tombol.
  Cancel -> popup "Save as Draft?"  |  Send -> popup "Kirim Laporan?"
  Gaya mengikuti component_bookdocbar dan gambar reportBottomBar.
--}}
@props([
    'cancelTarget' => 'popup-reportconfirm',
    'sendTarget'   => 'popup-reportsend',
])

<div {{ $attributes->class(['sticky bottom-0 z-30 flex h-[100px] w-full shrink-0 items-start gap-4 rounded-t-[32px] bg-primary-900 px-6 pt-5']) }}>
    <x-button variant="soft" size="medium" class="flex-1" :data-popup-open="$cancelTarget">Cancel</x-button>
    <x-button variant="secondary" size="medium" class="flex-1" :data-popup-open="$sendTarget">Send</x-button>
</div>
