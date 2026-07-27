{{-- Classic public event ticket (unchanged structure) --}}
@php $compact = $compact ?? false; @endphp
<div class="overflow-hidden shadow-xl bg-white border border-slate-100" style="border-radius: 28px;">
    <div class="relative {{ $compact ? 'h-28' : 'h-40' }} bg-slate-200">
        @if($ticket->event?->cover_image)
            <img src="{{ $ticket->event->cover_image }}" class="w-full h-full object-cover" alt="">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-black/10"></div>
        @endif
        <div class="absolute bottom-4 left-5 right-5 text-white">
            <div class="font-black {{ $compact ? 'text-base' : 'text-lg' }} leading-snug">{{ $ticket->event?->title }}</div>
            <div class="text-xs text-white/75 mt-1">
                {{ $ticket->event?->event_date?->format('M j, Y') }}
                @if($ticket->event?->event_time)
                    · {{ date('g:i A', strtotime($ticket->event->event_time)) }}
                @endif
                · {{ $ticket->event?->venue }}
            </div>
        </div>
    </div>
    <div class="px-5 pt-5 pb-3 flex justify-between gap-4">
        <div>
            <div class="text-[11px] text-mute font-semibold">Ticket Holder</div>
            <div class="font-black text-lg">{{ $ticket->holder_name }}</div>
        </div>
        <div class="text-right">
            <div class="text-[11px] text-mute font-semibold">Type</div>
            <div class="font-black">{{ $ticket->ticket_type_name }}</div>
        </div>
    </div>
    <div class="text-center font-mono font-extrabold text-brand tracking-widest text-sm mb-3">
        {{ $ticket->ticket_code }} · ADMIT ONE
    </div>
    <div class="flex items-center px-3">
        <div class="w-6 h-6 rounded-full bg-page"></div>
        <div class="flex-1 border-t-2 border-dashed border-slate-200 mx-1"></div>
        <div class="w-6 h-6 rounded-full bg-page"></div>
    </div>
    <div class="flex flex-col items-center py-6">
        <img src="{{ $qrImage }}" alt="Ticket QR" class="rounded-xl border border-slate-100 bg-white p-2 {{ $compact ? 'w-36 h-36' : 'w-56 h-56' }} object-contain">
        <p class="text-[11px] text-mute mt-3 font-semibold">
            Scan at entry · Status: <span class="{{ $ticket->status === 'valid' ? 'text-brand' : 'text-red-500' }}">{{ ucfirst($ticket->status) }}</span>
        </p>
    </div>
</div>
