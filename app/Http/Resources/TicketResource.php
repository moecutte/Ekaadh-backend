<?php

namespace App\Http\Resources;

use App\Services\TicketQrService;
use App\Support\TicketDesigns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $qr = app(TicketQrService::class);
        $event = $this->relationLoaded('event') ? $this->event : null;
        $order = $this->relationLoaded('orderItem') ? $this->orderItem?->order : null;

        $isUpcoming = $event?->event_date
            ? $event->event_date->copy()->startOfDay()->gte(now()->startOfDay())
            : true;

        $invitationDesign = null;
        if ($event?->is_private) {
            $design = TicketDesigns::resolveForEvent($event);
            if (($design['render_mode'] ?? '') === 'overlay' && ! empty($design['graphic_url'])) {
                $invitationDesign = [
                    'render_mode' => 'overlay',
                    'graphic_url' => $design['graphic_url'],
                    'thumbnail_url' => $design['thumbnail_url'] ?? $design['graphic_url'],
                    'card_bg' => $design['card_bg'] ?? '#ffffff',
                    'text' => $design['text'] ?? '#0f1a2e',
                    'muted' => $design['muted'] ?? '#64748b',
                    'accent' => $design['accent'] ?? '#323891',
                    'fields' => $design['fields'] ?? [],
                    'field_values' => $design['field_values'] ?? [],
                ];
            }
        }

        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'holder_name' => $this->holder_name,
            'ticket_type_name' => $this->ticket_type_name,
            'status' => $this->status,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'qr_payload' => $qr->payload($this->ticket_code),
            'public_url' => $qr->publicUrl($this->ticket_code),
            'is_upcoming' => $isUpcoming && $this->status === 'valid',
            'event' => $event ? [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'is_private' => (bool) $event->is_private,
                'cover_image' => $event->cover_image,
                'venue' => $event->venue,
                'city' => $event->city,
                'event_date' => $event->event_date?->format('Y-m-d'),
                'event_date_label' => $event->event_date?->format('M j, Y'),
                'event_time_label' => $event->event_time
                    ? date('g:i A', strtotime((string) $event->event_time))
                    : null,
            ] : null,
            'invitation_design' => $invitationDesign,
            'order_number' => $order?->order_number,
        ];
    }
}
