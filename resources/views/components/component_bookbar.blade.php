
{{-- BookBar Room Detail.
     Book Now menuju alur pemesanan.
     Favorit mengikuti tabel ruangan_favorit.
--}}

@props([
    'href' => '#',
    'favorite' => false,
    'favoriteAction' => null,
])

<div {{ $attributes->class([
    'sticky bottom-0 z-30 flex h-[100px] w-full shrink-0 items-start gap-4 rounded-t-[32px] bg-primary-900 px-6 pt-5'
]) }}>

    <x-button
        :href="$href"
        variant="secondary"
        size="medium"
        class="flex-1"
    >
        Book Now
    </x-button>

    @if($favoriteAction)
        <form action="{{ $favoriteAction }}" method="POST">
            @csrf

            <button
                type="submit"
                aria-label="{{ $favorite ? 'Hapus dari favorit' : 'Tambah ke favorit' }}"
                aria-pressed="{{ $favorite ? 'true' : 'false' }}"
                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-grey-50 text-primary-500"
            >
                <x-icon
                    name="heart"
                    :size="20"
                    @class(['fill-current' => $favorite])
                />
            </button>
        </form>
    @else
        <button
            type="button"
            disabled
            aria-label="Favorit belum tersedia"
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-grey-50 text-primary-500 opacity-50"
        >
            <x-icon
                name="heart"
                :size="20"
                @class(['fill-current' => $favorite])
            />
        </button>
    @endif

</div>
