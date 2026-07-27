@php
    $compact = $compact ?? false;
    $event = $ticket->event;
    $dateLabel = $event?->event_date?->format('F j, Y');
    $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
@endphp
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,400&family=Great+Vibes&display=swap" rel="stylesheet">
<article class="relative mx-auto shadow-xl overflow-hidden"
         style="max-width: 420px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border-radius: 20px; border: 1px solid {{ $design['border'] }};">
    {{-- Floral corner accents (CSS) --}}
    <div class="absolute top-3 left-3 text-2xl opacity-40 pointer-events-none" style="color: {{ $design['accent'] }};">❀</div>
    <div class="absolute top-3 right-3 text-2xl opacity-40 pointer-events-none" style="color: {{ $design['accent'] }};">❀</div>
    <div class="absolute bottom-3 left-3 text-2xl opacity-40 pointer-events-none" style="color: {{ $design['accent'] }};">❀</div>
    <div class="absolute bottom-3 right-3 text-2xl opacity-40 pointer-events-none" style="color: {{ $design['accent'] }};">❀</div>

    <div class="relative px-8 {{ $compact ? 'py-7' : 'py-10' }} text-center">
        <p class="text-[10px] tracking-[0.25em] uppercase mb-2" style="color: {{ $design['muted'] }};">{{ $design['invite_line'] }}</p>
        <p class="mb-2" style="font-family: 'Great Vibes', cursive; font-size: {{ $compact ? '32px' : '40px' }}; color: {{ $design['accent'] }};">
            Join Us
        </p>
        <p class="text-sm italic mb-5" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">
            {{ $design['request_line'] }}
        </p>

        <div class="mx-auto mb-5 max-w-[90%] rounded-full px-4 py-2 text-xs font-semibold"
             style="background: {{ $design['accent_soft'] }}; color: {{ $design['accent'] }};">
            {{ $design['badge'] }}
        </div>

        <h1 class="leading-tight mb-5" style="font-family: 'Cormorant Garamond', serif; font-weight: 700; font-size: {{ $compact ? '22px' : '26px' }};">
            {{ $event?->title }}
        </h1>

        <div class="space-y-1 mb-6" style="font-family: 'Cormorant Garamond', serif;">
            <p class="text-base">{{ $dateLabel }}@if($timeLabel) · {{ $timeLabel }}@endif</p>
            <p class="text-sm font-semibold">{{ $event?->venue }}</p>
            @if($event?->city)<p class="text-xs" style="color: {{ $design['muted'] }};">{{ $event->city }}</p>@endif
        </div>

        <p class="text-[10px] uppercase tracking-wider mb-1" style="color: {{ $design['muted'] }};">Dear</p>
        <p class="text-2xl mb-6" style="font-family: 'Great Vibes', cursive;">{{ $ticket->holder_name }}</p>

        <div class="flex flex-col items-center">
            <div class="rounded-full bg-white p-3 shadow-sm" style="border: 2px solid {{ $design['border'] }};">
                <img src="{{ $qrImage }}" alt="QR" class="{{ $compact ? 'w-24 h-24' : 'w-32 h-32' }} object-contain rounded-full">
            </div>
            <p class="font-mono text-[11px] font-bold tracking-widest mt-3" style="color: {{ $design['accent'] }};">{{ $ticket->ticket_code }}</p>
            <p class="text-[11px] mt-2" style="color: {{ $design['muted'] }};">{{ $design['footer_line'] }}</p>
            <p class="text-[10px] mt-1">{{ $ticket->ticket_type_name }} · {{ ucfirst($ticket->status) }}</p>
        </div>
    </div>
</article>
