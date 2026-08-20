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
            ->with(['items.ticketType', 'event.package'])
            ->where('event_id', $event->id)
            ->where('source', 'organizer_package')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    public function pendingOrCreate(Event $event, User $organizer): Order
    {
        $event->loadMissing(['package', 'ticketTypes', 'organizer']);

        if (! $event->isFreeEvent()) {
            throw ValidationException::withMessages([
                'event' => ['Only free events require a package payment.'],
            ]);
        }

        if ($event->packageIsPaid()) {
            throw ValidationException::withMessages([
                'event' => ['This event package is already paid.'],
            ]);
        }

        $package = $event->package;
        if (! $package || ! $package->isFreeEventPackage() || ! $package->is_active) {
            throw ValidationException::withMessages([
                'package_id' => ['Choose a valid free-event package.'],
            ]);
        }

        $amount = $package->chargeAmount();
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'package_id' => ['This package does not require payment.'],
            ]);
        }

        $pending = $this->pendingOrder($event);
        if ($pending && (float) $pending->total_amount === $amount) {
            return $pending;
        }

        return DB::transaction(function () use ($event, $organizer, $package, $amount, $pending) {
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

            return $order->load(['items.ticketType', 'event.package']);
        });
    }

    public function pay(Order $order, string $paymentMethod, ?string $phone = null, bool $forceFail = false, ?string $walletPin = null): Order
    {
        if ($order->source !== 'organizer_package') {
            throw ValidationException::withMessages([
                'order' => ['This order is not a free-event package purchase.'],
            ]);
        }

        return $this->orders->pay($order, $paymentMethod, $phone, $forceFail, $walletPin);
    }

    public function fulfill(Order $order): void
    {
        $order->loadMissing('event');
        $event = $order->event;
        if (! $event || ! $event->isFreeEvent()) {
            return;
        }

        $updates = ['package_paid_at' => $event->package_paid_at ?: now()];
        $after = session('event_package_after_pay.'.$event->id);
        if ($after === 'pending_review' && $event->status === 'draft') {
            $updates['status'] = 'pending_review';
        }

        $becameReview = ($updates['status'] ?? null) === 'pending_review';
        $event->update($updates);
        session()->forget('event_package_after_pay.'.$event->id);

        if ($becameReview) {
            app(PanelNotifier::class)->eventSubmittedForReview($event->fresh('organizer'));
        }
    }

    public function markComplimentaryPackagePaid(Event $event, string $afterStatus = 'draft'): void
    {
        $event->loadMissing('package');
        if (! $event->isFreeEvent() || $event->packageIsPaid()) {
            return;
        }

        if ($event->package && $event->package->chargeAmount() > 0) {
            return;
        }

        $updates = ['package_paid_at' => now()];
        if ($afterStatus === 'pending_review' && $event->status === 'draft') {
            $updates['status'] = 'pending_review';
        }
        $event->update($updates);
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
