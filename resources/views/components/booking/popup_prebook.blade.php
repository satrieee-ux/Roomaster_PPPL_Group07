{{-- Dibuat oleh: Thalia Harnum Fathina (5026241201) --}}
{{-- UC-11 Memesan Ruangan --}}

{{-- Konfirmasi sebelum mengirim pengajuan peminjaman.
     submitUrl harus berasal dari route POST backend yang valid.
--}}

@props([
    'id' => 'popup-prebook',
    'open' => false,
    'submitUrl' => null,
    'termsUrl' => null,
])

<x-popup :id="$id" :open="$open" title="Are you sure?" :stack="true">

    <x-slot:body>
        Before proceeding, make sure that your booking is aligned with
        Roomaster's

        @if($termsUrl)
            <a href="{{ $termsUrl }}" class="font-semibold text-primary-500 underline">
                Terms and Conditions.
            </a>
        @else
            <span class="font-semibold text-primary-500">
                Terms and Conditions.
            </span>
        @endif
    </x-slot:body>

    <x-slot:actions>
        @if($submitUrl)
            {{-- Tombol Proceed: POST ke $submitUrl untuk mengirim pengajuan peminjaman (PeminjamanController@simpanPengajuan). --}}
            <form method="POST" action="{{ $submitUrl }}" class="w-full">
                @csrf

                <x-button
                    type="submit"
                    variant="primary"
                    size="large"
                    class="w-full"
                >
                    Proceed
                </x-button>
            </form>
        @else
            <x-button
                type="button"
                variant="disabled"
                size="large"
                class="w-full"
                disabled
            >
                Proceed
            </x-button>
        @endif

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
