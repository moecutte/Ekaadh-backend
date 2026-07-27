<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(
        private TicketDeliveryService $delivery,
    ) {}

    /**
     * Issue complimentary tickets and deliver invitation links.
     *
     * @param  array<int, array{name?: string|null, phone: string, quantity: int, ticket_type_id: int}>  $guests
     * @return array{created: int, invitations: \Illuminate\Support\Collection<int, EventInvitation>}
     */
    public function issueAndSend(Event $event, array $guests): array
    {
        if (! $event->is_private) {
            throw ValidationException::withMessages([
                'event' => ['Invitations are only available for private events.'],
            ]);
        }

        if ($event->status !== 'published') {
            throw ValidationException::withMessages([
                'event' => ['Publish the event before sending invitations.'],
            ]);
        }

        if ($guests === []) {
            throw ValidationException::withMessages([
                'guests' => ['Add at least one guest.'],
            ]);
        }

        $invitations = collect();

        DB::transaction(function () use ($event, $guests, &$invitations) {
            foreach ($guests as $index => $guest) {
                $phone = Phone::normalize($guest['phone'] ?? '');
                if ($phone === '') {
                    throw ValidationException::withMessages([
                        "guests.{$index}.phone" => ['Enter a valid phone number.'],
                    ]);
                }

                $qty = (int) ($guest['quantity'] ?? 1);
                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        "guests.{$index}.quantity" => ['Quantity must be at least 1.'],
                    ]);
                }

                /** @var TicketType $type */
                $type = TicketType::query()
                    ->where('event_id', $event->id)
                    ->whereKey($guest['ticket_type_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($qty > $type->max_per_order) {
                    throw ValidationException::withMessages([
                        "guests.{$index}.quantity" => ["Max {$type->max_per_order} tickets for {$type->name}."],
                    ]);
                }

                if ($qty > $type->remaining()) {
                    throw ValidationException::withMessages([
                        "guests.{$index}.quantity" => ["Not enough {$type->name} tickets remaining."],
                    ]);
                }

                $name = trim((string) ($guest['name'] ?? '')) ?: 'Guest';

                $order = Order::query()->create([
                    'user_id' => null,
                    'event_id' => $event->id,
                    'order_number' => $this->nextOrderNumber(),
                    'buyer_name' => $name,
                    'buyer_email' => null,
                    'buyer_phone' => $phone,
                    'subtotal' => 0,
                    'service_fee' => 0,
                    'total_amount' => 0,
                    'commission_amount' => 0,
                    'status' => 'paid',
                    'payment_method' => null,
                    'payment_reference' => null,
                    'source' => 'invitation',
                ]);

                $item = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'ticket_type_id' => $type->id,
                    'quantity' => $qty,
                    'unit_price' => 0,
                    'subtotal' => 0,
                ]);

                $invitation = EventInvitation::query()->create([
                    'event_id' => $event->id,
                    'order_id' => $order->id,
                    'ticket_type_id' => $type->id,
                    'guest_name' => $name,
                    'guest_phone' => $phone,
                    'quantity' => $qty,
                    'token' => $this->nextToken(),
                    'status' => 'active',
                    'sms_status' => 'pending',
                    'whatsapp_status' => 'pending',
                ]);

                $type->increment('quantity_sold', $qty);

                for ($i = 0; $i < $qty; $i++) {
                    Ticket::query()->create([
                        'order_item_id' => $item->id,
                        'invitation_id' => $invitation->id,
                        'event_id' => $event->id,
                        'ticket_code' => $this->nextTicketCode(),
                        'holder_name' => $name,
                        'ticket_type_name' => $type->name,
                        'status' => 'valid',
                    ]);
                }

                $invitations->push($invitation->fresh(['tickets', 'event', 'ticketType']));
            }
        });

        foreach ($invitations as $invitation) {
            $this->delivery->sendForInvitation($invitation);
        }

        return [
            'created' => $invitations->count(),
            'invitations' => $invitations->map(
                fn (EventInvitation $i) => $i->fresh(['tickets', 'event', 'ticketType'])
            ),
        ];
    }

    public function resend(EventInvitation $invitation): EventInvitation
    {
        if (! $invitation->isActive()) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation was revoked and cannot be resent.'],
            ]);
        }

        $this->delivery->sendForInvitation($invitation->loadMissing(['tickets', 'event', 'ticketType']));

        return $invitation->fresh(['tickets', 'event', 'ticketType']);
    }

    public function updatePhoneAndResend(EventInvitation $invitation, string $phone): EventInvitation
    {
        if (! $invitation->isActive()) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation was revoked.'],
            ]);
        }

        $normalized = Phone::normalize($phone);
        if ($normalized === '') {
            throw ValidationException::withMessages([
                'phone' => ['Enter a valid phone number.'],
            ]);
        }

        DB::transaction(function () use ($invitation, $normalized) {
            $invitation->update([
                'guest_phone' => $normalized,
                'token' => $this->nextToken(),
            ]);

            if ($invitation->order_id) {
                Order::query()->whereKey($invitation->order_id)->update([
                    'buyer_phone' => $normalized,
                ]);
            }
        });

        return $this->resend($invitation->fresh());
    }

    public function revoke(EventInvitation $invitation): EventInvitation
    {
        if (! $invitation->isActive()) {
            return $invitation;
        }

        return DB::transaction(function () use ($invitation) {
            /** @var EventInvitation $invitation */
            $invitation = EventInvitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if (! $invitation->isActive()) {
                return $invitation;
            }

            $cancellable = Ticket::query()
                ->where('invitation_id', $invitation->id)
                ->where('status', 'valid')
                ->lockForUpdate()
                ->get();

            $freed = $cancellable->count();

            foreach ($cancellable as $ticket) {
                $ticket->update(['status' => 'cancelled']);
            }

            if ($freed > 0) {
                TicketType::query()
                    ->whereKey($invitation->ticket_type_id)
                    ->lockForUpdate()
                    ->first()
                    ?->decrement('quantity_sold', $freed);
            }

            if ($invitation->order_id) {
                Order::query()->whereKey($invitation->order_id)->update(['status' => 'cancelled']);
            }

            $invitation->update([
                'status' => 'revoked',
                'revoked_at' => now(),
            ]);

            return $invitation->fresh(['tickets', 'event', 'ticketType']);
        });
    }

    public function markOpened(EventInvitation $invitation): void
    {
        if ($invitation->opened_at) {
            return;
        }

        $invitation->update(['opened_at' => now()]);
    }

    private function nextToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (EventInvitation::query()->where('token', $token)->exists());

        return $token;
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'EKD-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function nextTicketCode(): string
    {
        do {
            $code = 'EKD-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Ticket::query()->where('ticket_code', $code)->exists());

        return $code;
    }
}
