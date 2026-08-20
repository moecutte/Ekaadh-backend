<?php

namespace App\Support;

use App\Models\Ticket;

class InvitationCardData
{
    /**
     * Shared copy for HTML invitation templates (names, date, venue, badge).
     *
     * @param  array<string, mixed>  $design
     * @return array<string, mixed>
     */
    public static function hydrate(array $design, ?Ticket $ticket = null, bool $compact = false, bool $showQr = true): array
    {
        $event = $ticket?->event;
        $values = $design['field_values'] ?? [];

        $couple1 = trim((string) ($values['couple_name_1'] ?? $event?->couple_name_1 ?? ''));
        $couple2 = trim((string) ($values['couple_name_2'] ?? $event?->couple_name_2 ?? ''));
        $coupleLine = ($couple1 !== '' && $couple2 !== '') ? ($couple1.' & '.$couple2) : null;
        $titleLine = trim((string) ($values['title'] ?? $event?->title ?? ''));
        $headline = $coupleLine ?: $titleLine;
        $venueLine = trim((string) ($values['venue'] ?? $event?->venue ?? ''));
        $cityLine = trim((string) ($event?->city ?? ''));
        $addressLine = collect([$event?->address ?? null, $cityLine])->filter()->implode(', ');

        $month = trim((string) ($values['date_month'] ?? ''));
        $day = trim((string) ($values['date_day'] ?? ''));
        $year = trim((string) ($values['date_year'] ?? ''));
        $timeFromFields = trim((string) ($values['date_time'] ?? ''));
        $prettyDate = ($month !== '' && $day !== '' && $year !== '')
            ? trim($month.' '.$day.', '.$year)
            : ($event?->event_date?->format('l, F j, Y') ?: '');
        $prettyTime = $timeFromFields !== ''
            ? $timeFromFields
            : ($event?->event_time ? date('g:i A', strtotime($event->event_time)) : null);

        return [
            'compact' => $compact,
            'showQr' => $showQr,
            'event' => $event,
            'values' => $values,
            'couple1' => $couple1,
            'couple2' => $couple2,
            'coupleLine' => $coupleLine,
            'titleLine' => $titleLine,
            'headline' => $headline,
            'venueLine' => $venueLine,
            'cityLine' => $cityLine,
            'addressLine' => $addressLine,
            'prettyDate' => $prettyDate,
            'prettyTime' => $prettyTime,
            'guestName' => trim((string) ($ticket?->holder_name ?? '')),
            'ticketCode' => (string) ($ticket?->ticket_code ?? ''),
            'ticketType' => (string) ($ticket?->ticket_type_name ?? ''),
            'statusLabel' => ucfirst((string) ($ticket?->status ?? 'valid')),
            'statusOk' => ($ticket?->status ?? 'valid') === 'valid',
            'inviteLine' => $design['invite_line'] ?? '',
            'requestLine' => $design['request_line'] ?? '',
            'footerLine' => $design['footer_line'] ?? 'Kindly present this invitation at the entrance',
            'badge' => $design['badge'] ?? '',
            'ornament' => $design['ornament'] ?? '✦',
        ];
    }
}
