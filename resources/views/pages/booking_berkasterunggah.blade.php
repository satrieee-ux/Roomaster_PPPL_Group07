{{-- Dibuat oleh: Muhammad Trinanda Thoriq (5026241124) --}}
{{-- UC-10 Booking langkah 2: memeriksa kembali dokumen yang sudah diunggah (tabel dokumen_peminjaman).
     Halaman ini SAMA dengan halaman UC-09 (booking_melampirkan_dokumenpendukung), ditambah aksi hapus sungguhan:
     klik ikon hapus pada dokumen yang sudah tersimpan di database → baris dokumen_peminjaman dan filenya dihapus,
     lalu halaman dimuat ulang (redirect dari controller) sehingga tampilan selalu sesuai database.
     Variabel yang diharapkan dari controller (sama dengan UC-09):
       $ruangan    = baris data_ruangan (dipakai: id, untuk URL)
       $peminjaman = baris peminjaman berstatus 'draft' milik user (dipakai: id)
       $dokumen    = dokumen yang sudah ada untuk booking ini, dikelompokkan per jenis:
                     $peminjaman->dokumen->keyBy('jenis_dokumen')
     Form hapus mengirim (DELETE): id_peminjaman, jenis_dokumen ('ktm_ktp' atau 'surat_kegiatan'). --}}
@extends('pages.booking_melampirkan_dokumenpendukung')

@section('content')
  {{-- Seluruh isi halaman UC-09 (header, stepper, slot dokumen, tombol Continue) --}}
  @parent

  {{-- Form hapus tersembunyi. Terpisah dari form unggah karena form tidak boleh bersarang.--}}
  <form id="form-hapus-dokumen" method="POST" action="{{ route('booking.dokumen.destroy', $ruangan->id) }}" class="hidden">
    @csrf
    @method('DELETE')
    <input type="hidden" name="id_peminjaman" value="{{ $peminjaman->id }}">
    <input type="hidden" name="jenis_dokumen" value="">
  </form>

  <script>
    (function () {
      var form = document.getElementById('form-hapus-dokumen');
      if (!form) return;

      // Jenis dokumen yang sudah tersimpan di database, contoh ["ktm_ktp", "surat_kegiatan"]
      var tersimpan = @json(collect($dokumen ?? [])->keys()->values());

      // Klik ikon hapus (atribut data-doc-remove dari file_card)
      document.addEventListener('click', function (e) {
        var tombol = e.target.closest && e.target.closest('[data-doc-remove]');
        if (!tombol) return;
        var slot = tombol.closest('[data-doc]');
        if (!slot) return;

        // File yang baru dipilih (belum diunggah): tidak ada di database, cukup ditangani JS doc_upload
        var jenis = slot.dataset.jenis;
        if (tersimpan.indexOf(jenis) === -1) return;

        // Dokumen tersimpan di database: kirim form hapus
        form.querySelector('input[name=jenis_dokumen]').value = jenis;
        form.submit();
      });
    })();
  </script>
@endsection
