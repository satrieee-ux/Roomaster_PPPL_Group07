{{-- Kartu berkas terunggah, menampilkan satu baris tabel dokumen_peminjaman.
     Contoh: <x-file_card :nama_berkas="$dok->nama_berkas" :ukuran_berkas="$dok->ukuran_berkas" :status="$dok->status" />
     nama_berkas   = nama asli file (VARCHAR 255)
     ukuran_berkas = ukuran file dalam BYTE (INT), ditampilkan seperti "1.2 MB"
     status        = 'draft' | 'diterima' | 'revisi' | 'ditolak'
     removable     = false untuk menyembunyikan tombol hapus.
     Catatan: tombol hapus hanya ditangani JS di doc_upload (tampilan). Menghapus baris
     dan file di storage adalah tugas controller. --}}
@props(['nama_berkas' => '', 'ukuran_berkas' => null, 'status' => 'draft', 'removable' => true])
@php
// Ubah byte menjadi teks yang mudah dibaca.
// 1 MB = 1.048.576 byte, 1 KB = 1.024 byte.
// Rumus yang sama dipakai di JavaScript doc_upload supaya hasilnya selalu cocok.
$teksUkuran = '';
if ($ukuran_berkas !== null && $ukuran_berkas !== '') {
    if ($ukuran_berkas >= 1048576) {
        $teksUkuran = number_format($ukuran_berkas / 1048576, 1, '.', '').' MB'; // contoh "1.2 MB"
    } else {
        $teksUkuran = round($ukuran_berkas / 1024).' KB';                       // contoh "350 KB"
    }
}

// Teks status dokumen (nilai ENUM dokumen_peminjaman.status → label di layar)
$labelStatus = [
    'draft'    => 'Upload complete',
    'diterima' => 'Accepted',
    'revisi'   => 'Needs revision',
    'ditolak'  => 'Rejected',
];
$teksStatus = $labelStatus[$status] ?? 'Upload complete';
@endphp

<div {{ $attributes->class(['flex items-center gap-4 rounded-xl border border-grey-300 bg-grey-50 p-4']) }}>
  {{-- Ikon berkas --}}
  <span class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-grey-300 bg-grey-100 text-grey-600">
    <x-icon name="file-text" :size="20" />
  </span>

  {{-- Nama berkas, ukuran, dan status. Atribut data-doc-* diisi ulang oleh JS doc_upload saat memilih file baru. --}}
  <div class="flex min-w-0 flex-1 flex-col">
    <span data-doc-name class="truncate text-[14px] leading-5 font-medium text-grey-800">{{ $nama_berkas }}</span>
    <span class="text-[12px] leading-4 font-medium text-grey-500">
      <span data-doc-size>{{ $teksUkuran }}</span> • <span data-doc-status>{{ $teksStatus }}</span>
    </span>
  </div>

  {{-- Tombol hapus --}}
  @if($removable)
    <button type="button" data-doc-remove aria-label="Hapus berkas" class="shrink-0 text-grey-800">
      <x-icon name="trash-2" :size="20" />
    </button>
  @endif
</div>
