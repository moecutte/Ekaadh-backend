<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public function __construct(
        private readonly TicketQrService $qr,
    ) {}

    /**
     * @return array{result: string, message: string, ticket: ?Ticket}
     */
    public function scan(string $payload, User $staff, ?int $eventId = null, bool $manual = false): array
    {
        $code = $this->qr->verify(trim($payload), $manual);

        if ($code === null) {
            return [
                'result' => 'invalid',
                'message' => 'Invalid ticket QR code.',
                'ticket' => null,
            ];
        }

        return DB::transaction(function () use ($code, $staff, $eventId) {
            $query = Ticket::query()
                ->with(['event', 'orderItem.order', 'checkedInBy'])
                ->where('ticket_code', $code)
                ->lockForUpdate();

            if ($eventId !== null) {
                $query->where('event_id', $eventId);
            }

            $ticket = $query->first();

            if (! $ticket) {
                // Wrong event or unknown code
                $existsElsewhere = $eventId !== null
                    && Ticket::query()->where('ticket_code', $code)->exists();

                return [
                    'result' => 'invalid',
                    'message' => $existsElsewhere
                        ? 'Ticket is not for this event.'
                        : 'Ticket not found.',
                    'ticket' => null,
                ];
            }

            if (! $ticket->event || $ticket->event->status !== 'published') {
                return [
                    'result' => 'invalid',
                    'message' => 'This event is not open for check-in.',
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status === 'cancelled') {
                return [
                    'result' => 'invalid',
                    'message' => 'This ticket has been cancelled.',
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status === 'used') {
                return [
                    'result' => 'used',
                    'message' => 'Already checked in'
                        .($ticket->checked_in_at ? ' at '.$ticket->checked_in_at->format('g:i A') : '')
                        .'.',
                    'ticket' => $ticket,
                ];
            }

            $ticket->update([
                'status' => 'used',
                'checked_in_at' => now(),
                'checked_in_by' => $staff->id,
            ]);

            $ticket->refresh()->load(['event', 'orderItem.order', 'checkedInBy']);

            return [
                'result' => 'valid',
                'message' => 'Admit guest.',
                'ticket' => $ticket,
            ];
        });
    }
}
