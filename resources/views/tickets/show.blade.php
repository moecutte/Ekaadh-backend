@extends('layouts.app')

@section('title', __('ui.tickets').' '.$ticket->ticket_code)

@section('content')
@php
    $design = $design ?? \App\Support\TicketDesigns::resolveForEvent($ticket->event);
    $useEnvelope = ! ($ticket->event?->is_private);
    $ticketFileName = 'ekaadh-ticket-'.strtolower($ticket->ticket_code).'.png';
@endphp
<div class="max-w-md mx-auto px-4 sm:px-6 py-10">
    @if(!empty($invitationUrl))
        <a href="{{ $invitationUrl }}" class="text-sm font-bold hover:opacity-80 mb-6 inline-block" style="color: {{ $design['muted'] }};">&larr; {{ __('ui.all_invitation_tickets') }}</a>
    @else
        <a href="{{ route('tickets.index', ['phone' => $ticket->orderItem?->order?->buyer_phone]) }}" class="text-sm font-bold text-mute hover:text-brand mb-6 inline-block">&larr; {{ __('ui.my_tickets') }}</a>
    @endif

    @include('invitations.partials.invitation-fonts')
    @include('invitations.partials.invite-look', [
        'ticket' => $ticket,
        'qrImage' => $qrImage,
        'design' => $design,
        'showQr' => true,
        'compact' => false,
        'withEnvelope' => $useEnvelope,
        'autoOpen' => false,
        'envelopeGuest' => $ticket->holder_name,
    ])

    @php
        $isPrivateInvite = ! empty($invitationUrl) && $ticket->event?->is_private;
    @endphp

    @unless($isPrivateInvite)
    <div
        class="mt-5 {{ !empty($invitationUrl) ? '' : 'grid grid-cols-2 gap-3' }}"
        x-data='invitationImageShare({
            targetId: "invitation-share-card",
            text: @json($ticket->event?->title ?? "Ekaadh"),
            fileName: @json($ticketFileName),
            failMsg: @json(__("ui.share_invitation_failed")),
            preparingMsg: @json(__("ui.sharing_invitation")),
            shareLabel: @json(__("ui.download_ticket_image")),
            spec: null,
        })'
    >
        @if(empty($invitationUrl))
            <a href="{{ route('tickets.pdf', $ticket->ticket_code) }}"
               class="text-center rounded-2xl font-extrabold py-3.5 text-sm text-white transition-opacity hover:opacity-90 block"
               style="background: {{ $design['accent'] }};">{{ __('ui.download_pdf') }}</a>
        @endif
        <button
            type="button"
            @click="download()"
            :disabled="busy"
            class="text-center rounded-2xl font-extrabold py-3.5 text-sm block transition disabled:opacity-60 {{ !empty($invitationUrl) ? 'w-full' : '' }}"
            style="background: {{ $design['accent_soft'] }}; color: {{ $design['accent'] }};"
        >
            <span x-text="busy ? preparingMsg : shareLabel"></span>
        </button>
        <p x-show="error" x-cloak x-text="error" class="text-center text-xs text-red-600 mt-2 {{ !empty($invitationUrl) ? '' : 'col-span-2' }}"></p>
    </div>
    @endunless
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@include('invitations.partials.share-image-script')
@endsection
