<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class PrivateEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $types = $this->relationLoaded('ticketTypes') ? $this->ticketTypes : collect();
        $capacity = (int) $types->sum('quantity_available');
        $sold = (int) $types->sum('quantity_sold');
        $remaining = (int) $types->sum(fn ($t) => $t->remaining());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'venue' => $this->venue,
            'address' => $this->address,
            'city' => $this->city,
            'event_date' => $this->event_date?->format('Y-m-d'),
            'event_date_label' => $this->event_date?->format('M j, Y'),
            'event_time' => $this->event_time ? substr((string) $this->event_time, 0, 5) : null,
            'event_time_label' => $this->event_time
                ? date('g:i A', strtotime((string) $this->event_time))
                : null,
            'cover_image' => $this->cover_image,
            'status' => $this->status,
            'is_private' => (bool) $this->is_private,
            'is_expired' => $this->isExpired(),
            'ticket_design' => $this->ticket_design,
            'invitation_design_id' => $this->invitation_design_id,
            'invitation_field_values' => $this->invitation_field_values,
            'private_event_category_id' => $this->private_event_category_id,
            'private_category' => $this->privateEventCategory
                ? [
                    'id' => $this->privateEventCategory->id,
                    'name' => $this->privateEventCategory->name,
                    'slug' => $this->privateEventCategory->slug,
                    'requires_couple_names' => (bool) $this->privateEventCategory->requires_couple_names,
                ]
                : null,
            'couple_name_1' => $this->couple_name_1,
            'couple_name_2' => $this->couple_name_2,
            'couple_display_name' => $this->coupleDisplayName(),
            'design' => $this->is_private
                ? \App\Support\TicketDesigns::resolveForEvent($this->resource)
                : null,
            'capacity' => $capacity,
            'invited' => $sold,
            'remaining' => $remaining,
            'ticket_types' => TicketTypeResource::collection($this->whenLoaded('ticketTypes')),
            'pending_order' => $this->when(
                isset($this->pending_order),
                fn () => $this->pending_order
                    ? new OrderResource($this->pending_order)
                    : null
            ),
            'payment_sandbox' => (bool) config('waafipay.sandbox'),
            'test_wallets' => $this->when(
                (bool) config('waafipay.sandbox'),
                array_values(config('waafipay.test_wallets', []))
            ),
        ];
    }
}
