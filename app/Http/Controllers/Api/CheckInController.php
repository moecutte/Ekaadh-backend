<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $events = Event::query()
            ->published()
            ->withCount([
                'tickets as tickets_total',
                'tickets as tickets_checked_in' => fn ($q) => $q->where('status', 'used'),
            ])
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'venue' => $event->venue,
                'city' => $event->city,
                'cover_image' => $event->cover_image,
                'event_date' => $event->event_date?->format('Y-m-d'),
                'event_date_label' => $event->event_date?->format('M j, Y'),
                'event_time_label' => $event->event_time
                    ? date('g:i A', strtotime((string) $event->event_time))
                    : null,
                'tickets_total' => (int) $event->tickets_total,
                'tickets_checked_in' => (int) $event->tickets_checked_in,
            ]);

        return response()->json(['data' => $events]);
    }

    public function scan(Request $request, CheckInService $checkIn): JsonResponse
    {
        $data = $request->validate([
            'payload' => ['required', 'string', 'max:500'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        $outcome = $checkIn->scan(
            $data['payload'],
            $request->user(),
            isset($data['event_id']) ? (int) $data['event_id'] : null,
        );

        $status = match ($outcome['result']) {
            'valid' => 200,
            'used' => 409,
            default => 422,
        };

        return response()->json([
            'result' => $outcome['result'],
            'message' => $outcome['message'],
            'ticket' => $outcome['ticket']
                ? (new TicketResource($outcome['ticket']))->resolve()
                : null,
        ], $status);
    }

    public function stats(Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $total = Ticket::query()->where('event_id', $event->id)->count();
        $checkedIn = Ticket::query()->where('event_id', $event->id)->where('status', 'used')->count();
        $valid = Ticket::query()->where('event_id', $event->id)->where('status', 'valid')->count();

        return response()->json([
            'event_id' => $event->id,
            'tickets_total' => $total,
            'tickets_checked_in' => $checkedIn,
            'tickets_valid' => $valid,
        ]);
    }
}
