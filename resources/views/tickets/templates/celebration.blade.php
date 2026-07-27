@php
    $compact = $compact ?? false;
    $event = $ticket->event;
    $dateLabel = $event?->event_date?->format('D · M j, Y');
    $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
@endphp
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
<article class="relative overflow-hidden shadow-2xl mx-auto"
         style="max-width: 420px; background: {{ $design['card_bg'] }}; color: {{ $design['text'] }}; border-radius: 24px;">
    <div class="px-5 pt-5 pb-3 text-center text-white"
         style="background: linear-gradient(135deg, {{ $design['header_from'] }}, {{ $design['header_to'] }});">
        <p class="text-[11px] font-bold tracking-[0.2em] uppercase opacity-90">{{ $design['badge'] }}</p>
        <p class="mt-2 text-2xl font-bold" style="font-family: 'Playfair Display', serif;">{{ $design['invite_line'] }}</p>
        <div class="mt-3 flex justify-center gap-2 text-lg opacity-80">
            <span>✦</span><span>◆</span><span>✦</span>
        </div>
    </div>

    <div class="px-6 {{ $compact ? 'py-5' : 'py-7' }} text-center" style="font-family: 'Source Sans 3', sans-serif;">
        <p class="text-sm mb-3" style="color: {{ $design['muted'] }};">{{ $design['request_line'] }}</p>
        <h1 class="font-bold leading-tight mb-4" style="font-family: 'Playfair Display', serif; font-size: {{ $compact ? '22px' : '26px' }};">
            {{ $event?->title }}
        </h1>

        <div class="rounded-2xl px-4 py-3 mb-5 inline-block text-left min-w-[80%]"
             style="background: {{ $design['accent_soft'] }}; border: 1px dashed {{ $design['border'] }};">
            <p class="text-sm font-bold">{{ $dateLabel }}</p>
            @if($timeLabel)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $timeLabel }}</p>@endif
            <p class="text-sm font-semibold mt-1">{{ $event?->venue }}</p>
        </div>

        <div class="mb-5">
            <p class="text-[10px] uppercase tracking-wider font-bold" style="color: {{ $design['muted'] }};">Celebrating with</p>
            <p class="text-xl font-bold mt-1">{{ $ticket->holder_name }}</p>
            <p class="text-xs" style="color: {{ $design['muted'] }};">{{ $ticket->ticket_type_name }}</p>
        </div>

        <div class="flex flex-col items-center">
            <div class="rounded-2xl bg-white p-2 shadow-sm" style="border: 2px solid {{ $design['border'] }};">
                <img src="{{ $qrImage }}" alt="QR" class="{{ $compact ? 'w-28 h-28' : 'w-40 h-40' }} object-contain">
            </div>
            <p class="font-mono text-xs font-extrabold tracking-widest mt-3" style="color: {{ $design['accent'] }};">{{ $ticket->ticket_code }}</p>
            <p class="text-[11px] mt-2 font-semibold" style="color: {{ $design['muted'] }};">{{ $design['footer_line'] }}</p>
        </div>
    </div>
</article>
