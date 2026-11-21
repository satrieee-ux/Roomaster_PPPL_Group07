{{--
  Bottom bar of laporkerusakan. Button logic only.
  Cancel -> popup "Save as Draft?"  |  Send -> popup "Kirim Laporan?"
--}}
@props([
    'cancelTarget' => 'popup-reportconfirm',
    'sendTarget'   => 'popup-reportsend',
])

<div {{ $attributes->merge(['class' => 'sticky bottom-0 z-30 flex gap-3 rounded-t-3xl bg-primary-800 px-5 pb-6 pt-4']) }}>
    <x-button variant="outline" size="large" class="flex-1" :data-popup-open="$cancelTarget">Cancel</x-button>
    <x-button variant="secondary" size="large" class="flex-1" :data-popup-open="$sendTarget">Send</x-button>
</div>
