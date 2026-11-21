{{-- Slot unggah satu dokumen peminjaman: kotak "Upload File" ↔ kartu berkas (card_file).
     Contoh:
       <x-doc_upload jenis_dokumen="ktm_ktp" :dokumen="$ktm" />
       <x-doc_upload jenis_dokumen="surat_kegiatan" :dokumen="null" />

     jenis_dokumen = 'ktm_ktp' atau 'surat_kegiatan' (ENUM dokumen_peminjaman.jenis_dokumen).
                     Label di layar dan nama input file diambil dari sini.
     dokumen       = baris dokumen_peminjaman yang sudah ada (model atau array berisi
                     nama_berkas, ukuran_berkas, status), atau null jika belum diunggah.
     name          = opsional, nama input file. Default sama dengan jenis_dokumen.

     path_berkas tidak ditampilkan; nilainya dibuat controller saat menyimpan file.
     Saat semua slot di halaman terisi, tombol Continue di component_bookdocbar otomatis aktif. --}}
@props(['jenis_dokumen', 'dokumen' => null, 'name' => null])
@php
// Label di layar untuk tiap nilai ENUM jenis_dokumen
$labelJenis = [
    'ktm_ktp'        => 'KTM/KTP',
    'surat_kegiatan' => 'Surat Peminjaman',
];
$label = $labelJenis[$jenis_dokumen] ?? $jenis_dokumen;

// Nama input file yang dikirim ke server, contoh name="ktm_ktp"
$namaInput = $name ?? $jenis_dokumen;

// data_get() bisa membaca model Eloquent maupun array, jadi halaman bebas mengirim keduanya
$adaFile = $dokumen !== null;
$namaBerkas = data_get($dokumen, 'nama_berkas', '');
$ukuranBerkas = data_get($dokumen, 'ukuran_berkas');
$statusBerkas = data_get($dokumen, 'status', 'draft');
@endphp

<div {{ $attributes->class(['flex flex-col gap-2.5']) }} data-doc data-jenis="{{ $jenis_dokumen }}">
  <span class="text-[14px] leading-5 font-semibold text-grey-800">{{ $label }}</span>

  {{-- Kotak "Upload File": tampil jika belum ada file. Klik kotak = buka pemilih file. --}}
  <label data-doc-drop @class([
    'flex h-[46px] cursor-pointer items-center justify-center gap-2 rounded-xl border border-grey-200 bg-grey-50 text-[16px] leading-6 font-medium text-grey-500',
    'hidden' => $adaFile,
  ])>
    <x-icon name="upload" :size="20" /> Upload File
    <input type="file" name="{{ $namaInput }}" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" data-doc-input>
  </label>

  {{-- Kartu berkas: tampil jika sudah ada file --}}
  <x-card_file :nama_berkas="$namaBerkas" :ukuran_berkas="$ukuranBerkas" :status="$statusBerkas"
               :class="$adaFile ? '' : 'hidden'" data-doc-card />
</div>

{{-- @once: script ini cukup dimuat sekali walaupun ada beberapa slot dokumen --}}
@once
<script>
  (function () {
    // Ubah byte menjadi teks. Rumusnya sama dengan PHP di card_file.
    function teksUkuran(byte) {
      if (byte >= 1048576) return (byte / 1048576).toFixed(1) + ' MB';
      return Math.round(byte / 1024) + ' KB';
    }

    // Cek apakah semua slot sudah berisi file, lalu nyalakan/matikan tombol Continue
    function refresh() {
      var docs = document.querySelectorAll('[data-doc]');
      if (!docs.length) return;
      var semuaTerisi = true;
      docs.forEach(function (d) {
        if (d.querySelector('[data-doc-card]').classList.contains('hidden')) semuaTerisi = false;
      });
      document.querySelectorAll('[data-docbar-on]').forEach(function (b) { b.classList.toggle('hidden', !semuaTerisi); });
      document.querySelectorAll('[data-docbar-off]').forEach(function (b) { b.classList.toggle('hidden', semuaTerisi); });
    }

    // Saat file dipilih: tampilkan kartu berisi nama dan ukuran file
    document.addEventListener('change', function (e) {
      var inp = e.target.closest && e.target.closest('[data-doc-input]');
      if (!inp || !inp.files[0]) return;
      var d = inp.closest('[data-doc]');
      var f = inp.files[0];
      d.querySelector('[data-doc-name]').textContent = f.name;     // → nama_berkas
      d.querySelector('[data-doc-size]').textContent = teksUkuran(f.size); // f.size dalam byte → ukuran_berkas
      d.querySelector('[data-doc-status]').textContent = 'Upload complete'; // file baru = status draft
      d.querySelector('[data-doc-drop]').classList.add('hidden');
      d.querySelector('[data-doc-card]').classList.remove('hidden');
      refresh();
    });

    // Saat tombol hapus diklik: kosongkan input dan tampilkan lagi kotak "Upload File"
    document.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-doc-remove]');
      if (!b) return;
      var d = b.closest('[data-doc]');
      if (!d) return;
      d.querySelector('[data-doc-input]').value = '';
      d.querySelector('[data-doc-card]').classList.add('hidden');
      d.querySelector('[data-doc-drop]').classList.remove('hidden');
      refresh();
    });

    // Cek sekali setelah seluruh halaman selesai dimuat (misal semua dokumen sudah ada dari database).
    // Harus menunggu DOMContentLoaded karena script ini muncul sebelum slot lain dan tombol Continue.
    document.addEventListener('DOMContentLoaded', refresh);
  })();
</script>
@endonce
