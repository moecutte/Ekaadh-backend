@php
    $motionKey = $design['blade_key'] ?? '';
    $accent = $design['accent'] ?? '#c48b96';
@endphp
@if(in_array($motionKey, ['blush_petal', 'velvet_gold', 'sage_promise', 'starlit_vow', 'lantern_garden', 'oasis_gala', 'pearl_soiree'], true) && ! ($forPdf ?? false))
    @include('tickets.partials.invite-motion')
    <div class="absolute inset-0 pointer-events-none invite-motion" style="z-index: 5; overflow: hidden;" aria-hidden="true">
        @if($motionKey === 'blush_petal')
            @foreach([10, 22, 36, 50, 64, 78, 90] as $i => $left)
                <span class="inv-petal" style="left: {{ $left }}%; animation-duration: {{ 10 + ($i % 4) }}s; animation-delay: {{ $i * 0.8 }}s; color: {{ $accent }}; font-size: {{ 12 + ($i % 3) * 3 }}px;">❀</span>
            @endforeach
        @elseif($motionKey === 'velvet_gold' || $motionKey === 'oasis_gala' || $motionKey === 'pearl_soiree')
            <div class="absolute inset-0 inv-foil opacity-30"></div>
        @elseif($motionKey === 'starlit_vow')
            @foreach(range(0, 16) as $i)
                <span class="inv-star inv-twinkle" style="left: {{ 8 + ($i * 5.2) }}%; top: {{ 6 + (($i * 11) % 40) }}%; animation-delay: {{ $i * 0.16 }}s;"></span>
            @endforeach
        @elseif($motionKey === 'lantern_garden')
            @foreach([18, 38, 58, 78] as $i => $left)
                <span class="inv-lantern" style="left: {{ $left }}%; animation-duration: {{ 11 + $i }}s; animation-delay: {{ $i * 0.7 }}s;">
                    <span class="block w-3.5 h-5 rounded-sm" style="background: linear-gradient(180deg, #fde68a, #d97706); box-shadow: 0 0 12px #fbbf24aa;"></span>
                </span>
            @endforeach
        @elseif($motionKey === 'sage_promise')
            <div class="absolute left-1/2 top-[48%] -translate-x-1/2 -translate-y-1/2 opacity-15 inv-spin-slow">
                <div class="w-24 h-24 rounded-full" style="border: 1.5px solid {{ $accent }};"></div>
            </div>
        @endif
    </div>
@endif
