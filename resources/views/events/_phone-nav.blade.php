@php
    $items = [
        ['key' => 'home', 'label' => __('ui.home'), 'filled' => $active === 'home', 'd' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['key' => 'booked', 'label' => __('ui.booked_events'), 'filled' => $active === 'booked', 'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['key' => 'create', 'label' => __('ui.create'), 'filled' => $active === 'create', 'd' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
        ['key' => 'profile', 'label' => __('ui.profile'), 'filled' => $active === 'profile', 'd' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    ];
@endphp
<div class="hp-nav">
    @foreach($items as $item)
        <span class="{{ $item['filled'] ? 'is-on' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 {{ $item['filled'] ? 'text-white' : 'text-white/50' }}" fill="{{ $item['filled'] ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['d'] }}"/></svg>
            {{ $item['label'] }}
        </span>
    @endforeach
</div>
