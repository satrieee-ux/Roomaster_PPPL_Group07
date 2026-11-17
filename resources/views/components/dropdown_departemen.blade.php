{{-- Dropdown pilih departemen penyelenggara kegiatan.
     Contoh: <x-dropdown_departemen :value="$peminjaman->departemen_penyelenggara" />
     Nilai yang dipilih dikirim dengan nama input "departemen_penyelenggara"
     dan disimpan apa adanya di kolom peminjaman.departemen_penyelenggara (VARCHAR 100).
     Daftar departemen sengaja statis (tidak ada tabel departemen di database). --}}
@props(['name' => 'departemen_penyelenggara', 'value' => null, 'placeholder' => 'Select'])
@php
// Semua nama di bawah ini kurang dari 100 karakter, sesuai batas kolom VARCHAR(100)
$departments = [
  'Departemen Sistem Informasi',
  'Departemen Teknik Informatika',
  'Departemen Teknik Elektro',
  'Departemen Teknik Mesin',
  'Departemen Teknik Sipil',
  'Departemen Teknik Industri',
  'Departemen Teknik Kimia',
  'Departemen Arsitektur',
  'Departemen Matematika',
  'Departemen Perencanaan Wilayah dan Kota',
];
@endphp

{{-- Memakai komponen dropdown umum; atribut tambahan (misal class) diteruskan --}}
<x-dropdown :name="$name" :options="$departments" :value="$value" :placeholder="$placeholder" {{ $attributes }} />
