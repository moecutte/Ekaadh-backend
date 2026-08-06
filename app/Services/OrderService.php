<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrganizerProfile;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function gateway(): PaymentGatewayInterface
    {
        return app(PaymentGatewayInterface::class);
    }

    public static function allowsForceFail(): bool
    {
        return app()->environment('local') && (bool) config('app.debug');
    }

    /**
     * @param  array<int, array{ticket_type_id: int, quantity: int}>  $items
     */
    public function createCheckout(Event $event, array $buyer, array $items, ?User $user = null): Order
    {
        if ($event->status !== 'published') {
            throw ValidationException::withMessages([
                'event' => ['This event is not available for purchase.'],
            ]);
        }

        if ($event->is_private) {
            throw ValidationException::withMessages([
                'event' => ['This is a private invitation-only event. Tickets are sent by the organizer.'],
            ]);
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => ['Select at least one ticket.'],
            ]);
        }

        return DB::transaction(function () use ($event, $buyer, $items, $user) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($items as $row) {
                /** @var TicketType $type */
                $type = TicketType::query()
                    ->where('event_id', $event->id)
                    ->whereKey($row['ticket_type_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $qty = (int) $row['quantity'];
                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'items' => ['Quantity must be at least 1.'],
                    ]);
                }

                if ($qty > $type->max_per_order) {
                    throw ValidationException::withMessages([
                        'items' => ["Max {$type->max_per_order} tickets for {$type->name}."],
                    ]);
                }

                if ($qty > $type->remaining()) {
                    throw ValidationException::withMessages([
                        'items' => ["Not enough {$type->name} tickets remaining."],
                    ]);
                }

                $lineSubtotal = round(((float) $type->price) * $qty, 2);
                $subtotal += $lineSubtotal;
                $lineItems[] = [
                    'type' => $type,
                    'quantity' => $qty,
                    'unit_price' => (float) $type->price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $serviceFee = (float) Setting::getValue('service_fee', 1);
            $total = round($subtotal + $serviceFee, 2);
            $commissionRate = $this->commissionRateFor($event);
            $commission = round($subtotal * ($commissionRate / 100), 2);

            $order = Order::query()->create([
                'user_id' => $user?->id,
                'event_id' => $event->id,
                'order_number' => $this->nextOrderNumber(),
                'buyer_name' => $buyer['name'],
                'buyer_email' => $buyer['email'] ?? null,
                'buyer_phone' => $buyer['phone'],
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'total_amount' => $total,
                'commission_amount' => $commission,
                'status' => 'pending',
                'payment_method' => $buyer['payment_method'] ?? null,
            ]);

            foreach ($lineItems as $line) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'ticket_type_id' => $line['type']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            return $order->load(['items.ticketType', 'event', 'payment']);
        });
    }

    public function pay(Order $order, string $paymentMethod, ?string $phone = null, bool $forceFail = false): Order
    {
        if (! self::allowsForceFail()) {
            $forceFail = false;
        }

        if ($order->status === 'paid') {
            return $order->load(['items.ticketType', 'event.ticketTypes', 'payment', 'items.tickets']);
        }

        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'order' => ['This order cannot be paid.'],
            ]);
        }

        if (! in_array($paymentMethod, ['zaad', 'edahab'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => ['Choose Zaad or eDahab.'],
            ]);
        }

        return DB::transaction(function () use ($order, $paymentMethod, $phone, $forceFail) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === 'paid') {
                return $order->load(['items.ticketType', 'event', 'payment', 'items.tickets']);
            }

            // Re-check stock before charging
            foreach ($order->items()->with('ticketType')->get() as $item) {
                $type = TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->firstOrFail();
                if ($item->quantity > $type->remaining()) {
                    $order->update(['status' => 'failed']);
                    throw ValidationException::withMessages([
                        'items' => ["Not enough {$type->name} tickets remaining."],
                    ]);
                }
            }

            $result = $this->gateway()->initiate(
                (float) $order->total_amount,
                $order->order_number,
                [
                    'phone' => $phone ?: $order->buyer_phone,
                    'force_fail' => $forceFail,
                ]
            );

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider' => $paymentMethod,
                'transaction_id' => $result['transaction_id'],
                'phone_number' => $phone ?: $order->buyer_phone,
                'amount' => $order->total_amount,
                'status' => $result['status'] === 'success' ? 'success' : 'failed',
                'raw_response' => $result['raw'],
            ]);

            $order->update([
                'payment_method' => $paymentMethod,
                'payment_reference' => $result['transaction_id'],
                'status' => $result['status'] === 'success' ? 'paid' : 'failed',
            ]);

            if ($result['status'] === 'success') {
                if ($order->source === 'private_event') {
                    app(PrivateEventService::class)->fulfillCapacityPurchase($order->load('items.ticketType'));
                    $paid = $order->fresh()->load(['items.ticketType', 'items.tickets', 'event', 'payment']);
                    $this->notifyPrivateEventPaid($paid);
                } else {
                    $this->fulfillPaidOrder($order->load('items.ticketType'));
                    $paid = $order->fresh()->load(['items.ticketType', 'items.tickets', 'event', 'payment']);
                    app(TicketDeliveryService::class)->sendForOrder($paid);
                }

                return $paid;
            }

            return $order->fresh()->load(['items.ticketType', 'event', 'payment', 'items.tickets']);
        });
    }

    public function fulfillPaidOrder(Order $order): void
    {
        foreach ($order->items()->with('ticketType')->get() as $item) {
            $type = TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->firstOrFail();
            $type->increment('quantity_sold', $item->quantity);

            for ($i = 0; $i < $item->quantity; $i++) {
                Ticket::query()->create([
                    'order_item_id' => $item->id,
                    'event_id' => $order->event_id,
                    'ticket_code' => $this->nextTicketCode(),
                    'holder_name' => $order->buyer_name,
                    'ticket_type_name' => $type->name,
                    'status' => 'valid',
                ]);
            }
        }
    }

    private function commissionRateFor(Event $event): float
    {
        if (! $event->organizer_id) {
            return 100.0;
        }

        $organizer = OrganizerProfile::query()->with('package')->find($event->organizer_id);

        if (! $organizer) {
            return (float) Setting::getValue('default_commission_rate', 10);
        }

        return $organizer->effectiveCommissionRate();
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

    private function notifyPrivateEventPaid(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $user = User::query()->find($order->user_id);
        if (! $user) {
            return;
        }

        $title = $order->event?->title ?? 'your event';
        app(PushNotificationService::class)->sendToUser(
            $user,
            'Private event paid',
            "{$title} is paid. You can now send invitations from Ekaadh.",
            PushNotificationService::TYPE_PRIVATE_EVENT_PAID,
            [
                'event_id' => (string) $order->event_id,
                'order_number' => (string) $order->order_number,
            ],
        );
    }
}
