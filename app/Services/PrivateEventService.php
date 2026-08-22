<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Event;
use App\Models\InvitationDesign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\TicketType;
use App\Models\User;
use App\Support\Phone;
use App\Support\TicketDesigns;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrivateEventService
{
    public function __construct(private OrderService $orders) {}

    public static function unitPrice(): float
    {
        return (float) Setting::getValue('private_ticket_price', 5);
    }

    public static function maxTickets(): int
    {
        return max(1, (int) Setting::getValue('private_ticket_max', 500));
    }

    public static function serviceFee(): float
    {
        return (float) Setting::getValue('service_fee', 1);
    }

    public static function premiumDesignSurcharge(): float
    {
        return (float) Setting::getValue('private_premium_design_surcharge', 2);
    }

    /** Unit price including premium design surcharge when applicable. */
    public static function unitPriceForDesign(?string $designId, ?InvitationDesign $design = null): float
    {
        if (! $design && $designId) {
            $design = InvitationDesign::query()->where('slug', $designId)->first();
        }

        if ($design) {
            return $design->unitPrice();
        }

        $base = self::unitPrice();
        if (TicketDesigns::isPremium($designId)) {
            return round($base + self::premiumDesignSurcharge(), 2);
        }

        return $base;
    }

    /**
     * Create a draft private event owned by the customer and a pending capacity order.
     *
     * @param  array{
     *     title?: string|null,
     *     description: string,
     *     venue: string,
     *     address?: string|null,
     *     city?: string|null,
     *     event_date: string,
     *     event_time: string,
     *     quantity: int,
     *     ticket_label?: string|null,
     *     ticket_design?: string|null,
     *     cover_image?: string|null,
     *     private_event_category_id: int,
     *     couple_name_1?: string|null,
     *     couple_name_2?: string|null
     * }  $data
     * @return array{event: Event, order: Order}
     */
    public function createWithCheckout(User $customer, array $data): array
    {
        if (! $customer->isCustomer()) {
            throw ValidationException::withMessages([
                'user' => ['Only customers can create private events.'],
            ]);
        }

        $qty = (int) $data['quantity'];
        $max = self::maxTickets();
        if ($qty < 1 || $qty > $max) {
            throw ValidationException::withMessages([
                'quantity' => ["Choose between 1 and {$max} tickets."],
            ]);
        }

        $designSlug = (string) ($data['ticket_design'] ?? '');
        $invitationDesign = null;

        if (! empty($data['invitation_design_id'])) {
            $invitationDesign = InvitationDesign::query()
                ->active()
                ->with('fields')
                ->find($data['invitation_design_id']);
        }

        if (! $invitationDesign && $designSlug !== '') {
            $invitationDesign = InvitationDesign::query()
                ->active()
                ->with('fields')
                ->where('slug', $designSlug)
                ->first();
        }

        if (! $invitationDesign) {
            throw ValidationException::withMessages([
                'invitation_design_id' => ['Choose a valid invitation design.'],
            ]);
        }

        $category = Category::query()
            ->active()
            ->find($data['private_event_category_id'] ?? null);

        $privateRootId = Category::privateRoot()?->id;
        if (! $category || ! $privateRootId || (int) $category->parent_id !== (int) $privateRootId) {
            throw ValidationException::withMessages([
                'private_event_category_id' => ['Choose a valid private event category.'],
            ]);
        }

        if ((int) $invitationDesign->private_event_category_id !== (int) $category->id) {
            throw ValidationException::withMessages([
                'invitation_design_id' => ['That design is not available for the selected category.'],
            ]);
        }

        $designSlug = $invitationDesign->slug;
        $fieldValues = is_array($data['invitation_field_values'] ?? null)
            ? $data['invitation_field_values']
            : [];

        $errors = [];
        $normalizedValues = [];

        foreach ($invitationDesign->fields as $field) {
            if ($field->field_type === 'qr') {
                continue;
            }

            $key = $field->field_key;
            $raw = trim((string) ($fieldValues[$key] ?? ''));

            // Date/time parts are filled from the event date/time below — not required as typed input.
            if ($field->is_required && $raw === '' && ! \App\Support\InvitationDateFields::isAutoType($field->field_type)) {
                $errors["invitation_field_values.{$key}"] = ["{$field->label} is required."];
            }

            if ($raw !== '' || $field->default_text) {
                $normalizedValues[$key] = $raw !== '' ? $raw : (string) $field->default_text;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $venue = trim((string) ($normalizedValues['venue'] ?? $normalizedValues['venue_line'] ?? ''));
        $eventDate = trim((string) ($data['event_date'] ?? $normalizedValues['event_date'] ?? ''));
        $eventTime = trim((string) ($data['event_time'] ?? $normalizedValues['event_time'] ?? '18:00'));

        if ($eventDate === '' || ! strtotime($eventDate)) {
            $eventDate = now()->addMonth()->toDateString();
        }
        if ($eventTime === '') {
            $eventTime = '18:00';
        }

        $normalizedValues = \App\Support\InvitationDateFields::applyToValues(
            $invitationDesign->fields,
            $normalizedValues,
            $eventDate,
            $eventTime
        );

        // Keep canonical keys for event columns / legacy matching.
        $normalizedValues['event_date'] = $eventDate;
        $normalizedValues['event_time'] = $eventTime;

        $title = trim((string) ($data['title'] ?? $normalizedValues['title'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($normalizedValues['couple_name_1'] ?? ''));
            $name2 = trim((string) ($normalizedValues['couple_name_2'] ?? ''));
            if ($title !== '' && $name2 !== '') {
                $title = $title.' & '.$name2;
            } elseif ($name2 !== '') {
                $title = $name2;
            }
        }
        if ($title === '') {
            $title = $category->name.' event';
        }

        $unit = self::unitPriceForDesign($designSlug, $invitationDesign);
        $label = trim((string) ($data['ticket_label'] ?? '')) ?: 'Invitation';
        $couple1 = trim((string) ($normalizedValues['couple_name_1'] ?? ''));
        $couple2 = trim((string) ($normalizedValues['couple_name_2'] ?? ''));

        return DB::transaction(function () use ($customer, $data, $qty, $unit, $label, $designSlug, $category, $title, $couple1, $couple2, $invitationDesign, $normalizedValues, $venue, $eventDate, $eventTime) {
            $event = Event::query()->create([
                'organizer_id' => null,
                'owner_user_id' => $customer->id,
                'title' => $title,
                'slug' => $this->uniqueSlug($title),
                'description' => $data['description'],
                'category' => 'Private',
                'venue' => $venue !== '' ? $venue : 'See invitation',
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'event_date' => $eventDate,
                'event_time' => $eventTime,
                'cover_image' => $data['cover_image'] ?? null,
                'is_featured' => false,
                'is_private' => true,
                'ticket_design' => $designSlug,
                'invitation_design_id' => $invitationDesign->id,
                'invitation_field_values' => $normalizedValues,
                'private_event_category_id' => $category->id,
                'couple_name_1' => $couple1 !== '' ? $couple1 : null,
                'couple_name_2' => $couple2 !== '' ? $couple2 : null,
                'status' => 'draft',
            ]);

            $type = TicketType::query()->create([
                'event_id' => $event->id,
                'name' => $label,
                'description' => 'Prepaid invitation tickets',
                'price' => $unit,
                'quantity_available' => $qty,
                'quantity_sold' => 0,
                'max_per_order' => min(20, max(1, $qty)),
            ]);

            $order = $this->buildCapacityOrder($event, $type, $qty, $unit, $customer);

            return [
                'event' => $event->fresh('ticketTypes'),
                'order' => $order,
            ];
        });
    }

    /**
     * Buy additional prepaid seats for an existing paid private event.
     *
     * @return array{event: Event, order: Order}
     */
    public function addCapacityCheckout(Event $event, User $customer, int $quantity): array
    {
        $this->assertOwnedPublished($event, $customer);

        $max = self::maxTickets();
        if ($quantity < 1 || $quantity > $max) {
            throw ValidationException::withMessages([
                'quantity' => ["Choose between 1 and {$max} tickets."],
            ]);
        }

        $type = $event->ticketTypes()->orderBy('id')->first();
        if (! $type) {
            throw ValidationException::withMessages([
                'event' => ['This private event has no ticket type.'],
            ]);
        }

        $unit = (float) $type->price ?: self::unitPrice();

            $order = $this->buildCapacityOrder($event, $type, $quantity, $unit, $customer);

        return [
            'event' => $event->fresh('ticketTypes'),
            'order' => $order,
        ];
    }

    public function payCapacityOrder(Order $order, string $paymentMethod, ?string $phone = null, bool $forceFail = false, ?string $walletPin = null): Order
    {
        if ($order->source !== 'private_event') {
            throw ValidationException::withMessages([
                'order' => ['This order is not a private event capacity purchase.'],
            ]);
        }

        return $this->orders->pay($order, $paymentMethod, $phone, $forceFail, $walletPin);
    }

    public function pendingOrder(Event $event, int $userId): ?Order
    {
        return Order::query()
            ->with(['items.ticketType', 'event', 'payment'])
            ->where('event_id', $event->id)
            ->where('user_id', $userId)
            ->where('source', 'private_event')
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    /**
     * Draft private events must always have a payable capacity order.
     * Recreates one when the original pending checkout was failed/expired/missing.
     */
    public function ensurePendingOrder(Event $event, User $customer): Order
    {
        $pending = $this->pendingOrder($event, $customer->id);
        if ($pending) {
            return $pending;
        }

        if (! $event->is_private || $event->owner_user_id !== $customer->id) {
            throw ValidationException::withMessages([
                'event' => ['You cannot pay for this private event.'],
            ]);
        }

        if ($event->status !== 'draft') {
            throw ValidationException::withMessages([
                'order' => ['No pending payment for this private event.'],
            ]);
        }

        $event->loadMissing('ticketTypes');
        $type = $event->ticketTypes->sortBy('id')->first();
        if (! $type) {
            throw ValidationException::withMessages([
                'event' => ['This private event has no ticket type.'],
            ]);
        }

        $qty = max(1, (int) $type->quantity_available);
        $unit = (float) $type->price ?: self::unitPriceForDesign($event->ticket_design);

        return $this->buildCapacityOrder($event, $type, $qty, $unit, $customer);
    }

    /**
     * After successful payment for source=private_event:
     * - First purchase (draft event): publish event (capacity already on ticket type).
     * - Top-up (published event): increase quantity_available.
     */
    public function fulfillCapacityPurchase(Order $order): void
    {
        $order->loadMissing(['items.ticketType', 'event']);

        $event = $order->event;
        if (! $event || ! $event->is_private) {
            return;
        }

        $wasPublished = $event->status === 'published';

        foreach ($order->items as $item) {
            $type = TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->first();
            if (! $type) {
                continue;
            }

            if ($wasPublished) {
                $type->increment('quantity_available', $item->quantity);
                $type->update([
                    'max_per_order' => min(20, max((int) $type->max_per_order, (int) $item->quantity)),
                ]);
            }
        }

        if (! $wasPublished) {
            $event->update(['status' => 'published']);
        }
    }

    private function buildCapacityOrder(
        Event $event,
        TicketType $type,
        int $qty,
        float $unit,
        User $customer,
    ): Order {
        $subtotal = round($unit * $qty, 2);
        $fee = self::serviceFee();
        $total = round($subtotal + $fee, 2);
        $commission = $subtotal;

        $phone = Phone::normalize($customer->phone);
        if ($phone === '') {
            throw ValidationException::withMessages([
                'phone' => ['Add a phone number to your account before creating a private event.'],
            ]);
        }

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'order_number' => $this->nextOrderNumber(),
            'buyer_name' => $customer->name ?: 'Customer',
            'buyer_email' => $this->customerEmail($customer),
            'buyer_phone' => $phone,
            'subtotal' => $subtotal,
            'service_fee' => $fee,
            'total_amount' => $total,
            'commission_amount' => $commission,
            'status' => 'pending',
            'payment_method' => null,
            'payment_reference' => null,
            'source' => 'private_event',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'ticket_type_id' => $type->id,
            'quantity' => $qty,
            'unit_price' => $unit,
            'subtotal' => $subtotal,
        ]);

        return $order->load(['items.ticketType', 'event']);
    }

    private function assertOwnedPublished(Event $event, User $customer): void
    {
        if (! $event->is_private || $event->owner_user_id !== $customer->id) {
            throw ValidationException::withMessages([
                'event' => ['You cannot modify this private event.'],
            ]);
        }

        if ($event->status !== 'published') {
            throw ValidationException::withMessages([
                'event' => ['Finish paying for this event before buying more tickets.'],
            ]);
        }
    }

    private function customerEmail(User $customer): ?string
    {
        $email = $customer->email;
        if (! $email || str_ends_with(strtolower($email), '@ekaadh.local')) {
            return null;
        }

        return $email;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'private-event';
        $slug = $base.'-'.Str::lower(Str::random(5));
        $i = 1;
        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5)).'-'.$i++;
        }

        return $slug;
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'EKD-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
