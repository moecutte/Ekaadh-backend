{{-- Router: picks real invitation template by design id --}}
@php
    $compact = $compact ?? false;
    $template = \App\Support\TicketDesigns::templateView($design);
@endphp
@include($template, [
    'ticket' => $ticket,
    'qrImage' => $qrImage,
    'design' => $design,
    'compact' => $compact,
])
