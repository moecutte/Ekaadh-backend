<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventInvitationResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PrivateEventResource;
use App\Models\City;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\InvitationDesign;
use App\Models\Category;
use App\Support\InvitationPreview;
use App\Services\InvitationService;
use App\Services\OrderService;
use App\Services\PrivateEventService;
use App\Services\Payments\WaafiPayGateway;
use App\Support\PaymentMessage;
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

    public function invitationPreview(Request $request)
    {
        $this->customer();

        $design = InvitationDesign::query()
            ->with('fields')
            ->findOrFail($request->integer('invitation_design_id'));

        abort_unless($design->is_active, 404);

        $fields = $request->input('fields', []);
        if (! is_array($fields)) {
            $fields = [];
        }

        $preview = InvitationPreview::make($design, $fields, [
            'event_date' => $request->input('event_date'),
            'event_time' => $request->input('event_time'),
            'venue' => $request->input('venue'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'guest_name' => $request->input('guest_name', ''),
        ]);

        return response()->view('invitations.preview-frame', $preview + [
            'showQr' => $request->boolean('show_qr', false),
            'withEnvelope' => $request->boolean('envelope', true),
            'autoOpen' => $request->boolean('auto_open', true),
            'compact' => $request->boolean('compact', false),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->customer();

        $events = Event::query()
            ->with(['ticketTypes', 'privateEventCategory', 'pendingPrivateOrder.items.ticketType', 'pendingPrivateOrder.event', 'pendingPrivateOrder.payment'])
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

        $event = $result['event']->load([
            'ticketTypes',
            'privateEventCategory',
            'pendingPrivateOrder.items.ticketType',
            'pendingPrivateOrder.event',
            'pendingPrivateOrder.payment',
        ]);

        return response()->json([
            'message' => 'Private event created. Complete payment to unlock invitations.',
            'event' => new PrivateEventResource($event),
            'order' => new OrderResource($result['order']->load(['items.ticketType', 'event', 'payment'])),
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        $event->load('ticketTypes');
        if ($event->status === 'draft') {
            $this->privateEvents->ensurePendingOrder($event, $user);
        }
        $event->load([
            'pendingPrivateOrder.items.ticketType',
            'pendingPrivateOrder.event',
            'pendingPrivateOrder.payment',
        ]);

        return response()->json([
            'data' => new PrivateEventResource($event),
        ]);
    }

    public function pay(Request $request, Event $event): JsonResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        $data = $request->validate([
            'payment_method' => ['required', 'in:waafipay'],
            'force_fail' => ['sometimes', 'boolean'],
            'wallet_pin' => ['nullable', 'string', 'max:8'],
            'buyer_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $order = $event->status === 'draft'
            ? $this->privateEvents->ensurePendingOrder($event, $user)
            : $this->privateEvents->pendingOrder($event, $user->id);
        if (! $order) {
            return response()->json([
                'message' => 'No pending payment for this private event.',
            ], 404);
        }

        $walletPin = WaafiPayGateway::sandboxPin($data['wallet_pin'] ?? null);
        if (config('waafipay.sandbox') && $walletPin === null) {
            throw ValidationException::withMessages([
                'wallet_pin' => [WaafiPayGateway::sandboxPinError($data['wallet_pin'] ?? null)],
            ]);
        }

        $chargePhone = Phone::normalize($user->phone);
        if (config('waafipay.sandbox')) {
            $chargePhone = Phone::normalize($data['buyer_phone'] ?? '');
            if ($chargePhone === '') {
                throw ValidationException::withMessages([
                    'buyer_phone' => [__('ui.sandbox_charge_phone_required')],
                ]);
            }
        }

        $order = $this->privateEvents->payCapacityOrder(
            $order,
            $data['payment_method'],
            $chargePhone,
            OrderService::allowsForceFail() && $request->boolean('force_fail'),
            $walletPin
        );

        $event = $event->fresh()->load('ticketTypes');

        if ($order->status === 'paid') {
            return response()->json([
                'message' => 'Payment successful. You can send invitations now.',
                'event' => new PrivateEventResource($event),
                'order' => new OrderResource($order->load(['items.ticketType', 'event', 'payment'])),
            ]);
        }

        if ($order->status === 'pending') {
            return response()->json([
                'message' => 'Payment is being confirmed.',
                'event' => new PrivateEventResource($event),
                'order' => new OrderResource($order->load(['items.ticketType', 'event', 'payment'])),
            ], 202);
        }

        return response()->json([
            'message' => PaymentMessage::forOrder($order),
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
        $event = $result['event']->load([
            'ticketTypes',
            'pendingPrivateOrder.items.ticketType',
            'pendingPrivateOrder.event',
            'pendingPrivateOrder.payment',
        ]);

        return response()->json([
            'message' => 'Pay to add more invitation tickets.',
            'event' => new PrivateEventResource($event),
            'order' => new OrderResource($result['order']->load(['items.ticketType', 'event', 'payment'])),
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
            'channel' => ['required', 'in:sms,whatsapp'],
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

        $result = $invitations->issueAndSend($event, $guests, $data['channel']);

        return response()->json([
            'message' => "Sent {$result['created']} invitation(s).",
            'created' => $result['created'],
            'invitations' => EventInvitationResource::collection($result['invitations']),
        ], 201);
    }

    public function resendInvitation(Request $request, Event $event, EventInvitation $invitation, InvitationService $invitations): JsonResponse
    {
        $this->authorizeInvitation($event, $invitation);
        $data = $request->validate([
            'channel' => ['nullable', 'in:sms,whatsapp'],
        ]);
        $invitation = $invitations->resend($invitation, $data['channel'] ?? null);

        return response()->json([
            'message' => 'Invitation resent.',
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
}
