{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-09 Booking langkah 2: melampirkan dokumen pendukung (tabel dokumen_peminjaman).
     Variabel yang diharapkan dari controller:
       $ruangan    = baris data_ruangan (dipakai: id, untuk URL)
       $peminjaman = baris peminjaman berstatus 'draft' milik user (dipakai: id)
       $dokumen    = dokumen yang sudah ada untuk booking ini, dikelompokkan per jenis:
                     $peminjaman->dokumen->keyBy('jenis_dokumen')
                     Contoh: $dokumen['ktm_ktp'] = baris dokumen_peminjaman (nama_berkas, ukuran_berkas, status),
                     atau tidak ada jika belum diunggah.
     Form mengirim (POST, multipart):
       id_peminjaman, file "ktm_ktp", file "surat_kegiatan" (nama input = nilai ENUM jenis_dokumen).
       Slot yang tidak diganti dikirim kosong → controller memakai dokumen lama.
       Satu jenis = satu dokumen per booking: file baru menggantikan baris dan file lama.

     + Satrio: pesan error validasi tampil di bawah slot masing-masing (x-form_error di doc_upload,
       kuncinya ktm_ktp dan surat_kegiatan), bukan satu blok di bawah. Form diberi novalidate. --}}
@extends('layouts.app')

@section('content')
<div class="flex min-h-full flex-col bg-grey-100">
  <x-header_topappbar title="Booking" endIcon="x" :endUrl="route('rooms.detail', $ruangan->id)" />

  {{-- TODO: ganti ke route('booking.dokumen.store', $ruangan->id) setelah route dibuat di routes/web.php --}}
  <form method="POST" enctype="multipart/form-data" class="flex flex-1 flex-col" novalidate
        action="{{ url('/booking/'.$ruangan->id.'/dokumen') }}">
    @csrf
    <input type="hidden" name="id_peminjaman" value="{{ $peminjaman->id }}">

    <main class="flex flex-1 flex-col px-6 pb-5 pt-5">
      <x-booking_stepper :step="2" />

      <h2 class="mt-8 text-[24px] leading-7 font-extrabold text-grey-800">Supporting Documents</h2>
      <p class="mt-0.5 text-[16px] leading-6 font-normal text-grey-500">
        Please upload any required identification, permits, or supporting letters for your reservation.
      </p>

      <h3 class="mt-7 text-[18px] leading-7 font-medium text-grey-800">Uploaded Files</h3>

      {{-- Dua slot dokumen sesuai ENUM dokumen_peminjaman.jenis_dokumen.
           Error validasi tiap slot tampil di bawah slotnya sendiri (lihat doc_upload). --}}
      <div class="mt-4 flex flex-col gap-[18px]">
        <x-doc_upload jenis_dokumen="ktm_ktp" :dokumen="$dokumen['ktm_ktp'] ?? null" />
        <x-doc_upload jenis_dokumen="surat_kegiatan" :dokumen="$dokumen['surat_kegiatan'] ?? null" />
      </div>
    </main>

    {{-- Continue mengirim form. Tombol otomatis aktif jika kedua slot terisi (diatur oleh doc_upload). --}}
    <x-component_bookdocbar :submit="true" />
  </form>
</div>
@endsection
