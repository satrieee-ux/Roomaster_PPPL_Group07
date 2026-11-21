{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Mengisi Form Peminjaman --}}
{{-- Dropdown kustom (pengganti <select> biasa supaya tampilannya sesuai desain).
     Contoh: <x-dropdown name="departemen_penyelenggara" :options="$list" :value="$terpilih" placeholder="Select" />
     options boleh berupa:
       - array teks biasa:        ['A', 'B']
       - array value + label:     [['value' => 'a', 'label' => 'A'], ...]
     Nilai terpilih disimpan di <input type="hidden" name="...">, jadi ikut terkirim bersama form. --}}
@props(['name', 'options' => [], 'value' => null, 'placeholder' => 'Select', 'id' => null])
@php
// id tombol; jika tidak diisi, dibuat dari nama input
$id = $id ?? 'dd-'.$name;

// Samakan bentuk semua pilihan menjadi ['value' => ..., 'label' => ...]
$opts = [];
foreach ($options as $o) {
    if (is_array($o)) {
        $opts[] = $o;
    } else {
        $opts[] = ['value' => $o, 'label' => $o];
    }
}

// Cari pilihan yang sedang terpilih (untuk teks di tombol)
$cur = null;
foreach ($opts as $o) {
    if ($o['value'] === $value) {
        $cur = $o;
        break; // berhenti di pilihan pertama yang cocok
    }
}
@endphp

<div {{ $attributes->class(['relative']) }} data-dropdown>
  {{-- Input tersembunyi: inilah yang dikirim ke server --}}
  <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-dd-input>

  {{-- Tombol pembuka: menampilkan label terpilih atau placeholder --}}
  <button type="button" id="{{ $id }}" aria-haspopup="listbox" aria-expanded="false" data-dd-trigger
          class="flex h-[46px] w-full items-center justify-between gap-3 rounded-xl border border-grey-200 bg-grey-50 px-3 text-left shadow-[0_1px_2px_rgba(20,26,40,0.04)] outline-none focus-visible:border-primary-500">
    <span data-dd-label class="min-w-0 flex-1 truncate text-[16px] leading-6 font-normal text-grey-800">{{ $cur['label'] ?? $placeholder }}</span>
    <span data-dd-chevron class="shrink-0 text-grey-500 transition-transform duration-200"><x-icon name="chevron-down" :size="24" /></span>
  </button>

  {{-- Daftar pilihan: awalnya tersembunyi (invisible + opacity-0) --}}
  <ul role="listbox" aria-labelledby="{{ $id }}" data-dd-panel
      class="no-scrollbar invisible absolute inset-x-0 top-full z-40 mt-1 max-h-[232px] -translate-y-1 overflow-y-auto rounded-xl border border-grey-200 bg-white p-1 opacity-0 shadow-[0_8px_24px_rgba(20,26,40,0.12)] transition duration-150 ease-out">
    @foreach($opts as $o)
      <li role="option" aria-selected="{{ $o['value'] === $value ? 'true' : 'false' }}">
        <button type="button" data-dd-option data-value="{{ $o['value'] }}" data-label="{{ $o['label'] }}"
                class="flex min-h-11 w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-[14px] leading-5 font-medium text-grey-800 hover:bg-grey-100">
          <span>{{ $o['label'] }}</span>
          {{-- Tanda centang hanya tampil di pilihan yang terpilih --}}
          <span data-dd-check @class(['shrink-0 text-primary-500', 'hidden' => $o['value'] !== $value])><x-icon name="check" :size="18" /></span>
        </button>
      </li>
    @endforeach
  </ul>
</div>

{{-- @once: script ini cukup dimuat sekali walaupun ada banyak dropdown di satu halaman --}}
@once
<script>
  (function () {
    // Buka atau tutup satu dropdown dengan menukar kelas Tailwind
    function setOpen(dd, open) {
      var panel = dd.querySelector('[data-dd-panel]');
      panel.classList.toggle('invisible', !open);
      panel.classList.toggle('opacity-0', !open);
      panel.classList.toggle('-translate-y-1', !open);
      panel.classList.toggle('opacity-100', open);
      panel.classList.toggle('translate-y-0', open);
      dd.querySelector('[data-dd-chevron]').classList.toggle('rotate-180', open);
      dd.querySelector('[data-dd-trigger]').setAttribute('aria-expanded', open ? 'true' : 'false');
      dd.querySelector('[data-dd-trigger]').classList.toggle('border-primary-500', open);
      dd.dataset.open = open ? '1' : '0';
    }

    // Satu pendengar klik untuk semua dropdown di halaman
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-dd-trigger]');
      var option = e.target.closest('[data-dd-option]');

      document.querySelectorAll('[data-dropdown]').forEach(function (dd) {
        if (trigger && dd.contains(trigger)) {
          // Klik tombol pembuka → buka/tutup dropdown ini
          setOpen(dd, dd.dataset.open !== '1');
        } else if (option && dd.contains(option)) {
          // Klik salah satu pilihan → simpan nilai, ganti teks, pindahkan centang
          var input = dd.querySelector('[data-dd-input]');
          input.value = option.dataset.value;
          dd.querySelector('[data-dd-label]').textContent = option.dataset.label;
          dd.querySelectorAll('[data-dd-option]').forEach(function (o) {
            o.querySelector('[data-dd-check]').classList.toggle('hidden', o !== option);
          });
          // Beri tahu kode lain bahwa nilai berubah
          input.dispatchEvent(new Event('change', { bubbles: true }));
          setOpen(dd, false);
        } else {
          // Klik di luar dropdown → tutup
          setOpen(dd, false);
        }
      });
    });

    // Tombol Escape menutup semua dropdown
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('[data-dropdown]').forEach(function (dd) { setOpen(dd, false); });
      }
    });
  })();
</script>
@endonce
