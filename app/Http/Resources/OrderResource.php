<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    /** Hide mock / unconfigured gateway IDs from API clients. */
    private function publicTransactionId(?string $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        if (preg_match('/^(MOCK-|ZAAD-|EDAHAB-|WAAFI-UNCONFIGURED|WAAFI-PENDING|WAAFI-FORCE|WAAFI-INVALID|WAAFI-FAILED|WAAFI-TIMEOUT|WAAFI-SSL|WAAFI-PIN)/i', $id)) {
            return null;
        }

        return $id;
    }

    public function toArray(Request $request): array
    {
        $tickets = $this->whenLoaded('items', function () {
            return $this->items
                ->flatMap(fn ($item) => $item->relationLoaded('tickets') ? $item->tickets : collect())
                ->values();
        });

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'buyer_name' => $this->buyer_name,
            'buyer_email' => $this->buyer_email,
            'buyer_phone' => $this->buyer_phone,
            'subtotal' => (float) $this->subtotal,
            'service_fee' => (float) $this->service_fee,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->publicTransactionId($this->payment_reference),
            'created_at' => $this->created_at?->toIso8601String(),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'slug' => $this->event->slug,
                'cover_image' => $this->event->cover_image,
                'is_free' => $this->event->isFreeEvent(),
                'event_date_label' => $this->event->event_date?->format('M j, Y'),
                'event_time_label' => $this->event->event_time
                    ? date('g:i A', strtotime((string) $this->event->event_time))
                    : null,
                'venue' => $this->event->venue,
                'city' => $this->event->city,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'ticket_type_id' => $item->ticket_type_id,
                'ticket_type_name' => $item->ticketType?->name ?? $item->tickets->first()?->ticket_type_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
            ])),
            'tickets' => $this->when($tickets !== null, fn () => collect($tickets)->map(function ($t) {
                $qr = app(\App\Services\TicketQrService::class);

                return [
                    'id' => $t->id,
                    'ticket_code' => $t->ticket_code,
                    'holder_name' => $t->holder_name,
                    'ticket_type_name' => $t->ticket_type_name,
                    'status' => $t->status,
                    'qr_payload' => $qr->payload($t->ticket_code),
                    'public_url' => $qr->publicUrl($t->ticket_code),
                ];
            })),
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'provider' => $this->payment->provider,
                'transaction_id' => $this->publicTransactionId($this->payment->transaction_id),
                'status' => $this->payment->status,
                'amount' => (float) $this->payment->amount,
            ] : null),
        ];
    }
}
