{{-- Dibuat oleh: Satya Azka Aqilla (5026241199) --}}
{{-- UC-01 Login MyITS --}}
{{-- Kartu putih di tengah layar untuk alur myITS SSO.
     Latar cukup gradasi biru. Gambar bg/myits.jpg tidak dipakai lagi karena di dalamnya
     sudah ada gambar kartu login, sehingga terlihat "kartu dobel" di belakang. --}}
<div class="flex min-h-full items-center justify-center px-8 py-10"
     style="background: linear-gradient(180deg, #0B3A6B 0%, #072747 100%);">
  <div class="w-full rounded-3xl bg-white p-6">
    <div class="flex items-center gap-2">
      <span class="text-[18px] leading-6 font-extrabold tracking-tight text-[#1E4E9B]">my<span class="text-[#1E4E9B]">ITS</span></span>
      <span class="text-[16px] leading-6 font-medium text-grey-800">Single Sign-On</span>
    </div>
    {{ $slot }}
  </div>
</div>