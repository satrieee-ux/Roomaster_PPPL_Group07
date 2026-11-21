{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-01 Login MyITS --}}
@extends('layouts.app')

@section('content')
{{-- Login testing (tanpa MyITS): username + password dari tabel profil_pengguna --}}
<x-auth_page active="login">
  <form action="{{ url('/login') }}" method="POST" class="flex flex-col">
    @csrf
    <div class="flex flex-col gap-2">
      <x-form_field label="Username" name="username" :value="old('username')" placeholder="Masukkan username" />
      <x-form_field label="Password" name="password" type="password" placeholder="Masukkan password" />
    </div>

    <div class="mt-5 flex items-center justify-between text-[14px] leading-5">
      <label class="flex items-center gap-2 font-normal text-grey-500">
        <input type="checkbox" name="remember" value="1" class="size-4 rounded-sm border border-grey-500 accent-primary-800"> Remember me
      </label>
      <a href="#" class="font-semibold text-[#3B9BEB]">Forgot Password ?</a>
    </div>

    <x-button type="submit" variant="primary" size="medium" class="mt-6 w-full">Log In</x-button>

    <x-social_login myits="MyITS Account" />

    <p class="mt-6 text-center text-[14px] leading-5 font-normal text-grey-500">
      Don't have an account? <a href="{{ url('/signup') }}" class="font-semibold text-[#3B9BEB] underline">Sign Up</a>
    </p>
  </form>
</x-auth_page>
@endsection
