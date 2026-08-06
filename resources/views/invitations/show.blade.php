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
    $isOverlayDesign = ! empty($design['graphic_url'])
        || ! empty($design['graphic_path'])
        || (($design['render_mode'] ?? '') === 'overlay');

    $shareSpec = null;
    if ($isOverlayDesign) {
        $values = $design['field_values'] ?? [];
        $guestName = trim((string) ($invitation->guest_name ?? ''));
        $shareFields = [];
        foreach (collect($design['fields'] ?? [])->where('show_on_card', true) as $field) {
            if (($field['field_type'] ?? '') === 'qr') {
                continue;
            }
            $key = $field['field_key'] ?? '';
            $raw = $values[$key] ?? $field['default_text'] ?? '';
            if ($key === 'guest_name' && $guestName !== '') {
                $raw = $guestName;
            }
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            $shareFields[] = [
                'text' => $raw,
                'pos_x' => (float) ($field['pos_x'] ?? 20),
                'pos_y' => (float) ($field['pos_y'] ?? 30),
                'box_width' => (float) ($field['box_width'] ?? 60),
                'font_size' => (float) ($field['font_size'] ?? 18),
                'font_family' => \App\Support\InvitationFonts::cssFontFamily($field['font_family'] ?? 'Montserrat'),
                'font_weight' => (string) ($field['font_weight'] ?? '400'),
                'font_style' => (string) ($field['font_style'] ?? 'normal'),
                'color' => (string) ($field['color'] ?? ($design['text'] ?? '#0f1a2e')),
                'text_align' => (string) ($field['text_align'] ?? 'center'),
            ];
        }
        $shareSpec = [
            'mode' => 'overlay',
            'cardBg' => $design['card_bg'] ?? '#ffffff',
            'graphicUrl' => $design['graphic_url'] ?? null,
            'fields' => $shareFields,
        ];
    }
@endphp
<div class="max-w-lg mx-auto px-4 sm:px-6 py-10">
    @if(! $invitation->isActive())
        <div class="rounded-2xl border border-red-100 bg-red-50 text-red-700 p-6 text-center">
            <h1 class="font-extrabold text-lg mb-2">{{ __('ui.invitation_cancelled') }}</h1>
            <p class="text-sm">{{ __('ui.invitation_cancelled_desc') }}</p>
        </div>
    @else
        <div id="invitation-share-card">
            @if($isOverlayDesign)
                @php
                    $shareTicket = $activeTickets->first();
                    if (! $shareTicket) {
                        $shareTicket = new \App\Models\Ticket([
                            'holder_name' => $invitation->guest_name,
                            'status' => 'valid',
                            'ticket_code' => 'INVITE',
                        ]);
                        $shareTicket->setRelation('event', $event);
                    } else {
                        $shareTicket->holder_name = $invitation->guest_name ?: $shareTicket->holder_name;
                    }
                @endphp
                @include('tickets.partials.designed-card', [
                    'ticket' => $shareTicket,
                    'qrImage' => $shareTicket->qr_image ?? '',
                    'design' => $design,
                    'showQr' => false,
                    'compact' => false,
                ])
            @else
                @include('invitations.partials.envelope', [
                    'design' => $design,
                    'event' => $event,
                    'invitation' => $invitation,
                    'tickets' => $tickets,
                ])
            @endif
        </div>

        @include('invitations.partials.share-image-buttons', [
            'design' => $design,
            'event' => $event,
            'shareSpec' => $shareSpec,
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

@if($invitation->isActive())
@unless($isOverlayDesign)
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
@endunless
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@include('invitations.partials.share-image-script')
@endif
@endsection
