@extends('layouts.app')

@section('title', 'Ticket '.$ticket->ticket_code)

@section('content')
@php
    $design = \App\Support\TicketDesigns::resolveForEvent($ticket->event);
@endphp
<div class="max-w-md mx-auto px-4 sm:px-6 py-10">
    @if(!empty($invitationUrl))
        <a href="{{ $invitationUrl }}" class="text-sm font-bold hover:opacity-80 mb-6 inline-block" style="color: {{ $design['muted'] }};">&larr; All invitation tickets</a>
    @else
        <a href="{{ route('tickets.index', ['phone' => $ticket->orderItem?->order?->buyer_phone]) }}" class="text-sm font-bold text-mute hover:text-brand mb-6 inline-block">&larr; My tickets</a>
    @endif

    @include('tickets.partials.designed-card', [
        'ticket' => $ticket,
        'qrImage' => $qrImage,
        'design' => $design,
        'compact' => false,
    ])

    <div class="mt-5 grid grid-cols-2 gap-3">
        <a href="{{ route('tickets.pdf', $ticket->ticket_code) }}"
           class="text-center rounded-2xl font-extrabold py-3.5 text-sm text-white transition-opacity hover:opacity-90"
           style="background: {{ $design['accent'] }};">Download PDF</a>
        <a href="{{ $qrImage }}" download
           class="text-center rounded-2xl font-extrabold py-3.5 text-sm"
           style="background: {{ $design['accent_soft'] }}; color: {{ $design['accent'] }};">Download QR</a>
    </div>
</div>
@endsection
