{{-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) --}}
{{-- Pesan error satu kolom, ditaruh tepat di bawah kolomnya.
     Contoh: <x-form_error name="judul" />. Wildcard juga bisa: name="bukti.*" --}}
@props(['name'])
@php $pesan = collect($errors->get($name))->flatten()->first(); @endphp
@if($pesan)
  <p {{ $attributes->class(['text-[12px] leading-4 font-medium text-danger-500']) }}>{{ $pesan }}</p>
@endif
