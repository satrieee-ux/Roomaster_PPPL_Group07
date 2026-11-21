@extends('layouts.app')

@section('content')
{{-- Sign Up: Nama, NRP, Email ITS -> mahasiswa_its. Username, Password -> profil_pengguna.
     Field Birth date dan Phone dihapus karena tidak ada kolomnya di DDL. --}}
<x-auth_page active="signup" padBottom="pb-14">
  <form action="{{ url('/signup') }}" method="POST" class="flex flex-col">
    @csrf
    <div class="flex flex-col gap-4">
      <x-form_field label="Nama Lengkap" name="nama" :value="old('nama')" placeholder="Nama sesuai KTM" />
      <x-form_field label="NRP" name="nrp" :value="old('nrp')" placeholder="Masukkan NRP" />
      <x-form_field label="Email ITS" name="email_its" type="email" :value="old('email_its')" placeholder="nrp@student.its.ac.id" />
      <x-form_field label="Username" name="username" :value="old('username')" placeholder="Untuk login" />
      <x-form_field label="Set Password" name="password" type="password" placeholder="Minimal 6 karakter" />
    </div>

    <x-button type="submit" variant="primary" size="medium" class="mt-6 w-full">Sign Up</x-button>

    <x-social_login myits="Masuk dengan MyITS" />
  </form>
</x-auth_page>
@endsection
