@php
    $compact = $compact ?? false;
    $event = $ticket->event;
    $dateLabel = $event?->event_date?->format('F j, Y');
    $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
@endphp
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<article class="relative mx-auto shadow-2xl overflow-hidden"
         style="max-width: 420px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 18px;">
    <div class="absolute inset-0 opacity-30 pointer-events-none"
         style="background-image: radial-gradient(circle at 20% 20%, {{ $design['accent'] }}33 0 1px, transparent 1px), radial-gradient(circle at 80% 40%, {{ $design['accent'] }}22 0 1px, transparent 1px), radial-gradient(circle at 40% 80%, {{ $design['accent'] }}28 0 1px, transparent 1px); background-size: 48px 48px;"></div>

    <div class="relative px-7 {{ $compact ? 'py-7' : 'py-10' }} text-center">
        <p class="text-[10px] tracking-[0.35em] uppercase mb-3" style="color: {{ $design['muted'] }};">{{ $design['badge'] }}</p>
        <p class="text-sm tracking-wide mb-2" style="font-family: 'Playfair Display', serif; font-style: italic; color: {{ $design['accent'] }};">
            {{ $design['invite_line'] }}
        </p>
        <h1 class="leading-tight mb-3" style="font-family: 'Playfair Display', serif; font-weight: 700; font-size: {{ $compact ? '22px' : '28px' }};">
            {{ $event?->title }}
        </h1>
        <p class="text-sm mb-6" style="color: {{ $design['muted'] }}; font-family: 'Source Sans 3', sans-serif;">
            {{ $design['request_line'] }}
        </p>

        <div class="mx-auto mb-6 max-w-[85%] rounded-xl px-4 py-3"
             style="background: {{ $design['accent_soft'] }}; border: 1px solid {{ $design['border'] }};">
            <p class="font-semibold" style="font-family: 'Playfair Display', serif;">{{ $dateLabel }}</p>
            @if($timeLabel)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $timeLabel }}</p>@endif
            <p class="text-sm mt-1">{{ $event?->venue }}</p>
        </div>

        <p class="text-[10px] uppercase tracking-widest mb-1" style="color: {{ $design['muted'] }};">Guest</p>
        <p class="text-lg font-semibold mb-1" style="font-family: 'Playfair Display', serif;">{{ $ticket->holder_name }}</p>
        <p class="text-xs mb-6" style="color: {{ $design['muted'] }};">{{ $ticket->ticket_type_name }}</p>

        <div class="flex flex-col items-center">
            <div class="p-2 rounded-xl bg-white/95">
                <img src="{{ $qrImage }}" alt="QR" class="{{ $compact ? 'w-28 h-28' : 'w-36 h-36' }} object-contain">
            </div>
            <p class="font-mono text-[11px] font-bold tracking-[0.2em] mt-3" style="color: {{ $design['accent'] }};">{{ $ticket->ticket_code }}</p>
            <p class="text-[11px] mt-2" style="color: {{ $design['muted'] }};">{{ $design['footer_line'] }}</p>
            <p class="text-[10px] mt-1">★ Status: {{ ucfirst($ticket->status) }} ★</p>
        </div>
    </div>
</article>
