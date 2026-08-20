{{-- Mini invitation template preview for the create-form design picker --}}
@php
    $isPremium = ($d['category'] ?? '') === 'premium';
    $layout = $d['blade_key'] ?? $d['id'] ?? 'wedding';
@endphp
<div class="relative text-center overflow-hidden"
     style="background: {{ $d['card_bg'] }}; color: {{ $d['text'] }}; min-height: 148px;">
    @if($isPremium)
        <div class="absolute top-2 right-2 z-10 text-[9px] font-black px-1.5 py-0.5 rounded bg-amber-400 text-amber-950">PREMIUM</div>
    @endif

    @if($layout === 'celebration')
        <div class="px-3 pt-3 pb-2 text-white text-[10px] font-bold tracking-wide"
             style="background: linear-gradient(135deg, {{ $d['header_from'] }}, {{ $d['header_to'] }});">
            {{ $d['invite_line'] }}
        </div>
        <div class="px-3 py-3">
            <p class="text-[9px] italic mb-1" style="color: {{ $d['muted'] }};">{{ $d['request_line'] }}</p>
            <p class="text-[11px] font-extrabold leading-snug">Your Event Title</p>
            <p class="text-[9px] mt-2" style="color: {{ $d['accent'] }};">{{ $d['ornament'] }} Guest name {{ $d['ornament'] }}</p>
        </div>
    @elseif($layout === 'formal')
        <div class="px-4 py-4">
            <div class="mx-auto mb-2 w-8 h-px" style="background: {{ $d['accent'] }};"></div>
            <p class="text-[9px] tracking-[0.2em] uppercase mb-1" style="color: {{ $d['muted'] }};">{{ $d['invite_line'] }}</p>
            <p class="text-[10px] italic mb-2" style="color: {{ $d['muted'] }};">{{ $d['request_line'] }}</p>
            <p class="text-[12px] font-bold leading-snug">Your Event Title</p>
            <div class="mx-auto mt-2 w-8 h-px" style="background: {{ $d['border'] }};"></div>
        </div>
    @elseif($layout === 'royal_gold')
        <div class="m-2 px-3 py-3" style="border: 2px double {{ $d['border'] }};">
            <p class="text-lg leading-none mb-1" style="color: {{ $d['accent'] }};">{{ $d['ornament'] }}</p>
            <p class="text-[9px] tracking-[0.2em] uppercase mb-1" style="color: {{ $d['muted'] }};">{{ $d['invite_line'] }}</p>
            <p class="text-[12px] font-bold leading-snug">Your Event Title</p>
            <p class="text-[9px] mt-2 italic" style="color: {{ $d['accent'] }};">Presented to Guest</p>
        </div>
    @elseif($layout === 'garden_romance')
        <div class="px-4 py-4">
            <p class="text-sm leading-none mb-1 opacity-50" style="color: {{ $d['accent'] }};">❀ &nbsp; ❀</p>
            <p class="text-[10px] italic mb-1" style="color: {{ $d['accent'] }};">{{ $d['invite_line'] }}</p>
            <p class="text-[12px] font-bold leading-snug">Your Event Title</p>
            <p class="text-[9px] mt-2" style="color: {{ $d['muted'] }};">{{ $d['request_line'] }}</p>
        </div>
    @elseif($layout === 'midnight_gala')
        <div class="px-4 py-4">
            <p class="text-[9px] tracking-[0.25em] uppercase mb-1" style="color: {{ $d['muted'] }};">{{ $d['badge'] }}</p>
            <p class="text-[10px] italic mb-1" style="color: {{ $d['accent'] }};">{{ $d['invite_line'] }}</p>
            <p class="text-[12px] font-bold leading-snug">Your Event Title</p>
            <p class="text-[9px] mt-2" style="color: {{ $d['muted'] }};">★ Guest of honour ★</p>
        </div>
    @elseif($layout === 'blush_petal')
        <div class="px-4 py-4">
            <p class="text-sm leading-none mb-1 opacity-70" style="color: {{ $d['accent'] }};">❀</p>
            <p class="text-[10px] italic mb-1" style="color: {{ $d['accent'] }};">You're Invited</p>
            <p class="text-[12px] font-bold leading-snug">Amina & Hassan</p>
        </div>
    @elseif($layout === 'velvet_gold')
        <div class="m-2 px-3 py-3" style="border: 2px double {{ $d['border'] }};">
            <p class="text-lg leading-none mb-1" style="color: {{ $d['accent'] }};">❖</p>
            <p class="text-[9px] tracking-[0.2em] uppercase mb-1" style="color: {{ $d['muted'] }};">Royal Wedding</p>
            <p class="text-[12px] font-bold leading-snug">Amina & Hassan</p>
        </div>
    @elseif($layout === 'sage_promise')
        <div class="px-4 py-4">
            <p class="text-sm leading-none mb-1" style="color: {{ $d['accent'] }};">○</p>
            <p class="text-[10px] italic mb-1" style="color: {{ $d['accent'] }};">A Promise</p>
            <p class="text-[12px] font-bold leading-snug">Amina & Hassan</p>
        </div>
    @elseif($layout === 'starlit_vow')
        <div class="px-4 py-4">
            <p class="text-[9px] tracking-[0.25em] uppercase mb-1" style="color: {{ $d['muted'] }};">Engagement</p>
            <p class="text-[10px] italic mb-1" style="color: {{ $d['accent'] }};">Under the Stars</p>
            <p class="text-[12px] font-bold leading-snug">Amina & Hassan</p>
        </div>
    @elseif($layout === 'lantern_garden')
        <div class="px-3 pt-3 pb-2 text-amber-50 text-[10px] font-bold tracking-wide"
             style="background: linear-gradient(135deg, {{ $d['header_from'] }}, {{ $d['header_to'] }});">Ceremony</div>
        <div class="px-3 py-3">
            <p class="text-[12px] font-extrabold leading-snug">Join the Light</p>
        </div>
    @elseif($layout === 'oasis_gala')
        <div class="px-4 py-4">
            <p class="text-[9px] tracking-[0.25em] uppercase mb-1" style="color: {{ $d['accent'] }};">Evening Ceremony</p>
            <p class="text-[10px] italic mb-1" style="color: {{ $d['accent'] }};">An Oasis</p>
            <p class="text-[12px] font-bold leading-snug">Your Celebration</p>
        </div>
    @elseif($layout === 'pearl_soiree')
        <div class="px-4 py-4">
            <p class="text-[9px] tracking-[0.2em] uppercase mb-1" style="color: {{ $d['muted'] }};">Dinner Invitation</p>
            <p class="text-[10px] italic mb-1" style="color: {{ $d['accent'] }};">An Evening</p>
            <p class="text-[12px] font-bold leading-snug">Your Event Title</p>
        </div>
    @else
        {{-- wedding default --}}
        <div class="px-4 py-4">
            <p class="text-[9px] tracking-[0.2em] uppercase mb-1" style="color: {{ $d['muted'] }};">{{ $d['invite_line'] }}</p>
            <p class="text-[13px] mb-1" style="color: {{ $d['accent'] }}; font-family: Georgia, serif; font-style: italic;">You're Invited</p>
            <p class="text-[9px] italic mb-2" style="color: {{ $d['muted'] }};">{{ $d['request_line'] }}</p>
            <p class="text-[12px] font-bold leading-snug">Your Event Title</p>
            <p class="text-[9px] mt-2" style="color: {{ $d['accent'] }};">{{ $d['ornament'] }}</p>
        </div>
    @endif
</div>
<p class="px-3 py-2.5 text-left border-t" style="background: {{ $d['card_bg'] }}; border-color: {{ $d['border'] }};">
    <span class="block text-xs font-extrabold" style="color: {{ $d['text'] }};">{{ $d['name'] }}</span>
    <span class="block text-[11px] mt-0.5" style="color: {{ $d['muted'] }};">{{ $d['description'] }}</span>
</p>
