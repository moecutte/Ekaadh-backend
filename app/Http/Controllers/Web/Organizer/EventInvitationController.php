<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ResolvesInvitationGuests;
use App\Http\Controllers\Web\Organizer\Concerns\ResolvesOrganizerProfile;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventInvitationController extends Controller
{
    use ResolvesInvitationGuests;
    use ResolvesOrganizerProfile;

    public function __construct(private InvitationService $invitations) {}

    public function index(Event $event): View
    {
        $this->authorizeEvent($event);

        $invitations = EventInvitation::query()
            ->with(['ticketType', 'tickets'])
            ->where('event_id', $event->id)
            ->latest()
            ->paginate(30);

        $event->load(['ticketTypes', 'package']);
        $remaining = $event->ticketTypes->sum(fn ($t) => $t->remaining());
        $pending = is_array($event->pending_invitations) ? ($event->pending_invitations['guests'] ?? []) : [];
        $guestLimit = Event::MAX_COMPLIMENTARY_GUESTS;
        $guestUsed = $event->activeComplimentaryGuestCount();
        $guestSlots = $event->complimentaryGuestSlotsLeft();

        return view('organizer.events.invitations.index', compact(
            'event',
            'invitations',
            'remaining',
            'pending',
            'guestLimit',
            'guestUsed',
            'guestSlots',
        ));
    }

    public function create(Event $event): View|RedirectResponse
    {
        $this->authorizeEvent($event);
        abort_unless($event->status === 'published', 404);

        $slots = $event->complimentaryGuestSlotsLeft();
        if ($slots < 1) {
            return redirect()
                ->route('organizer.events.invitations.index', $event)
                ->with('error', 'Complimentary guests are limited to '.Event::MAX_COMPLIMENTARY_GUESTS.' per event.');
        }

        return view('organizer.events.invitations.create', [
            'event' => $event->load('ticketTypes'),
            'guestSlots' => $slots,
            'guestLimit' => Event::MAX_COMPLIMENTARY_GUESTS,
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        abort_unless($event->status === 'published', 404);

        $slots = $event->complimentaryGuestSlotsLeft();
        if ($slots < 1) {
            return back()->with('error', 'Complimentary guests are limited to '.Event::MAX_COMPLIMENTARY_GUESTS.' per event.');
        }

        try {
            $guests = $this->resolveGuests($request, $event->load('ticketTypes'), $slots);
            $channel = $this->resolveInviteChannel($request);
            $result = $this->invitations->issueAndSend($event, $guests, $channel);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('organizer.events.invitations.index', $event)
            ->with('success', "Sent {$result['created']} complimentary invitation(s).");
    }

    public function flush(Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);

        $result = $this->invitations->flushPending($event->fresh(['ticketTypes']));

        if ($result['created'] > 0) {
            return redirect()
                ->route('organizer.events.invitations.index', $event)
                ->with('success', "Sent {$result['created']} queued invitation(s).");
        }

        if ($result['error']) {
            return back()->with('error', $result['error']);
        }

        if ($event->status !== 'published') {
            return back()->with('error', 'Queued invitations send automatically after the event is published.');
        }

        return back()->with('error', 'No queued invitations to send.');
    }

    public function resend(Request $request, Event $event, EventInvitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($event, $invitation);

        try {
            $this->invitations->resend($invitation, $this->optionalInviteChannel($request));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Invitation resent.');
    }

    public function revoke(Event $event, EventInvitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($event, $invitation);
        $this->invitations->revoke($invitation);

        return back()->with('success', 'Invitation revoked. Those seats are available again.');
    }

    private function authorizeEvent(Event $event): void
    {
        abort_unless($event->organizer_id === $this->organizerProfile()->id, 403);
        abort_unless(! $event->is_private, 404);
    }

    private function authorizeInvitation(Event $event, EventInvitation $invitation): void
    {
        $this->authorizeEvent($event);
        abort_unless($invitation->event_id === $event->id, 404);
    }
}
