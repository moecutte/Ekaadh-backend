<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EventInvitation;
use App\Models\Ticket;
use App\Services\InvitationService;
use App\Services\TicketQrService;
use App\Support\InvitationDateFields;
use App\Support\TicketDesigns;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(
        private TicketQrService $qr,
        private InvitationService $invitations,
    ) {}

    public function show(string $token): View
    {
        $invitation = EventInvitation::query()
            ->with([
                'event.invitationDesign.fields',
                'ticketType',
                'tickets' => fn ($q) => $q->orderBy('id'),
            ])
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isActive()) {
            $this->invitations->markOpened($invitation);
        }

        $event = $invitation->event;

        $tickets = $invitation->tickets->map(function (Ticket $ticket) {
            $payload = $this->qr->payload($ticket->ticket_code);
            $ticket->qr_image = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data='.urlencode($payload);
            $ticket->ticket_url = $this->qr->publicUrl($ticket->ticket_code);

            return $ticket;
        });

        $design = TicketDesigns::resolveForEvent($event);
        $design['field_values'] = InvitationDateFields::applyToValues(
            $design['fields'] ?? [],
            $event?->invitation_field_values ?? ($design['field_values'] ?? []),
            $event?->event_date,
            $event?->event_time,
        );

        return view('invitations.show', [
            'invitation' => $invitation->fresh(['tickets']),
            'tickets' => $tickets,
            'event' => $event,
            'design' => $design,
        ]);
    }
}
