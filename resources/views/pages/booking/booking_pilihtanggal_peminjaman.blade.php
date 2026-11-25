{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-08 Booking langkah 1: formulir peminjaman, tanggal sudah dipilih (state "date").
     Tombol Check Availability aktif dan membuka popup pilih jam.
     Variabel yang diharapkan dari controller:
       $ruangan       = baris data_ruangan (dipakai: id, kode_ruangan)
       $peminjaman    = baris peminjaman berstatus 'draft' milik user, atau null jika belum ada.
                        Dipakai untuk mengisi ulang: nama_kegiatan, tujuan, departemen_penyelenggara.
       $tanggal_pakai = tanggal terpilih, format Y-m-d (contoh "2026-05-20")
       $jamTerpakai   = array jam (8-19) yang sudah terpakai pada ruangan dan tanggal itu, contoh [9, 13, 14].
                        Sumbernya peminjaman dengan id_ruangan dan tanggal_pakai yang sama,
                        status 'disetujui' atau 'berlangsung' saja ('draft' dan 'pending' tidak menahan slot).
                        Satu peminjaman 13:00-15:00 menjadi jam [13, 14].
     Form mengirim: nama_kegiatan, tujuan, departemen_penyelenggara, tanggal_pakai, waktu_mulai, waktu_selesai. --}}
@extends('layouts.app')

@section('content')
<x-booking_form :id_ruangan="$ruangan->id" :kode_ruangan="$ruangan->kode_ruangan" state="date"
                :tanggal_pakai="$tanggal_pakai"
                :jam_terpakai="$jamTerpakai"
                :nama_kegiatan="$peminjaman?->nama_kegiatan"
                :tujuan="$peminjaman?->tujuan"
                :departemen_penyelenggara="$peminjaman?->departemen_penyelenggara" />
@endsection
