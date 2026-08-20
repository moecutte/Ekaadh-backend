<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\PaginatesFilteredLists;
use App\Models\Event;
use App\Models\EventInvitation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InviteeController extends Controller
{
    use PaginatesFilteredLists;

    public function index(Request $request): View
    {
        $eventId = $request->integer('event');
        $eventSearch = $request->string('event_q')->trim()->toString();
        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();
        $opened = $request->string('opened')->toString();
        $channel = $request->string('channel')->toString();

        $eventsQuery = Event::query()
            ->where(function ($q) {
                $q->where('is_private', true)
                    ->orWhereHas('invitations')
                    ->orWhereNotNull('pending_invitations');
            })
            ->with(['owner:id,name,phone', 'organizer.user:id,name,phone'])
            ->withCount([
                'invitations as invitees_count',
            ])
            ->orderByDesc('id');

        if ($eventSearch !== '') {
            $eventsQuery->where(function ($q) use ($eventSearch) {
                $q->where('title', 'like', '%'.$eventSearch.'%')
                    ->orWhere('city', 'like', '%'.$eventSearch.'%')
                    ->orWhere('venue', 'like', '%'.$eventSearch.'%')
                    ->orWhereHas('owner', function ($o) use ($eventSearch) {
                        $o->where('name', 'like', '%'.$eventSearch.'%')
                            ->orWhere('phone', 'like', '%'.$eventSearch.'%');
                    })
                    ->orWhereHas('organizer', function ($o) use ($eventSearch) {
                        $o->where('business_name', 'like', '%'.$eventSearch.'%')
                            ->orWhereHas('user', function ($u) use ($eventSearch) {
                                $u->where('name', 'like', '%'.$eventSearch.'%')
                                    ->orWhere('phone', 'like', '%'.$eventSearch.'%');
                            });
                    });
            });
        }

        $events = $eventsQuery
            ->paginate(20, ['*'], 'events_page')
            ->withQueryString();

        $event = null;
        $invitees = null;
        $stats = [
            'invitees' => 0,
            'active' => 0,
            'opened' => 0,
            'seats' => 0,
            'failed' => 0,
        ];

        if ($eventId > 0) {
            $event = Event::query()
                ->where(function ($q) {
                    $q->where('is_private', true)
                        ->orWhereHas('invitations')
                        ->orWhereNotNull('pending_invitations');
                })
                ->with(['owner:id,name,phone,email', 'organizer.user:id,name,phone,email', 'ticketTypes'])
                ->withCount('invitations as invitees_count')
                ->find($eventId);
        }

        if ($event) {
            $query = EventInvitation::query()
                ->where('event_id', $event->id)
                ->with('ticketType:id,name');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('guest_name', 'like', '%'.$search.'%')
                        ->orWhere('guest_phone', 'like', '%'.$search.'%');
                });
            }

            if (in_array($status, ['active', 'revoked'], true)) {
                $query->where('status', $status);
            }

            if ($opened === 'yes') {
                $query->whereNotNull('opened_at');
            } elseif ($opened === 'no') {
                $query->whereNull('opened_at');
            }

            if (in_array($channel, ['sms', 'whatsapp'], true)) {
                $query->where('delivery_channel', $channel);
            }

            $base = EventInvitation::query()->where('event_id', $event->id);
            $stats = [
                'invitees' => (clone $base)->count(),
                'active' => (clone $base)->where('status', 'active')->count(),
                'opened' => (clone $base)->whereNotNull('opened_at')->count(),
                'seats' => (int) (clone $base)->where('status', 'active')->sum('quantity'),
                'failed' => (clone $base)->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('delivery_channel', 'sms')->where('sms_status', 'failed');
                    })->orWhere(function ($inner) {
                        $inner->where('delivery_channel', 'whatsapp')->where('whatsapp_status', 'failed');
                    });
                })->count(),
            ];

            $total = (clone $query)->count();
            $invitees = $this->paginateFiltered(
                (clone $query)->latest(),
                $total,
                $request
            );
        }

        return view('admin.invitees.index', [
            'events' => $events,
            'event' => $event,
            'invitees' => $invitees,
            'stats' => $stats,
            'eventSearch' => $eventSearch,
            'search' => $search,
            'status' => $status,
            'opened' => $opened,
            'channel' => $channel,
        ]);
    }
}
