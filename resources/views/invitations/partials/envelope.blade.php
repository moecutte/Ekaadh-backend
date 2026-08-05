{{-- Guest invitation envelope — matches the chosen invitation template language --}}
@php
    $id = $design['id'] ?? 'wedding';
    $dateLabel = $event?->event_date?->format('l, F j, Y');
    $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
    $ticketCount = $tickets->where('status', '!=', 'cancelled')->count();
@endphp
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,400&family=Great+Vibes&family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">

@if($id === 'celebration')
<article class="overflow-hidden shadow-xl mx-auto mb-6" style="max-width: 480px; border-radius: 24px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }};">
    <div class="px-6 py-6 text-center text-white" style="background: linear-gradient(135deg, {{ $design['header_from'] }}, {{ $design['header_to'] }});">
        <p class="text-[11px] font-bold tracking-[0.2em] uppercase opacity-90">{{ $design['badge'] }}</p>
        <p class="mt-2 text-2xl font-bold" style="font-family: 'Playfair Display', serif;">{{ $design['invite_line'] }}</p>
        <div class="mt-3 opacity-80">✦ ◆ ✦</div>
    </div>
    <div class="px-6 py-6 text-center" style="font-family: 'Source Sans 3', sans-serif;">
        <p class="text-sm mb-2" style="color: {{ $design['muted'] }};">{{ $design['request_line'] }}</p>
        <h1 class="text-xl font-bold mb-4" style="font-family: 'Playfair Display', serif;">{{ $event?->title }}</h1>
        <div class="rounded-2xl px-4 py-3 mb-5 inline-block" style="background: {{ $design['accent_soft'] }}; border: 1px dashed {{ $design['border'] }};">
            <p class="text-sm font-bold">{{ $dateLabel }}@if($timeLabel) · {{ $timeLabel }}@endif</p>
            <p class="text-sm">{{ $event?->venue }}</p>
        </div>
        <p class="text-[10px] uppercase tracking-wider font-bold" style="color: {{ $design['muted'] }};">Celebrating with</p>
        <p class="text-xl font-bold mt-1">{{ $invitation->guest_name }}</p>
        <p class="text-xs mt-1" style="color: {{ $design['muted'] }};">{{ $ticketCount }} {{ Str::plural('ticket', $ticketCount) }} on this invitation</p>
    </div>
</article>

@elseif($id === 'formal')
<article class="mx-auto mb-6 shadow-xl relative" style="max-width: 480px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }};">
    <div class="absolute inset-x-0 top-0 h-1" style="background: {{ $design['accent'] }};"></div>
    <div class="absolute inset-x-0 bottom-0 h-1" style="background: {{ $design['accent'] }};"></div>
    <div class="px-10 py-10 text-center">
        <p class="text-[11px] tracking-[0.3em] uppercase mb-4" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">{{ $design['invite_line'] }}</p>
        <div class="mx-auto mb-4 w-16 h-px" style="background: {{ $design['accent'] }};"></div>
        <p class="text-sm italic mb-3" style="font-family: 'Playfair Display', serif; color: {{ $design['muted'] }};">{{ $design['request_line'] }}</p>
        <h1 class="text-2xl mb-4" style="font-family: 'Playfair Display', serif; font-weight: 600;">{{ $event?->title }}</h1>
        <div class="mx-auto mb-4 w-16 h-px" style="background: {{ $design['border'] }};"></div>
        <p style="font-family: 'Cormorant Garamond', serif;">{{ $dateLabel }}</p>
        @if($timeLabel)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $timeLabel }}</p>@endif
        <p class="mt-2 font-semibold" style="font-family: 'Cormorant Garamond', serif;">{{ $event?->venue }}</p>
        <div class="mt-6 grid grid-cols-2 gap-4 text-left text-sm" style="font-family: 'Cormorant Garamond', serif;">
            <div>
                <p class="text-[10px] uppercase tracking-wider" style="color: {{ $design['muted'] }};">Guest</p>
                <p class="font-semibold">{{ $invitation->guest_name }}</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-wider" style="color: {{ $design['muted'] }};">Admission</p>
                <p class="font-semibold">{{ $ticketCount }} {{ Str::plural('ticket', $ticketCount) }}</p>
            </div>
        </div>
    </div>
</article>

@elseif($id === 'royal_gold')
<article class="mx-auto mb-6 shadow-2xl relative overflow-hidden" style="max-width: 480px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 3px double {{ $design['border'] }};">
    <div class="absolute inset-2 pointer-events-none" style="border: 1px solid {{ $design['accent'] }}55;"></div>
    <div class="relative px-8 py-10 text-center">
        <div class="mx-auto mb-4 w-12 h-12 rounded-full flex items-center justify-center text-xl" style="border: 2px solid {{ $design['accent'] }}; color: {{ $design['accent'] }};">❖</div>
        <p class="text-[10px] tracking-[0.4em] uppercase font-bold mb-2" style="color: {{ $design['muted'] }};">{{ $design['invite_line'] }}</p>
        <p class="mb-3" style="font-family: 'Great Vibes', cursive; font-size: 36px; color: {{ $design['accent'] }};">An Honour</p>
        <p class="text-sm italic mb-4" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">{{ $design['request_line'] }}</p>
        <h1 class="text-2xl font-bold mb-4" style="font-family: 'Cormorant Garamond', serif;">{{ $event?->title }}</h1>
        <div class="py-3 mb-4" style="background: linear-gradient(90deg, transparent, {{ $design['accent_soft'] }}, transparent);">
            <p class="font-semibold" style="font-family: 'Cormorant Garamond', serif;">{{ $dateLabel }}</p>
            @if($timeLabel)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $timeLabel }}</p>@endif
            <p class="text-sm mt-1 font-semibold">{{ $event?->venue }}</p>
        </div>
        <p class="text-[10px] uppercase tracking-widest mb-1" style="color: {{ $design['muted'] }};">Presented to</p>
        <p class="text-2xl" style="font-family: 'Great Vibes', cursive; color: {{ $design['accent'] }};">{{ $invitation->guest_name }}</p>
        <p class="text-xs mt-2" style="color: {{ $design['muted'] }};">{{ $ticketCount }} {{ Str::plural('seat', $ticketCount) }} reserved</p>
    </div>
</article>

@elseif($id === 'garden_romance')
<article class="mx-auto mb-6 shadow-xl relative overflow-hidden" style="max-width: 480px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border-radius: 20px; border: 1px solid {{ $design['border'] }};">
    <div class="absolute top-3 left-3 text-2xl opacity-40" style="color: {{ $design['accent'] }};">❀</div>
    <div class="absolute top-3 right-3 text-2xl opacity-40" style="color: {{ $design['accent'] }};">❀</div>
    <div class="absolute bottom-3 left-3 text-2xl opacity-40" style="color: {{ $design['accent'] }};">❀</div>
    <div class="absolute bottom-3 right-3 text-2xl opacity-40" style="color: {{ $design['accent'] }};">❀</div>
    <div class="relative px-8 py-10 text-center">
        <p class="text-[10px] tracking-[0.25em] uppercase mb-2" style="color: {{ $design['muted'] }};">{{ $design['invite_line'] }}</p>
        <p class="mb-2" style="font-family: 'Great Vibes', cursive; font-size: 40px; color: {{ $design['accent'] }};">Join Us</p>
        <p class="text-sm italic mb-4" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">{{ $design['request_line'] }}</p>
        <div class="mx-auto mb-4 max-w-[90%] rounded-full px-4 py-2 text-xs font-semibold" style="background: {{ $design['accent_soft'] }}; color: {{ $design['accent'] }};">{{ $design['badge'] }}</div>
        <h1 class="text-2xl font-bold mb-4" style="font-family: 'Cormorant Garamond', serif;">{{ $event?->title }}</h1>
        <p style="font-family: 'Cormorant Garamond', serif;">{{ $dateLabel }}@if($timeLabel) · {{ $timeLabel }}@endif</p>
        <p class="text-sm font-semibold mt-1">{{ $event?->venue }}</p>
        <p class="text-[10px] uppercase tracking-wider mt-5 mb-1" style="color: {{ $design['muted'] }};">Dear</p>
        <p class="text-2xl" style="font-family: 'Great Vibes', cursive;">{{ $invitation->guest_name }}</p>
        <p class="text-xs mt-2" style="color: {{ $design['muted'] }};">{{ $ticketCount }} {{ Str::plural('invitation', $ticketCount) }} enclosed</p>
    </div>
</article>

@elseif($id === 'midnight_gala')
<article class="mx-auto mb-6 shadow-2xl relative overflow-hidden" style="max-width: 480px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 18px;">
    <div class="absolute inset-0 opacity-30 pointer-events-none"
         style="background-image: radial-gradient(circle at 20% 20%, {{ $design['accent'] }}33 0 1px, transparent 1px), radial-gradient(circle at 80% 40%, {{ $design['accent'] }}22 0 1px, transparent 1px); background-size: 48px 48px;"></div>
    <div class="relative px-8 py-10 text-center">
        <p class="text-[10px] tracking-[0.35em] uppercase mb-3" style="color: {{ $design['muted'] }};">{{ $design['badge'] }}</p>
        <p class="text-sm tracking-wide mb-2 italic" style="font-family: 'Playfair Display', serif; color: {{ $design['accent'] }};">{{ $design['invite_line'] }}</p>
        <h1 class="text-2xl font-bold mb-3" style="font-family: 'Playfair Display', serif;">{{ $event?->title }}</h1>
        <p class="text-sm mb-5" style="color: {{ $design['muted'] }};">{{ $design['request_line'] }}</p>
        <div class="mx-auto mb-5 max-w-[85%] rounded-xl px-4 py-3" style="background: {{ $design['accent_soft'] }}; border: 1px solid {{ $design['border'] }};">
            <p class="font-semibold" style="font-family: 'Playfair Display', serif;">{{ $dateLabel }}</p>
            @if($timeLabel)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $timeLabel }}</p>@endif
            <p class="text-sm mt-1">{{ $event?->venue }}</p>
        </div>
        <p class="text-[10px] uppercase tracking-widest mb-1" style="color: {{ $design['muted'] }};">Guest</p>
        <p class="text-lg font-semibold" style="font-family: 'Playfair Display', serif;">{{ $invitation->guest_name }}</p>
        <p class="text-xs mt-2" style="color: {{ $design['muted'] }};">★ {{ $ticketCount }} {{ Str::plural('ticket', $ticketCount) }} ★</p>
    </div>
</article>

@else
{{-- Default / wedding simple invite (upper card — no QR) --}}
@php
    $values = $design['field_values'] ?? [];
    $couple1 = trim((string) ($values['couple_name_1'] ?? $event?->couple_name_1 ?? ''));
    $couple2 = trim((string) ($values['couple_name_2'] ?? $event?->couple_name_2 ?? ''));
    $coupleLine = ($couple1 !== '' && $couple2 !== '') ? ($couple1.' & '.$couple2) : null;
    $venueLine = trim((string) ($values['venue'] ?? $event?->venue ?? ''));
    $month = trim((string) ($values['date_month'] ?? ''));
    $day = trim((string) ($values['date_day'] ?? ''));
    $year = trim((string) ($values['date_year'] ?? ''));
    $timeFromFields = trim((string) ($values['date_time'] ?? ''));
    $prettyDate = ($month !== '' && $day !== '' && $year !== '')
        ? trim($month.' '.$day.', '.$year)
        : $dateLabel;
    $prettyTime = $timeFromFields !== '' ? $timeFromFields : $timeLabel;
    $headline = $coupleLine ?: ($event?->title ?? '');
@endphp
<article class="relative overflow-hidden shadow-2xl mx-auto mb-6" style="max-width: 480px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 8px;">
    <div class="absolute inset-3 pointer-events-none" style="border: 1px solid {{ $design['border'] }};"></div>
    <div class="absolute inset-4 pointer-events-none" style="border: 1px solid {{ $design['accent'] }}33;"></div>
    <div class="relative px-8 py-10 text-center">
        <p class="text-[10px] tracking-[0.35em] uppercase font-semibold mb-3" style="color: {{ $design['muted'] }};">{{ $design['invite_line'] ?: "You're Invited" }}</p>
        <p class="mb-4" style="font-family: 'Great Vibes', cursive; font-size: 36px; color: {{ $design['accent'] }}; line-height: 1.1;">You're Invited</p>
        <p class="text-sm italic mb-5" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">{{ $design['request_line'] }}</p>
        <div class="my-5 flex items-center justify-center gap-3">
            <span class="h-px w-10" style="background: {{ $design['border'] }};"></span>
            <span style="color: {{ $design['accent'] }};">{{ $design['ornament'] ?: '✦' }}</span>
            <span class="h-px w-10" style="background: {{ $design['border'] }};"></span>
        </div>
        <h1 class="text-2xl font-bold leading-tight mb-2" style="font-family: 'Cormorant Garamond', serif;">{{ $headline }}</h1>
        <div class="mt-5 space-y-1" style="font-family: 'Cormorant Garamond', serif;">
            <p class="text-base font-semibold">{{ $prettyDate }}</p>
            @if($prettyTime)<p class="text-sm" style="color: {{ $design['muted'] }};">at {{ $prettyTime }}</p>@endif
            @if($venueLine !== '')<p class="text-sm mt-2" style="color: {{ $design['muted'] }};">{{ $venueLine }}</p>@endif
        </div>
        <div class="mt-6 pt-5" style="border-top: 1px solid {{ $design['border'] }};">
            <p class="text-[10px] uppercase tracking-widest mb-1" style="color: {{ $design['muted'] }};">Guest of honour</p>
            <p class="text-lg font-semibold" style="font-family: 'Cormorant Garamond', serif;">{{ $invitation->guest_name }}</p>
            <p class="text-xs mt-1" style="color: {{ $design['muted'] }};">{{ $ticketCount }} {{ Str::plural('ticket', $ticketCount) }} enclosed below</p>
        </div>
    </div>
</article>
@endif
