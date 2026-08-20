<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EventInvitation */
class EventInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guest_name' => $this->guest_name,
            'guest_phone' => $this->guest_phone,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'sms_status' => $this->sms_status,
            'whatsapp_status' => $this->whatsapp_status,
            'delivery_channel' => $this->delivery_channel,
            'invitation_url' => $this->publicUrl(),
            'ticket_type' => $this->whenLoaded('ticketType', fn () => [
                'id' => $this->ticketType->id,
                'name' => $this->ticketType->name,
            ]),
            'last_sent_at' => $this->last_sent_at?->toIso8601String(),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'tickets' => $this->whenLoaded('tickets', fn () => $this->tickets->map(function ($t) {
                $qr = app(\App\Services\TicketQrService::class);

                return [
                    'id' => $t->id,
                    'ticket_code' => $t->ticket_code,
                    'status' => $t->status,
                    'ticket_type_name' => $t->ticket_type_name,
                    'public_url' => $qr->publicUrl($t->ticket_code),
                ];
            })),
        ];
    }
}
