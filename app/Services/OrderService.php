<?php

namespace App\Services;

use App\Jobs\DeliverPaidOrderTickets;
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

        if ($event->isExpired()) {
            throw ValidationException::withMessages([
                'event' => [__('ui.event_expired_hint')],
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
            $isFreeEvent = $event->isFreeEvent();
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

                $unit = $isFreeEvent ? 0.0 : (float) $type->price;
                $lineSubtotal = round($unit * $qty, 2);
                $subtotal += $lineSubtotal;
                $lineItems[] = [
                    'type' => $type,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $serviceFee = $isFreeEvent ? 0.0 : (float) Setting::getValue('service_fee', 1);
            $total = round($subtotal + $serviceFee, 2);
            $commissionRate = $isFreeEvent ? 0.0 : $this->commissionRateFor($event);
            $commission = $isFreeEvent ? 0.0 : round($subtotal * ($commissionRate / 100), 2);

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
                'status' => $isFreeEvent ? 'paid' : 'pending',
                'payment_method' => $isFreeEvent ? null : ($buyer['payment_method'] ?? null),
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

            $order = $order->load(['items.ticketType', 'event', 'payment']);

            if ($isFreeEvent) {
                $this->fulfillPaidOrder($order);
                $paid = $order->fresh()->load(['items.ticketType', 'items.tickets', 'event', 'payment']);
                $this->queueTicketDelivery($paid);
                $this->notifyOrganizerTicketSale($paid);

                return $paid;
            }

            return $order;
        });
    }

    public function pay(Order $order, string $paymentMethod, ?string $phone = null, bool $forceFail = false, ?string $walletPin = null): Order
    {
        if (! self::allowsForceFail()) {
            $forceFail = false;
        }

        if ($paymentMethod !== 'waafipay') {
            throw ValidationException::withMessages([
                'payment_method' => ['Pay with WaafiPay.'],
            ]);
        }

        $prepared = $this->prepareCharge($order, $paymentMethod, $phone);

        /** @var Order $order */
        $order = $prepared['order'];
        if ($order->status === 'paid') {
            return $this->loadedOrder($order);
        }

        $eventTitle = $order->event?->title ?? 'tickets';
        $chargePhone = $phone ?: $order->buyer_phone;
        $reference = $prepared['reference'];

        if ($prepared['action'] === 'inquire') {
            $result = $this->gateway()->inquire($reference, $order->payment?->transaction_id);
            if (in_array($result['status'], ['pending', 'unknown'], true)) {
                return $this->applyGatewayResult($order, $paymentMethod, $chargePhone, [
                    'status' => 'pending',
                    'transaction_id' => $result['transaction_id'] ?: ($order->payment?->transaction_id ?: $reference),
                    'message' => $result['message'] ?: __('ui.payment_confirming'),
                    'raw' => is_array($result['raw'] ?? null) ? $result['raw'] : [],
                ]);
            }
        } else {
            $result = $this->gateway()->initiate(
                (float) $order->total_amount,
                $reference,
                [
                    'phone' => $chargePhone,
                    'force_fail' => $forceFail,
                    'pin' => $walletPin,
                    'description' => 'Ekaadh: '.$eventTitle.' '.$order->order_number,
                ]
            );
        }

        return $this->applyGatewayResult($order, $paymentMethod, $chargePhone, $result);
    }

    public function reconcile(Order $order): Order
    {
        $order->loadMissing('payment');

        if ($order->status === 'paid') {
            return $this->loadedOrder($order);
        }

        if ($order->status !== 'pending' || ! $order->payment || $order->payment->status !== 'initiated') {
            return $this->loadedOrder($order);
        }

        $reference = $this->chargeReference($order);
        $result = $this->gateway()->inquire($reference, $order->payment->transaction_id);

        if (in_array($result['status'], ['pending', 'unknown'], true)) {
            return $this->loadedOrder($order);
        }

        return $this->applyGatewayResult($order, (string) ($order->payment_method ?: 'waafipay'), $order->buyer_phone, $result);
    }

    /**
     * @return array{order: Order, action: string, reference: string}
     */
    private function prepareCharge(Order $order, string $paymentMethod, ?string $phone): array
    {
        return DB::transaction(function () use ($order, $paymentMethod, $phone) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->loadMissing(['event', 'payment', 'items']);

            if ($order->status === 'paid') {
                return ['order' => $order, 'action' => 'none', 'reference' => $this->chargeReference($order)];
            }

            if (! in_array($order->status, ['pending', 'failed'], true)) {
                throw ValidationException::withMessages([
                    'order' => ['This order cannot be paid.'],
                ]);
            }

            $existingRef = $this->chargeReference($order);
            $inFlight = $order->status === 'pending'
                && $order->payment
                && $order->payment->status === 'initiated'
                && $existingRef !== '';

            if ($inFlight) {
                return ['order' => $order, 'action' => 'inquire', 'reference' => $existingRef];
            }

            $attempt = (int) (($order->payment?->raw_response ?? [])['attempt'] ?? 0) + 1;
            $reference = $attempt === 1
                ? $order->order_number
                : $order->order_number.'-R'.$attempt;

            $this->assertAndReserveStock($order);

            $raw = [
                'charge_reference' => $reference,
                'attempt' => $attempt,
                    'stock_reserved' => ! $this->skipsTicketStock($order),
                'result' => 'INITIATED',
            ];

            $this->upsertPayment($order, 'initiated', $reference, $phone, $raw);

            $order->update([
                'payment_method' => $paymentMethod,
                'payment_reference' => $reference,
                'status' => 'pending',
            ]);

            return [
                'order' => $order->fresh()->load(['event', 'payment', 'items.ticketType']),
                'action' => 'initiate',
                'reference' => $reference,
            ];
        });
    }

    /**
     * @param  array{status: string, transaction_id: string, message: string, raw?: array}  $result
     */
    private function applyGatewayResult(Order $order, string $paymentMethod, ?string $phone, array $result): Order
    {
        return DB::transaction(function () use ($order, $paymentMethod, $phone, $result) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->loadMissing(['event', 'payment', 'items.ticketType', 'items.tickets']);

            if ($order->status === 'paid') {
                return $this->loadedOrder($order);
            }

            $status = (string) ($result['status'] ?? 'failed');
            if ($status === 'unknown') {
                $status = 'pending';
            }

            $paymentStatus = match ($status) {
                'success' => 'success',
                'pending' => 'initiated',
                default => 'failed',
            };
            $orderStatus = match ($status) {
                'success' => 'paid',
                'pending' => 'pending',
                default => 'failed',
            };

            $raw = is_array($result['raw'] ?? null) ? $result['raw'] : [];
            $previous = is_array($order->payment?->raw_response) ? $order->payment->raw_response : [];
            $raw = array_merge($previous, $raw);
            $raw['charge_reference'] = $previous['charge_reference'] ?? $this->chargeReference($order);
            $raw['attempt'] = $previous['attempt'] ?? 1;
            $raw['stock_reserved'] = (bool) ($previous['stock_reserved'] ?? false);
            $raw['stock_committed'] = (bool) ($previous['stock_committed'] ?? false);
            if (! empty($result['message'])) {
                $raw['user_message'] = (string) $result['message'];
            }

            $this->upsertPayment($order, $paymentStatus, $result['transaction_id'] ?? null, $phone, $raw);

            $order->update([
                'payment_method' => $paymentMethod,
                'payment_reference' => $result['transaction_id'] ?? $order->payment_reference,
                'status' => $orderStatus,
            ]);

            $order = $order->fresh()->load(['event', 'payment', 'items.ticketType', 'items.tickets']);

            if ($status === 'success') {
                $this->commitReservedStock($order);

                return $this->completePaidOrder($order->fresh());
            }

            if ($status === 'failed') {
                $this->releaseReservedStock($order);
            }

            return $this->loadedOrder($order->fresh());
        });
    }

    private function completePaidOrder(Order $order): Order
    {
        $order->loadMissing(['items.ticketType', 'items.tickets', 'event']);

        if ($order->source === 'private_event') {
            if ($order->items->flatMap->tickets->isEmpty()) {
                app(PrivateEventService::class)->fulfillCapacityPurchase($order->load('items.ticketType'));
            }
            $paid = $order->fresh()->load(['items.ticketType', 'items.tickets', 'event', 'payment']);
            $this->notifyPrivateEventPaid($paid);

            return $paid;
        }

        if ($order->source === 'organizer_package') {
            app(OrganizerEventPackageService::class)->fulfill($order->load('event'));

            return $order->fresh()->load(['items.ticketType', 'items.tickets', 'event', 'payment']);
        }

        if ($order->items->flatMap->tickets->isEmpty()) {
            $this->fulfillPaidOrder($order->load('items.ticketType'));
            $paid = $order->fresh()->load(['items.ticketType', 'items.tickets', 'event', 'payment']);
            $this->queueTicketDelivery($paid);
            $this->notifyOrganizerTicketSale($paid);

            return $paid;
        }

        return $order->fresh()->load(['items.ticketType', 'items.tickets', 'event', 'payment']);
    }

    public function expireStaleInitiated(Order $order, int $minutes): Order
    {
        $order->loadMissing('payment');
        if ($order->status !== 'pending' || $order->payment?->status !== 'initiated') {
            return $this->loadedOrder($order);
        }

        $cutoff = now()->subMinutes(max(1, $minutes));
        if ($order->payment->updated_at->gt($cutoff)) {
            return $this->loadedOrder($order);
        }

        return DB::transaction(function () use ($order) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing(['payment', 'items']);

            if ($locked->status !== 'pending' || $locked->payment?->status !== 'initiated') {
                return $this->loadedOrder($locked);
            }

            $this->releaseReservedStock($locked);
            $raw = is_array($locked->payment?->raw_response) ? $locked->payment->raw_response : [];
            $raw['expired_pending'] = true;
            $raw['user_message'] = 'Payment was not confirmed in time. Please try again.';
            $this->upsertPayment(
                $locked,
                'failed',
                $locked->payment?->transaction_id,
                $locked->buyer_phone,
                $raw
            );
            $locked->update(['status' => 'failed']);

            return $this->loadedOrder($locked->fresh());
        });
    }

    private function queueTicketDelivery(Order $order): void
    {
        $orderId = $order->id;
        DB::afterCommit(function () use ($orderId) {
            DeliverPaidOrderTickets::dispatch($orderId);
        });
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function upsertPayment(Order $order, string $status, ?string $transactionId, ?string $phone, array $raw): void
    {
        $order->loadMissing('payment');
        $attrs = [
            'transaction_id' => $transactionId,
            'phone_number' => $phone ?: $order->buyer_phone,
            'amount' => $order->total_amount,
            'status' => $status,
            'raw_response' => $raw,
        ];

        if ($order->payment) {
            $order->payment->update($attrs);

            return;
        }

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => $this->gateway()->name(),
            ...$attrs,
        ]);
    }

    public function fulfillPaidOrder(Order $order): void
    {
        $committed = (bool) (($order->payment?->raw_response ?? [])['stock_committed'] ?? false);

        foreach ($order->items()->with('ticketType')->get() as $item) {
            $type = TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->firstOrFail();
            if (! $committed) {
                $type->increment('quantity_sold', $item->quantity);
            }

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

    private function assertAndReserveStock(Order $order): void
    {
        if ($this->skipsTicketStock($order)) {
            return;
        }
        $raw = is_array($order->payment?->raw_response) ? $order->payment->raw_response : [];
        if (! empty($raw['stock_reserved']) && empty($raw['stock_committed'])) {
            return;
        }

        foreach ($order->items()->with('ticketType')->get() as $item) {
            $type = TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->firstOrFail();
            if ($item->quantity > $type->remaining()) {
                throw ValidationException::withMessages([
                    'items' => ["Not enough {$type->name} tickets remaining."],
                ]);
            }
        }

        foreach ($order->items as $item) {
            TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->increment('quantity_sold', $item->quantity);
        }
    }

    private function commitReservedStock(Order $order): void
    {
        if ($this->skipsTicketStock($order)) {
            return;
        }
        $order->loadMissing('payment');
        $raw = is_array($order->payment?->raw_response) ? $order->payment->raw_response : [];
        $raw['stock_reserved'] = true;
        $raw['stock_committed'] = true;
        $order->payment?->update(['raw_response' => $raw]);
    }

    private function releaseReservedStock(Order $order): void
    {
        if ($this->skipsTicketStock($order)) {
            return;
        }
        $order->loadMissing(['payment', 'items']);
        $raw = is_array($order->payment?->raw_response) ? $order->payment->raw_response : [];
        if (empty($raw['stock_reserved']) || ! empty($raw['stock_committed'])) {
            return;
        }

        foreach ($order->items as $item) {
            TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->decrement('quantity_sold', $item->quantity);
        }

        $raw['stock_reserved'] = false;
        $order->payment?->update(['raw_response' => $raw]);
    }

    private function skipsTicketStock(Order $order): bool
    {
        return in_array($order->source, ['private_event', 'organizer_package'], true);
    }

    private function chargeReference(Order $order): string
    {
        $raw = is_array($order->payment?->raw_response) ? $order->payment->raw_response : [];
        $stored = trim((string) ($raw['charge_reference'] ?? ''));

        return $stored !== '' ? $stored : (string) $order->order_number;
    }

    private function loadedOrder(Order $order): Order
    {
        return $order->load(['items.ticketType', 'event.ticketTypes', 'payment', 'items.tickets']);
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
            $number = 'EKD-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function nextTicketCode(): string
    {
        do {
            $code = 'EKD-'.strtoupper(bin2hex(random_bytes(8)));
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
            true,
        );
    }

    private function notifyOrganizerTicketSale(Order $order): void
    {
        if (in_array($order->source, ['invitation', 'private_event', 'organizer_package'], true)) {
            return;
        }

        $order->loadMissing(['items', 'event.organizer.user']);
        $user = $order->event?->organizer?->user;
        if (! $user) {
            return;
        }

        $eventTitle = $order->event?->title ?: 'your event';
        $qty = (int) $order->items->sum('quantity');

        app(PanelNotifier::class)->toUser(
            $user,
            'New ticket sale',
            "{$order->buyer_name} claimed {$qty} ticket(s) for {$eventTitle}.",
            'ticket_sale',
            route('organizer.earnings'),
            [
                'event_id' => (string) $order->event_id,
                'order_number' => (string) $order->order_number,
            ],
        );
    }
}
