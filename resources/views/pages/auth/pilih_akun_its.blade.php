{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-01 Login MyITS --}}
@extends('layouts.app')

@section('content')
{{-- Simulasi MyITS: halaman Sign In Options (AkunController@redirectToMyITS).
     Tidak menampilkan akun/email apa pun; pengguna memilih jenis akun lalu mengisi email sendiri.
     Akun Tendik (TU/Sarpras) belum ada tabelnya di DDL, jadi hanya akun Student. --}}
<x-myits_card>
  <h2 class="mt-4 text-[18px] leading-7 font-semibold text-grey-800">Sign In Options</h2>

  <div class="mt-3 flex flex-col gap-2">
    <a href="{{ url('/auth/myits/email') }}"
       class="flex items-center gap-3 rounded-xl border border-grey-100 bg-white px-3 py-2.5 shadow-[0_1px_2px_rgba(20,26,40,0.06)]">
      <x-icon name="user" :size="20" class="shrink-0 text-grey-500" />
      <span class="flex min-w-0 flex-col">
        <span class="truncate text-[14px] leading-5 font-medium text-grey-800">Student Account</span>
        <span class="text-[12px] leading-4 font-normal text-grey-500">Masuk dengan email ITS</span>
      </span>
    </a>
  </div>

  <div class="mt-3 border-t border-grey-100 pt-3">
    <x-button :href="url('/login')" variant="soft" size="medium" class="w-full">Back</x-button>
  </div>
</x-myits_card>
@endsection
