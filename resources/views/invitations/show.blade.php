@extends('layouts.app')

@section('title', 'Invitation — '.($event?->title ?? 'Ekaadh'))

@section('content')
@php
    $design = \App\Support\TicketDesigns::resolveForEvent($event);
@endphp
<div class="max-w-lg mx-auto px-4 sm:px-6 py-10">
    @if(! $invitation->isActive())
        <div class="rounded-2xl border border-red-100 bg-red-50 text-red-700 p-6 text-center">
            <h1 class="font-extrabold text-lg mb-2">Invitation cancelled</h1>
            <p class="text-sm">This invitation is no longer valid. Contact the host if you think this is a mistake.</p>
        </div>
    @else
        @include('invitations.partials.envelope', [
            'design' => $design,
            'event' => $event,
            'invitation' => $invitation,
            'tickets' => $tickets,
        ])

        <p class="text-center text-xs mb-4" style="color: {{ $design['muted'] }};">
            {{ $design['footer_line'] }}
        </p>

        <div class="space-y-4">
            @forelse($tickets->where('status', '!=', 'cancelled') as $ticket)
                @include('tickets.partials.designed-card', [
                    'ticket' => $ticket,
                    'qrImage' => $ticket->qr_image,
                    'design' => $design,
                    'compact' => true,
                ])
                <div class="flex flex-wrap gap-2 justify-center -mt-2 mb-2">
                    <a href="{{ $ticket->ticket_url }}" class="inline-flex text-xs font-bold px-3 py-1.5 rounded-lg text-white"
                       style="background: {{ $design['accent'] }};">Open full ticket</a>
                    <a href="{{ route('tickets.pdf', $ticket->ticket_code) }}" class="inline-flex text-xs font-bold px-3 py-1.5 rounded-lg"
                       style="background: {{ $design['accent_soft'] }}; color: {{ $design['accent'] }};">Download PDF</a>
                </div>
            @empty
                <div class="rounded-2xl border p-6 text-center text-sm"
                     style="border-color: {{ $design['border'] }}; color: {{ $design['muted'] }}; background: {{ $design['card_bg'] }};">
                    No active tickets on this invitation.
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection
