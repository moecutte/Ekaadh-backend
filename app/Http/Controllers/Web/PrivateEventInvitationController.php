<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Services\InvitationService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrivateEventInvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function index(Event $event): View
    {
        $this->authorizeOwner($event);
        abort_unless($event->status === 'published', 404);

        $invitations = EventInvitation::query()
            ->with(['ticketType', 'tickets'])
            ->where('event_id', $event->id)
            ->latest()
            ->paginate(30);

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
            $result = $this->invitations->issueAndSend($event, $guests);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('private-events.invitations.index', $event)
            ->with('success', "Sent {$result['created']} invitation(s).");
    }

    public function resend(Event $event, EventInvitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($event, $invitation);

        try {
            $this->invitations->resend($invitation);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Invitation resent.');
    }

    public function updatePhone(Request $request, Event $event, EventInvitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($event, $invitation);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        try {
            $this->invitations->updatePhoneAndResend($invitation, $data['phone']);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Phone updated and invitation resent.');
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

    /**
     * @return array<int, array{name: ?string, phone: string, quantity: int, ticket_type_id: int}>
     */
    private function resolveGuests(Request $request, Event $event): array
    {
        if ($request->hasFile('csv')) {
            return $this->parseCsv($request, $event);
        }

        $data = $request->validate([
            'guests' => ['required', 'array', 'min:1', 'max:200'],
            'guests.*.name' => ['nullable', 'string', 'max:120'],
            'guests.*.phone' => ['required', 'string', 'max:30'],
            'guests.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'guests.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
        ]);

        $typeIds = $event->ticketTypes->pluck('id')->all();
        $guests = [];

        foreach ($data['guests'] as $i => $row) {
            if (! in_array((int) $row['ticket_type_id'], $typeIds, true)) {
                throw ValidationException::withMessages([
                    "guests.{$i}.ticket_type_id" => ['Invalid ticket type for this event.'],
                ]);
            }

            $phone = Phone::normalize($row['phone']);
            if ($phone === '') {
                throw ValidationException::withMessages([
                    "guests.{$i}.phone" => ['Enter a valid phone number.'],
                ]);
            }

            $guests[] = [
                'name' => $row['name'] ?? null,
                'phone' => $phone,
                'quantity' => (int) $row['quantity'],
                'ticket_type_id' => (int) $row['ticket_type_id'],
            ];
        }

        return $guests;
    }

    /**
     * @return array<int, array{name: ?string, phone: string, quantity: int, ticket_type_id: int}>
     */
    private function parseCsv(Request $request, Event $event): array
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'default_ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
        ]);

        $defaultTypeId = (int) $request->input('default_ticket_type_id');
        $typeIds = $event->ticketTypes->pluck('id')->all();
        if (! in_array($defaultTypeId, $typeIds, true)) {
            throw ValidationException::withMessages([
                'default_ticket_type_id' => ['Invalid ticket type for this event.'],
            ]);
        }

        $typesByName = $event->ticketTypes->mapWithKeys(
            fn ($t) => [mb_strtolower(trim($t->name)) => $t->id]
        );

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv' => ['Could not read the CSV file.'],
            ]);
        }

        $guests = [];
        $rowNum = 0;

        while (($cols = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($rowNum === 1 && $this->looksLikeHeader($cols)) {
                continue;
            }

            $cols = array_map(fn ($c) => trim((string) $c), $cols);
            if (count(array_filter($cols)) === 0) {
                continue;
            }

            $phone = $cols[0] ?? '';
            $name = $cols[1] ?? null;
            $qty = isset($cols[2]) && $cols[2] !== '' ? (int) $cols[2] : 1;
            $typeName = $cols[3] ?? '';

            $typeId = $defaultTypeId;
            if ($typeName !== '') {
                $typeId = $typesByName[mb_strtolower($typeName)] ?? null;
                if (! $typeId) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'csv' => ["Row {$rowNum}: unknown ticket type \"{$typeName}\"."],
                    ]);
                }
            }

            $normalized = Phone::normalize($phone);
            if ($normalized === '') {
                fclose($handle);
                throw ValidationException::withMessages([
                    'csv' => ["Row {$rowNum}: invalid phone \"{$phone}\"."],
                ]);
            }

            if ($qty < 1) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'csv' => ["Row {$rowNum}: quantity must be at least 1."],
                ]);
            }

            $guests[] = [
                'name' => $name !== '' ? $name : null,
                'phone' => $normalized,
                'quantity' => $qty,
                'ticket_type_id' => $typeId,
            ];
        }

        fclose($handle);

        if ($guests === []) {
            throw ValidationException::withMessages([
                'csv' => ['CSV had no guest rows. Use: phone, name, quantity, ticket_type.'],
            ]);
        }

        if (count($guests) > 200) {
            throw ValidationException::withMessages([
                'csv' => ['CSV is limited to 200 guests per upload.'],
            ]);
        }

        return $guests;
    }

    /**
     * @param  array<int, string|null>  $cols
     */
    private function looksLikeHeader(array $cols): bool
    {
        $first = mb_strtolower(trim((string) ($cols[0] ?? '')));

        return in_array($first, ['phone', 'mobile', 'number', 'guest_phone'], true);
    }
}
