@php
    $compact = $compact ?? false;
    $event = $ticket->event;
    $dateLabel = $event?->event_date?->format('l, F j, Y');
    $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
@endphp
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Great+Vibes&display=swap" rel="stylesheet">
<article class="relative overflow-hidden shadow-2xl mx-auto"
         style="max-width: 420px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 8px;">
    {{-- Double ornate frame --}}
    <div class="absolute inset-3 pointer-events-none" style="border: 1px solid {{ $design['border'] }};"></div>
    <div class="absolute inset-4 pointer-events-none" style="border: 1px solid {{ $design['accent'] }}33;"></div>

    <div class="relative px-8 {{ $compact ? 'py-6' : 'py-10' }} text-center">
        <p class="text-[10px] tracking-[0.35em] uppercase font-semibold mb-3" style="color: {{ $design['muted'] }};">
            {{ $design['invite_line'] }}
        </p>
        <p class="mb-4" style="font-family: 'Great Vibes', cursive; font-size: {{ $compact ? '28px' : '36px' }}; color: {{ $design['accent'] }}; line-height: 1.1;">
            You're Invited
        </p>
        <p class="text-sm italic mb-5" style="font-family: 'Cormorant Garamond', serif; color: {{ $design['muted'] }};">
            {{ $design['request_line'] }}
        </p>

        <div class="my-5 flex items-center justify-center gap-3">
            <span class="h-px w-10" style="background: {{ $design['border'] }};"></span>
            <span style="color: {{ $design['accent'] }};">{{ $design['ornament'] }}</span>
            <span class="h-px w-10" style="background: {{ $design['border'] }};"></span>
        </div>

        <h1 class="font-bold leading-tight mb-2" style="font-family: 'Cormorant Garamond', serif; font-size: {{ $compact ? '22px' : '28px' }};">
            {{ $event?->coupleDisplayName() ?: $event?->title }}
        </h1>

        <div class="mt-5 space-y-1" style="font-family: 'Cormorant Garamond', serif;">
            <p class="text-base font-semibold">{{ $dateLabel }}</p>
            @if($timeLabel)<p class="text-sm" style="color: {{ $design['muted'] }};">at {{ $timeLabel }}</p>@endif
            <p class="text-sm mt-2" style="color: {{ $design['muted'] }};">{{ $event?->venue }}</p>
            @if($event?->address || $event?->city)
                <p class="text-xs" style="color: {{ $design['muted'] }};">
                    {{ collect([$event?->address, $event?->city])->filter()->implode(', ') }}
                </p>
            @endif
        </div>

        <div class="mt-6 pt-5" style="border-top: 1px solid {{ $design['border'] }};">
            <p class="text-[10px] uppercase tracking-widest mb-1" style="color: {{ $design['muted'] }};">Guest of honour</p>
            <p class="text-lg font-semibold" style="font-family: 'Cormorant Garamond', serif;">{{ $ticket->holder_name }}</p>
            <p class="text-xs mt-1" style="color: {{ $design['muted'] }};">{{ $ticket->ticket_type_name }}</p>
        </div>

        <div class="mt-6 flex flex-col items-center">
            <div class="p-2 bg-white inline-block" style="border: 1px solid {{ $design['border'] }};">
                <img src="{{ $qrImage }}" alt="QR" class="{{ $compact ? 'w-28 h-28' : 'w-40 h-40' }} object-contain">
            </div>
            <p class="font-mono text-xs font-bold tracking-widest mt-3" style="color: {{ $design['accent'] }};">{{ $ticket->ticket_code }}</p>
            <p class="text-[11px] mt-2 italic" style="color: {{ $design['muted'] }};">{{ $design['footer_line'] }}</p>
            <p class="text-[10px] mt-1 font-semibold">
                Status:
                <span style="color: {{ $ticket->status === 'valid' ? $design['accent'] : '#ef4444' }};">{{ ucfirst($ticket->status) }}</span>
            </p>
        </div>
    </div>
</article>
