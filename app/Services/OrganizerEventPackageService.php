<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizerEventPackageService
{
    public function __construct(private OrderService $orders) {}

    public function pendingOrder(Event $event): ?Order
    {
        return Order::query()
            ->with(['items.ticketType', 'event'])
            ->where('event_id', $event->id)
            ->where('source', 'organizer_package')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    public function pendingOrCreate(Event $event, User $organizer): Order
    {
        $event->loadMissing(['ticketTypes', 'organizer']);

        if (! $event->isFreeEvent() || $event->is_private) {
            throw ValidationException::withMessages([
                'event' => ['Only free public events require this payment.'],
            ]);
        }

        if ($event->status !== 'published') {
            throw ValidationException::withMessages([
                'event' => ['Wait for admin approval before paying for this free event.'],
            ]);
        }

        if ($event->packageIsPaid()) {
            throw ValidationException::withMessages([
                'event' => ['This free event is already paid (or free of charge).'],
            ]);
        }

        $amount = $event->freeEventChargeAmount();
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'event' => ['This free event does not require payment.'],
            ]);
        }

        $pending = $this->pendingOrder($event);
        if ($pending && abs((float) $pending->total_amount - $amount) < 0.001) {
            return $pending;
        }

        return DB::transaction(function () use ($event, $organizer, $amount, $pending) {
            if ($pending) {
                $pending->update(['status' => 'cancelled']);
            }

            $type = $event->ticketTypes->first();
            if (! $type) {
                throw ValidationException::withMessages([
                    'tickets' => ['Add at least one ticket type before paying for this event.'],
                ]);
            }

            $phone = Phone::normalize($organizer->phone ?: $event->organizer?->business_phone);
            if ($phone === '') {
                throw ValidationException::withMessages([
                    'phone' => ['Add a phone number to your organizer account before paying.'],
                ]);
            }

            $order = Order::query()->create([
                'user_id' => $organizer->id,
                'event_id' => $event->id,
                'order_number' => $this->nextOrderNumber(),
                'buyer_name' => $organizer->name,
                'buyer_email' => $this->organizerEmail($organizer),
                'buyer_phone' => $phone,
                'subtotal' => $amount,
                'service_fee' => 0,
                'total_amount' => $amount,
                'commission_amount' => $amount,
                'status' => 'pending',
                'payment_method' => null,
                'payment_reference' => null,
                'source' => 'organizer_package',
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'ticket_type_id' => $type->id,
                'quantity' => 1,
                'unit_price' => $amount,
                'subtotal' => $amount,
            ]);

            return $order->load(['items.ticketType', 'event']);
        });
    }

    public function pay(Order $order, string $paymentMethod, ?string $phone = null, bool $forceFail = false, ?string $walletPin = null): Order
    {
        if ($order->source !== 'organizer_package') {
            throw ValidationException::withMessages([
                'order' => ['This order is not a free-event capacity payment.'],
            ]);
        }

        return $this->orders->pay($order, $paymentMethod, $phone, $forceFail, $walletPin);
    }

    public function fulfill(Order $order): void
    {
        $order->loadMissing('event.ticketTypes');
        $event = $order->event;
        if (! $event || ! $event->isFreeEvent()) {
            return;
        }

        $event->update([
            'package_paid_at' => $event->package_paid_at ?: now(),
        ]);

        $event = $event->fresh(['ticketTypes']);
        app(InvitationService::class)->flushPending($event);

        $user = $event->organizer?->user;
        if (! $user) {
            $event->loadMissing('organizer.user');
            $user = $event->organizer?->user;
        }
        if ($user) {
            app(PanelNotifier::class)->toUser(
                $user,
                'Free event is live',
                "{$event->title} payment received. Your event is now live.",
                'event_published',
                route('organizer.events.index'),
                ['event_id' => (string) $event->id],
            );
        }
    }

    public function markComplimentaryPackagePaid(Event $event): void
    {
        if (! $event->isFreeEvent() || $event->packageIsPaid()) {
            return;
        }

        $event->update(['package_paid_at' => now()]);
    }

    private function organizerEmail(User $organizer): ?string
    {
        $email = $organizer->email;
        if (! $email || str_ends_with(strtolower($email), '@ekaadh.local')) {
            return null;
        }

        return $email;
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'EKD-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
