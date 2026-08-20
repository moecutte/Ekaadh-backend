@php
    extract(\App\Support\InvitationCardData::hydrate(
        $design,
        $ticket ?? null,
        $compact ?? false,
        $showQr ?? true
    ), EXTR_SKIP);
    $align = $align ?? 'center';
    $nameFont = $nameFont ?? "'Cormorant Garamond', serif";
    $bodyFont = $bodyFont ?? "'Cormorant Garamond', serif";
    $scriptFont = $scriptFont ?? "'Great Vibes', cursive";
    $guestLabel = $guestLabel ?? 'Guest of honour';
    $inviteLine = $inviteLine ?? '';
    $requestLine = $requestLine ?? '';
    $headline = $headline ?? '';
    $prettyDate = $prettyDate ?? '';
    $prettyTime = $prettyTime ?? null;
    $venueLine = $venueLine ?? '';
    $addressLine = $addressLine ?? '';
    $guestName = $guestName ?? '';
    $ticketType = $ticketType ?? '';
    $ticketCode = $ticketCode ?? '';
    $footerLine = $footerLine ?? 'Kindly present this invitation at the entrance';
    $statusLabel = $statusLabel ?? 'Valid';
    $statusOk = $statusOk ?? true;
@endphp
<div class="relative text-{{ $align }}">
    @if($inviteLine)
        <p class="inv-rise text-[10px] tracking-[0.38em] uppercase font-semibold mb-2" style="color: {{ $design['muted'] }}; font-family: {{ $bodyFont }};">
            {{ $inviteLine }}
        </p>
    @endif

    @if(!empty($scriptLine))
        <p class="inv-rise-2 mb-3 leading-none" style="font-family: {{ $scriptFont }}; font-size: {{ $compact ? '30px' : '40px' }}; color: {{ $design['accent'] }};">
            {{ $scriptLine }}
        </p>
    @endif

    @if($requestLine)
        <p class="inv-rise-2 text-sm italic mb-4" style="font-family: {{ $bodyFont }}; color: {{ $design['muted'] }};">
            {{ $requestLine }}
        </p>
    @endif

    @if($headline)
        <h1 class="inv-rise-3 font-bold leading-tight mb-4" style="font-family: {{ $nameFont }}; font-size: {{ $compact ? '22px' : '28px' }};">
            {{ $headline }}
        </h1>
    @endif

    <div class="inv-rise-3 space-y-1 mb-5" style="font-family: {{ $bodyFont }};">
        @if($prettyDate)<p class="text-base font-semibold">{{ $prettyDate }}</p>@endif
        @if($prettyTime)<p class="text-sm" style="color: {{ $design['muted'] }};">{{ $prettyTime }}</p>@endif
        @if($venueLine !== '')<p class="text-sm mt-2 font-semibold">{{ $venueLine }}</p>@endif
        @if($addressLine !== '')<p class="text-xs" style="color: {{ $design['muted'] }};">{{ $addressLine }}</p>@endif
    </div>

    @if($guestName !== '')
        <p class="inv-rise-4 text-[10px] uppercase tracking-widest mb-1" style="color: {{ $design['muted'] }};">{{ $guestLabel }}</p>
        <p class="inv-rise-4 text-xl mb-1" style="font-family: {{ $scriptFont }}; color: {{ $design['accent'] }};">{{ $guestName }}</p>
        @if($ticketType !== '')<p class="text-xs mb-4" style="color: {{ $design['muted'] }};">{{ $ticketType }}</p>@endif
    @endif

    @if($showQr && ! empty($qrImage))
        <div class="inv-rise-4 flex flex-col items-center">
            <div class="p-2 bg-white inline-block" style="border: 1px solid {{ $design['border'] }};">
                <img src="{{ $qrImage }}" alt="QR" class="{{ $compact ? 'w-28 h-28' : 'w-36 h-36' }} object-contain">
            </div>
            @if($ticketCode !== '')
                <p class="font-mono text-[11px] font-bold tracking-[0.22em] mt-3" style="color: {{ $design['accent'] }};">{{ $ticketCode }}</p>
            @endif
            <p class="text-[11px] mt-2 italic" style="color: {{ $design['muted'] }};">{{ $footerLine }}</p>
            <p class="text-[10px] mt-1 font-semibold">
                Status:
                <span style="color: {{ $statusOk ? $design['accent'] : '#ef4444' }};">{{ $statusLabel }}</span>
            </p>
        </div>
    @endif
</div>
