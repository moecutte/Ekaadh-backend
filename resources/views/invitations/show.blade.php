@extends('layouts.app')

@section('title', __('ui.invitation').' — '.($event?->title ?? 'Ekaadh'))

@section('content')
@php
    $design = $design ?? \App\Support\TicketDesigns::resolveForEvent($event);
    if (empty($design['field_values']) && $event) {
        $design['field_values'] = \App\Support\InvitationDateFields::applyToValues(
            $design['fields'] ?? [],
            $event->invitation_field_values ?? [],
            $event->event_date,
            $event->event_time,
        );
    }
    $activeTickets = $tickets->where('status', '!=', 'cancelled')->values();
@endphp
<div class="max-w-lg mx-auto px-4 sm:px-6 py-10">
    @if(! $invitation->isActive())
        <div class="rounded-2xl border border-red-100 bg-red-50 text-red-700 p-6 text-center">
            <h1 class="font-extrabold text-lg mb-2">{{ __('ui.invitation_cancelled') }}</h1>
            <p class="text-sm">{{ __('ui.invitation_cancelled_desc') }}</p>
        </div>
    @else
        {{-- Previous simple invite (upper envelope design) --}}
        @include('invitations.partials.envelope', [
            'design' => $design,
            'event' => $event,
            'invitation' => $invitation,
            'tickets' => $tickets,
        ])

        <p class="text-center text-xs mb-6" style="color: {{ $design['muted'] }};">
            {{ $design['footer_line'] }}
        </p>

        @if($activeTickets->isNotEmpty())
            <div class="space-y-3">
                @foreach($activeTickets as $ticket)
                    <div class="rounded-2xl border px-4 py-3 flex flex-wrap items-center justify-between gap-3"
                         style="border-color: {{ $design['border'] }}; background: {{ $design['card_bg'] }};">
                        <div>
                            <p class="font-bold text-sm" style="color: {{ $design['text'] }};">{{ $ticket->holder_name }}</p>
                            <p class="text-xs font-mono mt-0.5" style="color: {{ $design['muted'] }};">{{ $ticket->ticket_code }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ $ticket->ticket_url }}" class="inline-flex text-xs font-bold px-3 py-1.5 rounded-lg text-white"
                               style="background: {{ $design['accent'] }};">{{ __('ui.open_full_ticket') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border p-6 text-center text-sm"
                 style="border-color: {{ $design['border'] }}; color: {{ $design['muted'] }}; background: {{ $design['card_bg'] }};">
                {{ __('ui.no_active_tickets') }}
            </div>
        @endif
    @endif
</div>
@endsection
