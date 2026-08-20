{{-- Router: picks real invitation template by design id --}}
@php
    $compact = $compact ?? false;
    $showQr = $showQr ?? true;
    $template = \App\Support\TicketDesigns::templateView($design);
    $cardData = \App\Support\InvitationCardData::hydrate($design, $ticket ?? null, $compact, $showQr);
@endphp
@include($template, array_merge($cardData, [
    'ticket' => $ticket,
    'qrImage' => $qrImage ?? '',
    'design' => $design,
    'compact' => $compact,
    'showQr' => $showQr,
]))
