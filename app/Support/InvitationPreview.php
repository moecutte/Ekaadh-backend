<?php

namespace App\Support;

use App\Models\Event;
use App\Models\InvitationDesign;
use App\Models\Ticket;
use Carbon\Carbon;

class InvitationPreview
{
    /**
     * Build an unsaved ticket + catalog design for live HTML previews.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $meta
     * @return array{ticket: Ticket, design: array<string, mixed>, qrImage: string}
     */
    public static function make(InvitationDesign $design, array $values = [], array $meta = []): array
    {
        $design->loadMissing('fields');
        $catalog = $design->toCatalogArray();

        foreach ($design->fields as $field) {
            $key = (string) $field->field_key;
            if ($key === '' || ($field->field_type ?? '') === 'qr') {
                continue;
            }
            $current = trim((string) ($values[$key] ?? ''));
            if ($current === '') {
                $values[$key] = (string) ($field->default_text ?? '');
            } else {
                $values[$key] = $current;
            }
        }

        $dateRaw = $meta['event_date'] ?? null;
        try {
            $date = $dateRaw ? Carbon::parse((string) $dateRaw) : now()->addWeeks(3);
        } catch (\Throwable) {
            $date = now()->addWeeks(3);
        }
        $time = (string) ($meta['event_time'] ?? '18:00');

        $values = InvitationDateFields::applyToValues(
            $catalog['fields'] ?? [],
            $values,
            $date,
            $time,
        );

        $couple1 = trim((string) ($values['couple_name_1'] ?? ''));
        $couple2 = trim((string) ($values['couple_name_2'] ?? ''));
        $title = trim((string) ($values['title'] ?? ''));
        if ($title === '') {
            $title = ($couple1 !== '' && $couple2 !== '')
                ? $couple1.' & '.$couple2
                : ($design->name ?: 'Invitation');
        }
        $venue = trim((string) ($values['venue'] ?? $meta['venue'] ?? ''));

        $event = new Event([
            'title' => $title,
            'venue' => $venue !== '' ? $venue : 'Grand Ballroom',
            'address' => $meta['address'] ?? null,
            'city' => $meta['city'] ?? null,
            'event_date' => $date->toDateString(),
            'event_time' => $time,
            'is_private' => true,
            'couple_name_1' => $couple1 !== '' ? $couple1 : null,
            'couple_name_2' => $couple2 !== '' ? $couple2 : null,
            'invitation_field_values' => $values,
            'invitation_design_id' => $design->id,
        ]);

        $ticket = new Ticket([
            'holder_name' => $meta['guest_name'] ?? '',
            'ticket_code' => 'PREVIEW',
            'ticket_type_name' => 'Invitation',
            'status' => 'valid',
        ]);
        $ticket->setRelation('event', $event);

        $catalog['field_values'] = $values;
        if (($catalog['render_mode'] ?? 'blade') !== 'overlay') {
            $catalog['graphic_url'] = null;
            $catalog['graphic_path'] = null;
        }

        return [
            'ticket' => $ticket,
            'design' => $catalog,
            'qrImage' => \App\Support\QrPng::dataUri('EKAADH-PREVIEW', 240),
        ];
    }
}
