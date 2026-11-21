{{--
  Bottom bar on laporkerusakan: Cancel (outline) + Send (orange) on a navy bar.
  Button logic only, no DB. Cancel opens the "Save as Draft?" popup.
--}}
@props([
    'formId'   => 'form-laporan',
    'disabled' => false,
])

<div {{ $attributes->merge(['class' => 'fixed inset-x-0 bottom-0 z-30 flex gap-3 rounded-t-3xl bg-[#153343] px-5 pb-6 pt-4']) }}>
    {{-- SWAP: hook into popup.blade.php once it exists --}}
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-popup', { detail: 'reportconfirm' }))"
            class="flex-1 rounded-full border border-white/70 py-2.5 text-sm font-semibold text-white">
        Cancel
    </button>

    <button type="submit" form="{{ $formId }}" @disabled($disabled)
            class="flex-1 rounded-full bg-[#D97A47] py-2.5 text-sm font-semibold text-white disabled:opacity-50">
        Send
    </button>
</div>
