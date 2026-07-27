@php
    $compact = $compact ?? false;
    $event = $ticket->event;
    $dateLabel = $event?->event_date?->format('l, F j, Y');
    $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
@endphp
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Playfair+Display:ital,wght@0,600;1,500&display=swap" rel="stylesheet">
<article class="relative mx-auto shadow-xl"
         style="max-width: 420px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }};">
    <div class="absolute inset-x-0 top-0 h-1" style="background: {{ $design['accent'] }};"></div>
    <div class="absolute inset-x-0 bottom-0 h-1" style="background: {{ $design['accent'] }};"></div>

    <div class="px-9 {{ $compact ? 'py-7' : 'py-11' }} text-center">
        <p class="text-[11px] tracking-[0.3em] uppercase mb-6" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">
            {{ $design['invite_line'] }}
        </p>

        <div class="mx-auto mb-6 w-16 h-px" style="background: {{ $design['accent'] }};"></div>

        <p class="text-sm italic mb-3" style="font-family: 'Playfair Display', serif; color: {{ $design['muted'] }};">
            {{ $design['request_line'] }}
        </p>

        <h1 class="leading-snug mb-6" style="font-family: 'Playfair Display', serif; font-weight: 600; font-size: {{ $compact ? '22px' : '26px' }};">
            {{ $event?->title }}
        </h1>

        <div class="mx-auto mb-6 w-16 h-px" style="background: {{ $design['border'] }};"></div>

        <div style="font-family: 'Cormorant Garamond', serif;" class="space-y-1 text-[15px]">
            <p>{{ $dateLabel }}</p>
            @if($timeLabel)<p style="color: {{ $design['muted'] }};">{{ $timeLabel }}</p>@endif
            <p class="mt-3 font-semibold">{{ $event?->venue }}</p>
            @if($event?->city)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $event->city }}</p>@endif
        </div>

        <div class="mt-8 grid grid-cols-2 gap-4 text-left text-sm" style="font-family: 'Cormorant Garamond', serif;">
            <div>
                <p class="text-[10px] uppercase tracking-wider" style="color: {{ $design['muted'] }};">Guest</p>
                <p class="font-semibold">{{ $ticket->holder_name }}</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-wider" style="color: {{ $design['muted'] }};">Admission</p>
                <p class="font-semibold">{{ $ticket->ticket_type_name }}</p>
            </div>
        </div>

        <div class="mt-8 flex flex-col items-center">
            <img src="{{ $qrImage }}" alt="QR" class="bg-white p-2 {{ $compact ? 'w-28 h-28' : 'w-36 h-36' }} object-contain"
                 style="border: 1px solid {{ $design['border'] }};">
            <p class="font-mono text-[11px] font-bold tracking-[0.2em] mt-3" style="color: {{ $design['accent'] }};">{{ $ticket->ticket_code }}</p>
            <p class="text-[11px] mt-2" style="color: {{ $design['muted'] }}; font-family: 'Cormorant Garamond', serif;">{{ $design['footer_line'] }}</p>
        </div>
    </div>
</article>
