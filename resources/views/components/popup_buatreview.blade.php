
{{-- Popup UC-14: memberikan rating dan komentar.
     submitUrl harus merupakan endpoint POST ulasan yang valid.
--}}

@props([
    'id' => 'popup-buatreview',
    'open' => false,
    'submitUrl' => null,
])

<x-popup :id="$id" :open="$open" title="Rate & Review">

    <x-slot:body>
        Keep your messages short, but make sure they cover everything you need to say.
    </x-slot:body>

    <form
        action="{{ $submitUrl ?? '#' }}"
        method="POST"
    >
        @csrf

        <div class="flex justify-center gap-1.5" data-rating>
            @for($i = 1; $i <= 5; $i++)
                <button
                    type="button"
                    data-star="{{ $i }}"
                    aria-label="{{ $i }} bintang"
                    class="text-grey-400"
                >
                    <x-icon
                        name="star"
                        :size="36"
                        class="fill-current"
                    />
                </button>
            @endfor
        </div>

        <input
            type="hidden"
            name="rating"
            value="0"
        >

        <textarea
            name="komentar"
            rows="8"
            maxlength="1000"
            placeholder="Fill your review here..."
            class="mt-5 h-[222px] w-full resize-none rounded-xl border border-grey-200 bg-white p-3 text-[14px] leading-5 text-grey-800 outline-none placeholder:text-grey-400"
        ></textarea>

        <div class="mt-6 flex gap-4">

            <x-button
                type="button"
                variant="outline"
                size="large"
                class="flex-1"
                data-popup-close
            >
                Cancel
            </x-button>

            <x-button
                type="submit"
                :variant="$submitUrl ? 'primary' : 'disabled'"
                size="large"
                class="flex-1"
            >
                Post
            </x-button>

        </div>

    </form>
</x-popup>

<script>
document.querySelectorAll('#{{ $id }} [data-rating]').forEach(function (box) {
    var input = box.parentElement.querySelector('input[name="rating"]');

    box.addEventListener('click', function (event) {
        var button = event.target.closest('[data-star]');
        if (!button) return;

        var selectedRating = Number(button.dataset.star);

        input.value = selectedRating;

        box.querySelectorAll('[data-star]').forEach(function (star) {
            var active = Number(star.dataset.star) <= selectedRating;

            star.classList.toggle('text-secondary-300', active);
            star.classList.toggle('text-grey-400', !active);
        });
    });
});
</script>
