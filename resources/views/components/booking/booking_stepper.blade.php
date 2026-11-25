{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Mengisi Form Peminjaman; juga dipakai di UC-09, UC-11 --}}
{{-- Stepper 3 langkah booking: 1 Details, 2 Documents, 3 Confirmation.
     Contoh: <x-booking_stepper :step="2" />
     step = nomor langkah yang sedang aktif (1, 2, atau 3). --}}
@props(['step' => 1, 'labels' => ['Details', 'Documents', 'Confirmation']])

<div {{ $attributes->class(['relative flex justify-between']) }}>
  {{-- Dua garis penghubung antar-lingkaran (posisi tetap sesuai desain) --}}
  <span class="absolute left-[81px] top-5 h-px w-[50px] bg-grey-200"></span>
  <span class="absolute left-[211px] top-5 h-px w-[50px] bg-grey-200"></span>

  @foreach($labels as $i => $label)
    {{-- $i mulai dari 0, jadi nomor langkah = $i + 1 --}}
    @php $n = $i + 1; @endphp
    <div class="flex w-[82px] flex-col items-center gap-2 whitespace-nowrap">
      {{-- Lingkaran nomor: biru tua jika langkah aktif, abu-abu jika tidak --}}
      <span @class([
        'flex size-10 items-center justify-center rounded-full text-[16px] leading-6 font-semibold',
        'bg-primary-900 text-white' => $n === $step,
        'bg-grey-200 text-grey-600' => $n !== $step,
      ])>{{ $n }}</span>

      {{-- Teks label: tebal (aktif), sedang (sudah lewat), tipis (belum dikerjakan) --}}
      <span @class([
        'text-[14px] leading-5',
        'font-semibold text-grey-800' => $n === $step,
        'font-medium text-grey-600'   => $n < $step,
        'font-normal text-grey-500'   => $n > $step,
      ])>{{ $label }}</span>
    </div>
  @endforeach
</div>
