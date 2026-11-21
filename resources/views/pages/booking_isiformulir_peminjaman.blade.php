{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Booking langkah 1: formulir peminjaman, tanggal belum dipilih (state "empty").
     Variabel yang diharapkan dari controller:
       $ruangan    = baris data_ruangan (dipakai: id, kode_ruangan)
       $peminjaman = baris peminjaman berstatus 'draft' milik user, atau null jika belum ada.
                     Dipakai untuk mengisi ulang: nama_kegiatan, tujuan, departemen_penyelenggara.
     Form mengirim: nama_kegiatan, tujuan, departemen_penyelenggara, tanggal_pakai, waktu_mulai, waktu_selesai. --}}
@extends('layouts.app')

@section('content')
<x-booking_form :id_ruangan="$ruangan->id" :kode_ruangan="$ruangan->kode_ruangan" state="empty"
                :nama_kegiatan="$peminjaman?->nama_kegiatan"
                :tujuan="$peminjaman?->tujuan"
                :departemen_penyelenggara="$peminjaman?->departemen_penyelenggara" />
@endsection
