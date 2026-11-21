
{{-- Popup konfirmasi checkout.
     Mengirim form checkout-form setelah backend siap.
--}}

@props([
    'id' => 'popup-checkout',
    'open' => false,
    'formId' => 'checkout-form',
    'canSubmit' => false,
    'termsUrl' => null,
])

<x-popup :id="$id" :open="$open" title="Are you sure?" :stack="true">

    <x-slot:body>
        Before proceeding, make sure that your check-out is aligned with Roomaster's

        @if($termsUrl)
            <a href="{{ $termsUrl }}"
               class="font-semibold text-primary-500 underline">
                Terms and Conditions.
            </a>
        @else
            <span class="font-semibold text-primary-500">
                Terms and Conditions.
            </span>
        @endif
    </x-slot:body>

    <x-slot:actions>

        <x-button
            type="submit"
            :form="$formId"
            :variant="$canSubmit ? 'primary' : 'disabled'"
            size="large"
            class="w-full"
        >
            Proceed
        </x-button>

        <x-button
            type="button"
            variant="outline"
            size="large"
            class="w-full"
            data-popup-close
        >
            Cancel
        </x-button>

    </x-slot:actions>

</x-popup>
