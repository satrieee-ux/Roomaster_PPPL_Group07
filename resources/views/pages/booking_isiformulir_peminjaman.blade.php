{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Booking langkah 1: formulir peminjaman, tanggal belum dipilih (state "empty").
     Variabel yang diharapkan dari controller:
       $ruangan    = baris data_ruangan (dipakai: id, kode_ruangan)
       $peminjaman = baris peminjaman berstatus 'draft' milik user, atau null jika belum ada.
                     Dipakai untuk mengisi ulang: nama_kegiatan, tujuan, departemen_penyelenggara.
       $draftAda   = true jika user punya draft untuk ruangan ini tetapi belum memilih lanjut → popup "Draft Found".
       $tanggal_pakai, $waktu_mulai, $waktu_selesai, $jamTerpakai = isian draft (hanya saat ?lanjut=1).
     Form mengirim: nama_kegiatan, tujuan, departemen_penyelenggara, tanggal_pakai, waktu_mulai, waktu_selesai. --}}
@extends('layouts.app')

@section('content')
<x-booking_form :id_ruangan="$ruangan->id" :kode_ruangan="$ruangan->kode_ruangan" state="empty"
                :tanggal_pakai="$tanggal_pakai ?? null"
                :waktu_mulai="$waktu_mulai ?? null"
                :waktu_selesai="$waktu_selesai ?? null"
                :jam_terpakai="$jamTerpakai ?? []"
                :nama_kegiatan="$peminjaman?->nama_kegiatan"
                :tujuan="$peminjaman?->tujuan"
                :departemen_penyelenggara="$peminjaman?->departemen_penyelenggara" />

@if($draftAda ?? false)
  <x-popup_booking_draft :open="true"
                         :lanjutUrl="route('booking.form', ['id' => $ruangan->id, 'lanjut' => 1])"
                         :timpaUrl="route('booking.timpa', $ruangan->id)" />
@endif
@endsection
