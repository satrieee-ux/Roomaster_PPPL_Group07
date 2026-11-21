
{{-- Banner status Roomaster sesuai ENUM DDL.
     Mendukung tampilan banner dan ikon lingkaran.
--}}

@props([
    'status' => 'pending',
    'updated' => null,
    'circle' => false,
    'isUpcoming' => false,
])

@php
    $map = [
        'draft' => [
            'Draft',
            'bg-primary-50 text-primary-500',
            'text-primary-500/70',
            'file',
        ],
        'pending' => [
            'Pending',
            'bg-warning-50 text-warning-500',
            'text-warning-300',
            'clock',
        ],
        'revisi' => [
            'Revised',
            'bg-warning-50 text-warning-500',
            'text-warning-300',
            'alert-circle',
        ],
        'disetujui' => [
            'Approved',
            'bg-success-50 text-success-500',
            'text-success-400',
            'check-circle',
        ],
        'ditolak' => [
            'Rejected',
            'bg-danger-50 text-danger-500',
            'text-danger-300',
            'x',
        ],
        'berlangsung' => [
            'Active',
            'bg-success-50 text-success-500',
            'text-success-400',
            'clock',
        ],
        'selesai' => [
            'Completed',
            'bg-primary-500 text-white',
            'text-white/80',
            'check-circle',
        ],
        'dibatalkan' => [
            'Cancelled',
            'bg-danger-50 text-danger-500',
            'text-danger-300',
            'x',
        ],
        'diproses' => [
            'In Progress',
            'bg-warning-50 text-warning-500',
            'text-warning-300',
            'clock',
        ],
        'upcoming' => [
            'Upcoming',
            'bg-secondary-500 text-white',
            'text-white/80',
            'door-open',
        ],
    ];

    $isUpcoming = filter_var(
        $isUpcoming,
        FILTER_VALIDATE_BOOLEAN
    );

    $displayStatus = (
        $status === 'disetujui' && $isUpcoming
    ) ? 'upcoming' : $status;

    [$label, $cls, $muted, $icon] = $map[$displayStatus]
        ?? [
            ucfirst(str_replace('_', ' ', $displayStatus)),
            'bg-primary-50 text-primary-500',
            'text-primary-500/70',
            'alert-circle',
        ];
@endphp

@if($circle)
    <span {{ $attributes->class([
        'inline-flex size-12 items-center justify-center rounded-full',
        $cls
    ]) }}>
        <x-icon :name="$icon" :size="24" />
    </span>
@else
    <div {{ $attributes->class([
        'flex items-center justify-between rounded-xl px-3 py-3',
        $cls
    ]) }}>
        <div class="flex items-center gap-2 text-[14px] leading-5 font-semibold">
            <x-icon :name="$icon" :size="20" />
            Status: {{ $label }}
        </div>

        @if($updated)
            <span class="text-[12px] leading-4 {{ $muted }}">
                Updated {{ $updated }}
            </span>
        @endif
    </div>
@endif
