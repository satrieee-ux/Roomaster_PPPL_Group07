{{-- Dibuat oleh: Muhammad Satrio Adjie (5026241065) --}}
{{-- Komponen bersama (dipakai lintas banyak UC) --}}

{{-- Tabs: variant line atau pill. Mendukung items dan tabs. --}}
@props([
    'items' => [],
    'tabs' => null,
    'active' => 0,
    'variant' => 'line',
])

@php
    // Samakan bentuk tab: bisa array ['label','href','icon'] atau string biasa (href '#').
    $tabItems = collect($tabs ?? $items)
        ->map(function ($item) {
            if (is_array($item)) {
                return [
                    'label' => $item['label'] ?? '',
                    'href' => $item['href'] ?? '#',
                    'icon' => $item['icon'] ?? null,
                ];
            }

            return [
                'label' => $item,
                'href' => '#',
                'icon' => null,
            ];
        })
        ->values();
@endphp

@if($variant === 'pill')
    <nav {{ $attributes->class([
        'no-scrollbar -my-1 flex items-center gap-2 overflow-x-auto px-6 py-1'
    ]) }}>
        @foreach($tabItems as $index => $item)
            @php
                $icon = $item['icon'];
                // Tab aktif: $active berupa angka = indeks tab; selain itu dicocokkan dengan label tab.
                $isActive = is_numeric($active) ? $index === (int) $active : $item['label'] === $active;
            @endphp

            <a href="{{ $item['href'] }}" @class([
                'inline-flex shrink-0 items-center whitespace-nowrap rounded-full text-[12px] leading-4 font-medium',
                'h-10 gap-2 pl-3 pr-4' => $icon,
                'h-8 px-4' => !$icon,
                'bg-primary-800 text-white shadow-[0_0_12px_rgba(59,123,160,0.5)]' => $isActive,
                'bg-grey-200 text-primary-500' => !$isActive,
            ])>
                @if($icon)
                    <x-icon :name="$icon" :size="24" />
                @endif

                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
@else
    <nav {{ $attributes->class([
        'flex border-b border-grey-300'
    ]) }}>
        @foreach($tabItems as $index => $item)
            @php
                // Tab aktif: $active berupa angka = indeks tab; selain itu dicocokkan dengan label tab.
                $isActive = is_numeric($active) ? $index === (int) $active : $item['label'] === $active;
            @endphp

            <a href="{{ $item['href'] }}" @class([
                'relative flex h-10 flex-1 items-center justify-center text-[14px] leading-5 font-semibold',
                'text-grey-900' => $isActive,
                'text-grey-400' => !$isActive,
            ])>
                {{ $item['label'] }}

                @if($isActive)
                    <span class="absolute inset-x-0 -bottom-px h-[3px] rounded-t-full bg-primary-800"></span>
                @endif
            </a>
        @endforeach
    </nav>
@endif
