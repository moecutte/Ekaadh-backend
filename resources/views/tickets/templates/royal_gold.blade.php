@php
    $compact = $compact ?? false;
    $event = $ticket->event;
    $dateLabel = $event?->event_date?->format('l, F j, Y');
    $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
@endphp
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=Great+Vibes&display=swap" rel="stylesheet">
<article class="relative mx-auto shadow-2xl overflow-hidden"
         style="max-width: 420px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 3px double {{ $design['border'] }}; border-radius: 4px;">
    <div class="absolute inset-2 pointer-events-none" style="border: 1px solid {{ $design['accent'] }}55;"></div>

    <div class="relative px-7 {{ $compact ? 'py-7' : 'py-10' }} text-center">
        <div class="mx-auto mb-4 w-12 h-12 rounded-full flex items-center justify-center text-xl"
             style="border: 2px solid {{ $design['accent'] }}; color: {{ $design['accent'] }};">❖</div>

        <p class="text-[10px] tracking-[0.4em] uppercase font-bold mb-2" style="color: {{ $design['muted'] }};">
            {{ $design['invite_line'] }}
        </p>
        <p class="mb-3" style="font-family: 'Great Vibes', cursive; font-size: {{ $compact ? '30px' : '38px' }}; color: {{ $design['accent'] }};">
            An Honour
        </p>
        <p class="text-sm italic mb-5" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">
            {{ $design['request_line'] }}
        </p>

        <h1 class="leading-tight mb-5" style="font-family: 'Cormorant Garamond', serif; font-weight: 700; font-size: {{ $compact ? '22px' : '28px' }};">
            {{ $event?->title }}
        </h1>

        <div class="py-4 px-3 mb-5" style="background: linear-gradient(90deg, transparent, {{ $design['accent_soft'] }}, transparent);">
            <p class="text-base font-semibold" style="font-family: 'Cormorant Garamond', serif;">{{ $dateLabel }}</p>
            @if($timeLabel)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $timeLabel }}</p>@endif
            <p class="text-sm mt-2 font-semibold">{{ $event?->venue }}</p>
        </div>

        <p class="text-[10px] uppercase tracking-widest mb-1" style="color: {{ $design['muted'] }};">Presented to</p>
        <p class="text-xl mb-1" style="font-family: 'Great Vibes', cursive; color: {{ $design['accent'] }};">{{ $ticket->holder_name }}</p>
        <p class="text-xs mb-6" style="color: {{ $design['muted'] }};">{{ $ticket->ticket_type_name }}</p>

        <div class="flex flex-col items-center">
            <div class="p-2 bg-white" style="border: 2px solid {{ $design['accent'] }};">
                <img src="{{ $qrImage }}" alt="QR" class="{{ $compact ? 'w-28 h-28' : 'w-36 h-36' }} object-contain">
            </div>
            <p class="font-mono text-[11px] font-bold tracking-[0.25em] mt-3" style="color: {{ $design['accent'] }};">{{ $ticket->ticket_code }}</p>
            <p class="text-[11px] mt-2 italic" style="color: {{ $design['muted'] }};">{{ $design['footer_line'] }}</p>
        </div>
    </div>
</article>
