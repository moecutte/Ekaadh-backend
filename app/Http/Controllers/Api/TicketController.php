<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

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
                $q->whereHas('orderItem.order', function ($q) use ($user, $phoneVariants) {
                    $q->where('status', 'paid')
                        ->where(function ($q) use ($user, $phoneVariants) {
                            $q->where('user_id', $user->id);
                            if ($phoneVariants !== []) {
                                $q->orWhereIn('buyer_phone', $phoneVariants)
                                    ->orWhereHas('payment', function ($q) use ($phoneVariants) {
                                        $q->whereIn('phone_number', $phoneVariants);
                                    });
                            }
                        });
                });

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

    public function show(Request $request, string $code): TicketResource
    {
        $ticket = Ticket::query()
            ->with(['event', 'orderItem.order.payment', 'invitation'])
            ->where('ticket_code', strtoupper($code))
            ->firstOrFail();

        if (! $this->viewerOwnsTicket($request, $ticket)) {
            abort(404);
        }

        return new TicketResource($ticket);
    }

    private function viewerOwnsTicket(Request $request, Ticket $ticket): bool
    {
        $user = $request->user('sanctum') ?: Auth::guard('sanctum')->user();
        $phone = trim((string) $request->query('phone', ''));

        if ($user instanceof User && $this->userOwnsTicket($user, $ticket)) {
            return true;
        }

        if ($phone !== '' && $this->phoneOwnsTicket($phone, $ticket)) {
            return true;
        }

        return false;
    }

    private function userOwnsTicket(User $user, Ticket $ticket): bool
    {
        $order = $ticket->orderItem?->order;
        if ($order && (int) $order->user_id === (int) $user->id) {
            return true;
        }

        if ($user->phone) {
            return $this->phoneOwnsTicket($user->phone, $ticket);
        }

        return false;
    }

    private function phoneOwnsTicket(string $phone, Ticket $ticket): bool
    {
        $order = $ticket->orderItem?->order;
        if ($order && Phone::matches($order->buyer_phone, $phone)) {
            return true;
        }
        if ($order?->payment && Phone::matches($order->payment->phone_number, $phone)) {
            return true;
        }

        $invitation = $ticket->invitation;
        if ($invitation && Phone::matches($invitation->guest_phone, $phone)) {
            return true;
        }

        return false;
    }
}
