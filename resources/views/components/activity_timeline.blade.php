{{-- Dibuat oleh: Thalia Harnum Fathina (5026241201) --}}
{{-- UC-06 Melihat Riwayat Aktivitas dan Booking Summary; juga dipakai di UC-11, UC-19, UC-20 --}}

{{-- Timeline aktivitas untuk booking dan laporan.
     Booking: menerima data riwayat_peminjaman.
     Laporan: tetap mendukung items statis.
--}}

@props([
    'items' => [],
    'riwayat' => null,
])

@php
    // Dua sumber data: $riwayat (baris riwayat_peminjaman dari database) atau $items (array jadi dari halaman).
    $timelineItems = [];

    if ($riwayat !== null) {
        // Urutkan dari yang paling lama. Baris terakhir = tahap yang sedang berjalan (ikon jam); sisanya selesai (ikon centang).
        $records = collect($riwayat)
            ->sortBy('waktu')
            ->values();

        $total = $records->count();

        foreach ($records as $index => $record) {
            $tahapan = data_get($record, 'tahapan', '');
            $keterangan = data_get($record, 'keterangan');
            $waktu = data_get($record, 'waktu');

            // Nilai tahapan (ENUM status peminjaman) -> judul yang tampil di layar.
            $labels = [
                'draft' => 'Draft',
                'pending' => 'Pending Review',
                'revisi' => 'Revision Required',
                'disetujui' => 'Approved',
                'ditolak' => 'Rejected',
                'berlangsung' => 'Check-In',
                'selesai' => 'Completed',
                'dibatalkan' => 'Cancelled',
            ];

            $timelineItems[] = [
                'icon' => $index === $total - 1
                    ? 'clock'
                    : 'check',
                'state' => $index === $total - 1
                    ? 'current'
                    : 'done',
                'title' => $labels[$tahapan] ?? ucfirst(
                    str_replace('_', ' ', $tahapan)
                ),
                // Deskripsi memakai keterangan; bila kosong, tampilkan waktu kejadian.
                'desc' => $keterangan
                    ?: ($waktu
                        ? \Illuminate\Support\Carbon::parse($waktu)
                            ->format('d M Y, H:i')
                        : ''),
            ];
        }
    } else {
        // Tanpa $riwayat: pakai array $items apa adanya.
        $timelineItems = $items;
    }
@endphp

<div {{ $attributes->class(['flex flex-col']) }}>
    @foreach($timelineItems as $a)
        @php
            $s = $a['state'] ?? 'todo';
            $icon = $a['icon'] ?? 'clock';
        @endphp

        <div @class([
            'relative flex items-center gap-3',
            'pb-8' => !$loop->last,
        ])>
            <span @class([
                'z-10 flex size-12 shrink-0 items-center justify-center rounded-full',
                'bg-primary-900 text-white' => $s === 'done',
                'border border-grey-600 bg-white text-grey-800' => $s === 'current',
                'bg-grey-200 text-grey-400' => $s === 'todo',
            ])>
                <x-icon :name="$icon" :size="20" />
            </span>

            @unless($loop->last)
                <span class="absolute left-6 top-12 h-8 w-0.5 -translate-x-1/2 bg-grey-300"></span>
            @endunless

            <div class="flex min-w-0 flex-col">
                <span @class([
                    'text-[14px] leading-5 font-semibold',
                    'text-grey-800' => $s !== 'todo',
                    'text-grey-500' => $s === 'todo',
                ])>
                    {{ $a['title'] }}
                </span>

                <span class="text-[12px] leading-4 font-medium text-grey-500">
                    {{ $a['desc'] }}
                </span>
            </div>
        </div>
    @endforeach
</div>
