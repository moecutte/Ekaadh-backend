<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function mine(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $tab = $request->string('tab')->toString() ?: 'upcoming';
        $phoneVariants = $user->phone ? Phone::variants($user->phone) : [];

        $query = Ticket::query()
            ->with(['event', 'orderItem.order', 'invitation'])
            ->where(function ($q) use ($user, $phoneVariants) {
                // Purchased tickets (public checkout or invitation orders on this phone)
                $q->whereHas('orderItem.order', function ($q) use ($user, $phoneVariants) {
                    $q->where('status', 'paid')
                        ->where(function ($q) use ($user, $phoneVariants) {
                            $q->where('user_id', $user->id);
                            if ($phoneVariants !== []) {
                                $q->orWhereIn('buyer_phone', $phoneVariants);
                            }
                        });
                });

                // Private invitation tickets addressed to this phone
                if ($phoneVariants !== []) {
                    $q->orWhere(function ($q) use ($phoneVariants) {
                        $q->whereHas('invitation', function ($q) use ($phoneVariants) {
                            $q->where('status', 'active')
                                ->whereIn('guest_phone', $phoneVariants);
                        });
                    });
                }
            })
            ->latest();

        if ($tab === 'past') {
            $query->where(function ($q) {
                $q->where('status', '!=', 'valid')
                    ->orWhereHas('event', fn ($e) => $e->whereDate('event_date', '<', now()->toDateString()));
            });
        } else {
            // Upcoming: valid tickets for today/future, or events with no date yet
            $query->where('status', 'valid')
                ->where(function ($q) {
                    $q->whereHas('event', function ($e) {
                        $e->whereNull('event_date')
                            ->orWhereDate('event_date', '>=', now()->toDateString());
                    });
                });
        }

        return TicketResource::collection($query->paginate(50));
    }

    public function show(string $code): TicketResource
    {
        $ticket = Ticket::query()
            ->with(['event', 'orderItem.order', 'invitation'])
            ->where('ticket_code', strtoupper($code))
            ->firstOrFail();

        return new TicketResource($ticket);
    }
}
