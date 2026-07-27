<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventInvitationResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PrivateEventResource;
use App\Models\City;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\Order;
use App\Models\Category;
use App\Services\InvitationService;
use App\Services\OrderService;
use App\Services\PrivateEventService;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PrivateEventController extends Controller
{
    public function __construct(private PrivateEventService $privateEvents) {}

    public function meta(): JsonResponse
    {
        $this->customer();

        return response()->json([
            'unit_price' => PrivateEventService::unitPrice(),
            'premium_design_surcharge' => PrivateEventService::premiumDesignSurcharge(),
            'service_fee' => PrivateEventService::serviceFee(),
            'max_tickets' => PrivateEventService::maxTickets(),
            'cities' => City::activeNames(),
            'categories' => Category::activeOptionsForPrivate(),
            'designs' => [
                'all' => array_values(\App\Support\TicketDesigns::all()),
                'standard' => \App\Support\TicketDesigns::standard(),
                'premium' => \App\Support\TicketDesigns::premium(),
                'default' => \App\Support\TicketDesigns::defaultId(),
            ],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->customer();

        $events = Event::query()
            ->with(['ticketTypes', 'privateEventCategory'])
            ->where('owner_user_id', $user->id)
            ->where('is_private', true)
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        return PrivateEventResource::collection($events);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->customer();
        $max = PrivateEventService::maxTickets();
        $categoryIds = Category::activePrivateChildIds();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100', Rule::in(City::activeNames())],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$max],
            'ticket_label' => ['nullable', 'string', 'max:120'],
            'ticket_design' => ['nullable', 'string', Rule::in(\App\Support\TicketDesigns::ids())],
            'invitation_design_id' => ['required', 'integer', 'exists:invitation_designs,id'],
            'invitation_field_values' => ['nullable', 'array'],
            'invitation_field_values.*' => ['nullable', 'string', 'max:500'],
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'private_event_category_id' => ['required', 'integer', Rule::in($categoryIds)],
        ]);

        try {
            $result = $this->privateEvents->createWithCheckout($user, $data);
        } catch (ValidationException $e) {
            throw $e;
        }

        $event = $result['event']->load(['ticketTypes', 'privateEventCategory']);
        $event->pending_order = $result['order']->load(['items.ticketType', 'event', 'payment']);

        return response()->json([
            'message' => 'Private event created. Complete payment to unlock invitations.',
            'event' => new PrivateEventResource($event),
            'order' => new OrderResource($result['order']),
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        $event->load('ticketTypes');
        $pending = $this->pendingOrder($event, $user->id);
        if ($pending) {
            $event->pending_order = $pending->load(['items.ticketType', 'event', 'payment']);
        }

        return response()->json([
            'data' => new PrivateEventResource($event),
        ]);
    }

    public function pay(Request $request, Event $event): JsonResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        $data = $request->validate([
            'payment_method' => ['required', 'in:zaad,edahab'],
            'force_fail' => ['sometimes', 'boolean'],
        ]);

        $order = $this->pendingOrder($event, $user->id);
        if (! $order) {
            return response()->json([
                'message' => 'No pending payment for this private event.',
            ], 404);
        }

        $order = $this->privateEvents->payCapacityOrder(
            $order,
            $data['payment_method'],
            $user->phone,
            OrderService::allowsForceFail() && $request->boolean('force_fail')
        );

        $event = $event->fresh()->load('ticketTypes');

        if ($order->status === 'paid') {
            return response()->json([
                'message' => 'Payment successful. You can send invitations now.',
                'event' => new PrivateEventResource($event),
                'order' => new OrderResource($order->load(['items.ticketType', 'event', 'payment'])),
            ]);
        }

        return response()->json([
            'message' => 'Payment could not be completed.',
            'event' => new PrivateEventResource($event),
            'order' => new OrderResource($order->load(['items.ticketType', 'event', 'payment'])),
        ], 422);
    }

    public function addCapacity(Request $request, Event $event): JsonResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);
        $max = PrivateEventService::maxTickets();

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$max],
        ]);

        $result = $this->privateEvents->addCapacityCheckout($event, $user, (int) $data['quantity']);
        $event = $result['event']->load('ticketTypes');
        $event->pending_order = $result['order']->load(['items.ticketType', 'event', 'payment']);

        return response()->json([
            'message' => 'Pay to add more invitation tickets.',
            'event' => new PrivateEventResource($event),
            'order' => new OrderResource($result['order']),
        ], 201);
    }

    public function invitations(Event $event): AnonymousResourceCollection
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);
        abort_unless($event->status === 'published', 404);

        $invitations = EventInvitation::query()
            ->with(['ticketType', 'tickets'])
            ->where('event_id', $event->id)
            ->latest()
            ->paginate(50);

        return EventInvitationResource::collection($invitations)->additional([
            'remaining' => $event->ticketTypes->sum(fn ($t) => $t->remaining()),
            'event' => new PrivateEventResource($event->load('ticketTypes')),
        ]);
    }

    public function storeInvitations(Request $request, Event $event, InvitationService $invitations): JsonResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);
        abort_unless($event->status === 'published', 404);

        $data = $request->validate([
            'guests' => ['required', 'array', 'min:1', 'max:200'],
            'guests.*.name' => ['nullable', 'string', 'max:120'],
            'guests.*.phone' => ['required', 'string', 'max:30'],
            'guests.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'guests.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
        ]);

        $typeIds = $event->ticketTypes()->pluck('id')->all();
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

        $result = $invitations->issueAndSend($event, $guests);

        return response()->json([
            'message' => "Sent {$result['created']} invitation(s).",
            'created' => $result['created'],
            'invitations' => EventInvitationResource::collection($result['invitations']),
        ], 201);
    }

    public function resendInvitation(Event $event, EventInvitation $invitation, InvitationService $invitations): JsonResponse
    {
        $this->authorizeInvitation($event, $invitation);
        $invitation = $invitations->resend($invitation);

        return response()->json([
            'message' => 'Invitation resent.',
            'invitation' => new EventInvitationResource($invitation),
        ]);
    }

    public function updateInvitationPhone(
        Request $request,
        Event $event,
        EventInvitation $invitation,
        InvitationService $invitations,
    ): JsonResponse {
        $this->authorizeInvitation($event, $invitation);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $invitation = $invitations->updatePhoneAndResend($invitation, $data['phone']);

        return response()->json([
            'message' => 'Phone updated and invitation resent.',
            'invitation' => new EventInvitationResource($invitation),
        ]);
    }

    public function revokeInvitation(Event $event, EventInvitation $invitation, InvitationService $invitations): JsonResponse
    {
        $this->authorizeInvitation($event, $invitation);
        $invitation = $invitations->revoke($invitation);

        return response()->json([
            'message' => 'Invitation revoked.',
            'invitation' => new EventInvitationResource($invitation),
        ]);
    }

    private function customer()
    {
        $user = auth('sanctum')->user();
        abort_unless($user && method_exists($user, 'isCustomer') && $user->isCustomer(), 403);

        return $user;
    }

    private function authorizeOwner(Event $event, $user): void
    {
        abort_unless($event->is_private && $event->owner_user_id === $user->id, 403);
    }

    private function authorizeInvitation(Event $event, EventInvitation $invitation): void
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);
        abort_unless($invitation->event_id === $event->id, 404);
    }

    private function pendingOrder(Event $event, int $userId): ?Order
    {
        return Order::query()
            ->where('event_id', $event->id)
            ->where('user_id', $userId)
            ->where('source', 'private_event')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }
}
