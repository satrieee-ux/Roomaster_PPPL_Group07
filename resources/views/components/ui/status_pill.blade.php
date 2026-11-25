{{-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) --}}
{{-- Komponen bersama (dipakai lintas banyak UC) --}}

{{-- Status pill yang mengikuti ENUM peminjaman dan laporan pada DDL Roomaster. --}}
@props([
    'status' => 'pending',
    'isUpcoming' => false,
])

@php
    // status (ENUM peminjaman dan laporan) -> [label, warna pill, ikon].
    $map = [
        'draft' => [
            'Draft',
            'bg-primary-50 text-primary-500',
            'file',
        ],
        'pending' => [
            'Pending',
            'bg-warning-50 text-warning-500',
            'clock',
        ],
        'revisi' => [
            'Revised',
            'bg-warning-50 text-warning-500',
            'alert-circle',
        ],
        'disetujui' => [
            'Approved',
            'bg-success-50 text-success-500',
            'check-circle',
        ],
        'ditolak' => [
            'Rejected',
            'bg-danger-50 text-danger-500',
            'x',
        ],
        'berlangsung' => [
            'Active',
            'bg-success-50 text-success-500',
            'clock',
        ],
        'selesai' => [
            'Completed',
            'bg-primary-500 text-white',
            'check-circle',
        ],
        'dibatalkan' => [
            'Cancelled',
            'bg-danger-50 text-danger-500',
            'x',
        ],
        'diproses' => [
            'In Progress',
            'bg-warning-50 text-warning-500',
            'clock',
        ],
        'upcoming' => [
            'Upcoming',
            'bg-secondary-500 text-white',
            'door-open',
        ],
    ];

    // Booking 'disetujui' yang jadwalnya belum lewat ditampilkan sebagai pill 'Upcoming'.
    $displayStatus = ($status === 'disetujui' && $isUpcoming)
        ? 'upcoming'
        : $status;

    // Status yang tidak dikenal: tampilkan nama status apa adanya dengan gaya netral.
    [$label, $cls, $icon] = $map[$displayStatus]
        ?? [$displayStatus, 'bg-primary-50 text-primary-500', 'alert-circle'];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 whitespace-nowrap rounded-[20px] px-2 py-1 text-[10px] leading-[14px] font-medium',
    $cls
]) }}>
    <x-icon :name="$icon" :size="12" />
    {{ $slot->isEmpty() ? $label : $slot }}
</span>
