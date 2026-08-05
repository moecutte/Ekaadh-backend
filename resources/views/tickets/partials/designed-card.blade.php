{{-- Router: picks real invitation template by design id --}}
@php
    $compact = $compact ?? false;
    $showQr = $showQr ?? true;
    $template = \App\Support\TicketDesigns::templateView($design);
@endphp
@include($template, [
    'ticket' => $ticket,
    'qrImage' => $qrImage,
    'design' => $design,
    'compact' => $compact,
    'showQr' => $showQr,
])
