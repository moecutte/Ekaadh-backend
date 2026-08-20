<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ResolvesInvitationGuests;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrivateEventInvitationController extends Controller
{
    use ResolvesInvitationGuests;

    public function __construct(private InvitationService $invitations) {}

    public function index(Request $request, Event $event): View
    {
        $this->authorizeOwner($event);
        abort_unless($event->status === 'published', 404);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 15;

        $invitations = EventInvitation::query()
            ->with(['ticketType', 'tickets'])
            ->where('event_id', $event->id)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $remaining = $event->ticketTypes->sum(fn ($t) => $t->remaining());

        return view('private-events.invitations.index', [
            'event' => $event->load(['ticketTypes', 'invitationDesign']),
            'invitations' => $invitations,
            'remaining' => $remaining,
        ]);
    }

    public function create(Event $event): View
    {
        $this->authorizeOwner($event);
        abort_unless($event->status === 'published', 404);

        return view('private-events.invitations.create', [
            'event' => $event->load(['ticketTypes', 'invitationDesign']),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($event);

        try {
            $guests = $this->resolveGuests($request, $event);
            $channel = $this->resolveInviteChannel($request);
            $result = $this->invitations->issueAndSend($event, $guests, $channel);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('private-events.invitations.index', $event)
            ->with('success', "Sent {$result['created']} invitation(s).");
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

        return back()->with('success', 'Invitation revoked.');
    }

    private function authorizeOwner(Event $event): void
    {
        $user = auth()->user();
        abort_unless($user && $user->isCustomer(), 403);
        abort_unless($event->is_private && $event->owner_user_id === $user->id, 403);
    }

    private function authorizeInvitation(Event $event, EventInvitation $invitation): void
    {
        $this->authorizeOwner($event);
        abort_unless($invitation->event_id === $event->id, 404);
    }
}
