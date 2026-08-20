<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startingPrice = $this->relationLoaded('ticketTypes')
            ? $this->ticketTypes->min('price')
            : $this->ticketTypes()->min('price');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'venue' => $this->venue,
            'address' => $this->address,
            'city' => $this->city,
            'event_date' => $this->event_date?->format('Y-m-d'),
            'event_date_label' => $this->event_date?->format('M j, Y'),
            'event_month' => $this->event_date?->format('M'),
            'event_day' => $this->event_date?->format('j'),
            'event_time' => $this->event_time ? substr((string) $this->event_time, 0, 5) : null,
            'event_time_label' => $this->formatTimeLabel(),
            'cover_image' => $this->cover_image,
            'is_featured' => (bool) $this->is_featured,
            'is_private' => (bool) $this->is_private,
            'is_free' => $this->isFreeEvent(),
            'is_expired' => $this->isExpired(),
            'status' => $this->status,
            'starting_price' => $startingPrice !== null ? (float) $startingPrice : null,
            'organizer' => $this->whenLoaded('organizer', function () {
                $this->organizer->loadMissing('user');

                return [
                    'id' => $this->organizer->id,
                    'business_name' => $this->organizer->business_name,
                    'profile_image' => $this->organizer->avatarUrl(),
                ];
            }),
            'ticket_types' => TicketTypeResource::collection($this->whenLoaded('ticketTypes')),
            'speakers' => $this->whenLoaded('speakers', fn () => $this->speakers->map(fn ($speaker) => [
                'id' => $speaker->id,
                'name' => $speaker->name,
                'role' => $speaker->role,
                'bio' => $speaker->bio,
                'photo' => $speaker->photo,
            ])->values()),
            'programme' => $this->whenLoaded('programmeItems', fn () => $this->programmeItems->map(fn ($item) => [
                'id' => $item->id,
                'starts_at' => \App\Models\EventProgrammeItem::clockValue($item->starts_at),
                'ends_at' => \App\Models\EventProgrammeItem::clockValue($item->ends_at),
                'time_label' => $item->timeRangeLabel(),
                'title' => $item->title,
                'description' => $item->description,
            ])->values()),
            'gallery' => $this->whenLoaded('galleryImages', fn () => $this->galleryImages->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->path,
            ])->values()),
            'payment_sandbox' => (bool) config('waafipay.sandbox'),
            'service_fee' => (float) \App\Models\Setting::getValue('service_fee', 1),
        ];
    }

    private function formatTimeLabel(): ?string
    {
        if (! $this->event_time) {
            return null;
        }

        return date('g:i A', strtotime((string) $this->event_time));
    }
}
