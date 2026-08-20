<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Models\Event;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ResolvesInvitationGuests
{
    /**
     * @return array<int, array{name: ?string, phone: string, quantity: int, ticket_type_id: int}>
     */
    private function resolveGuests(Request $request, Event $event, int $maxGuests = 200): array
    {
        if ($request->hasFile('csv')) {
            return $this->parseInvitationCsv($request, $event, $maxGuests);
        }

        $data = $request->validate([
            'guests' => ['required', 'array', 'min:1', 'max:'.$maxGuests],
            'guests.*.name' => ['nullable', 'string', 'max:120'],
            'guests.*.phone' => ['required', 'string', 'max:30'],
            'guests.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'guests.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
        ]);

        $typeIds = $event->ticketTypes->pluck('id')->all();
        $guests = [];

        foreach ($data['guests'] as $i => $row) {
            if (! in_array((int) $row['ticket_type_id'], $typeIds, true)) {
                throw ValidationException::withMessages([
                    "guests.{$i}.ticket_type_id" => ['Invalid ticket type for this event.'],
                ]);
            }

            $phone = Phone::normalize($row['phone']);
            if ($phone === '') {
                throw ValidationException::withMessages([
                    "guests.{$i}.phone" => ['Enter a valid phone number.'],
                ]);
            }

            $guests[] = [
                'name' => $row['name'] ?? null,
                'phone' => $phone,
                'quantity' => (int) $row['quantity'],
                'ticket_type_id' => (int) $row['ticket_type_id'],
            ];
        }

        return $guests;
    }

    /**
     * @return array<int, array{name: ?string, phone: string, quantity: int, ticket_type_id: int}>
     */
    private function parseInvitationCsv(Request $request, Event $event, int $maxGuests = 200): array
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'default_ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
        ]);

        $defaultTypeId = (int) $request->input('default_ticket_type_id');
        $typeIds = $event->ticketTypes->pluck('id')->all();
        if (! in_array($defaultTypeId, $typeIds, true)) {
            throw ValidationException::withMessages([
                'default_ticket_type_id' => ['Invalid ticket type for this event.'],
            ]);
        }

        $typesByName = $event->ticketTypes->mapWithKeys(
            fn ($t) => [mb_strtolower(trim($t->name)) => $t->id]
        );

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv' => ['Could not read the CSV file.'],
            ]);
        }

        $guests = [];
        $rowNum = 0;

        while (($cols = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($rowNum === 1 && $this->looksLikeInvitationHeader($cols)) {
                continue;
            }

            $cols = array_map(fn ($c) => trim((string) $c), $cols);
            if (count(array_filter($cols)) === 0) {
                continue;
            }

            $phone = $cols[0] ?? '';
            $name = $cols[1] ?? null;
            $qty = isset($cols[2]) && $cols[2] !== '' ? (int) $cols[2] : 1;
            $typeName = $cols[3] ?? '';

            $typeId = $defaultTypeId;
            if ($typeName !== '') {
                $typeId = $typesByName[mb_strtolower($typeName)] ?? null;
                if (! $typeId) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'csv' => ["Row {$rowNum}: unknown ticket type \"{$typeName}\"."],
                    ]);
                }
            }

            $normalized = Phone::normalize($phone);
            if ($normalized === '') {
                fclose($handle);
                throw ValidationException::withMessages([
                    'csv' => ["Row {$rowNum}: invalid phone \"{$phone}\"."],
                ]);
            }

            if ($qty < 1) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'csv' => ["Row {$rowNum}: quantity must be at least 1."],
                ]);
            }

            $guests[] = [
                'name' => $name !== '' ? $name : null,
                'phone' => $normalized,
                'quantity' => $qty,
                'ticket_type_id' => $typeId,
            ];
        }

        fclose($handle);

        if ($guests === []) {
            throw ValidationException::withMessages([
                'csv' => ['CSV had no guest rows. Use: phone, name, quantity, ticket_type.'],
            ]);
        }

        if (count($guests) > $maxGuests) {
            throw ValidationException::withMessages([
                'csv' => ["CSV is limited to {$maxGuests} guests per upload."],
            ]);
        }

        return $guests;
    }

    /**
     * @param  array<int, string|null>  $cols
     */
    private function looksLikeInvitationHeader(array $cols): bool
    {
        $first = mb_strtolower(trim((string) ($cols[0] ?? '')));

        return in_array($first, ['phone', 'mobile', 'number', 'guest_phone'], true);
    }

    private function resolveInviteChannel(Request $request): string
    {
        $data = $request->validate([
            'channel' => ['required', 'in:sms,whatsapp'],
        ]);

        return $data['channel'];
    }

    private function optionalInviteChannel(Request $request): ?string
    {
        $data = $request->validate([
            'channel' => ['nullable', 'in:sms,whatsapp'],
        ]);

        return $data['channel'] ?? null;
    }
}
