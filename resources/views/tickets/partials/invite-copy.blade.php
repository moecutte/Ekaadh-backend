@php
    extract(\App\Support\InvitationCardData::hydrate(
        $design,
        $ticket ?? null,
        $compact ?? false,
        $showQr ?? true
    ), EXTR_SKIP);
@endphp
