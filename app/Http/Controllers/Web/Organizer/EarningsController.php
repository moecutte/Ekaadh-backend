<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profile = auth()->user()->organizerProfile;
        $events = $profile
            ? $profile->events()->orderByDesc('event_date')->orderByDesc('id')->get(['id', 'title', 'event_date', 'pricing_type'])
            : collect();

        $allEventIds = $events->pluck('id');
        $selectedEventId = $request->integer('event_id') ?: null;
        if ($selectedEventId && ! $allEventIds->contains($selectedEventId)) {
            $selectedEventId = null;
        }

        $eventIds = $selectedEventId ? collect([$selectedEventId]) : $allEventIds;
        $selectedEvent = $selectedEventId
            ? $events->firstWhere('id', $selectedEventId)
            : null;

        $salesBase = fn () => Order::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', 'paid')
            ->ticketSales();

        $gross = $eventIds->isEmpty() ? 0.0 : (float) $salesBase()->sum('subtotal');
        $commission = $eventIds->isEmpty() ? 0.0 : (float) $salesBase()->sum('commission_amount');
        $net = $gross - $commission;

        $orderDateBounds = null;
        if ($selectedEventId && ! $eventIds->isEmpty()) {
            $orderDateBounds = Order::query()
                ->where('event_id', $selectedEventId)
                ->where('status', 'paid')
                ->ticketSales()
                ->selectRaw('MIN(DATE(created_at)) as first_at, MAX(DATE(created_at)) as last_at')
                ->first();
        }

        $payoutsQuery = $profile
            ? Payout::query()->where('organizer_id', $profile->id)
            : null;

        if ($payoutsQuery && $selectedEventId) {
            if ($orderDateBounds?->first_at && $orderDateBounds?->last_at) {
                $payoutsQuery
                    ->whereDate('period_end', '>=', $orderDateBounds->first_at)
                    ->whereDate('period_start', '<=', $orderDateBounds->last_at);
            } else {
                $payoutsQuery->whereRaw('1 = 0');
            }
        }

        $paidOut = $payoutsQuery
            ? (float) (clone $payoutsQuery)->where('status', 'paid')->sum('net_payout')
            : 0.0;
        $available = max(0, $net - $paidOut);

        $rate = $profile
            ? $profile->effectiveCommissionRate()
            : (float) Setting::getValue('default_commission_rate', 5);

        $payouts = $payoutsQuery
            ? (clone $payoutsQuery)->latest()->take(20)->get()
            : collect();

        $orders = $eventIds->isEmpty()
            ? collect()
            : Order::query()
                ->with('event')
                ->whereIn('event_id', $eventIds)
                ->where('status', 'paid')
                ->ticketSales()
                ->latest()
                ->take(20)
                ->get();

        $ticketTypesBought = collect();
        $totalBoughtCards = 0;
        if (! $eventIds->isEmpty()) {
            $ticketTypesBought = OrderItem::query()
                ->selectRaw('ticket_types.name as name, COALESCE(SUM(order_items.quantity), 0) as bought')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('ticket_types', 'ticket_types.id', '=', 'order_items.ticket_type_id')
                ->whereIn('orders.event_id', $eventIds)
                ->where('orders.status', 'paid')
                ->whereNotIn('orders.source', ['invitation', 'private_event', 'organizer_package'])
                ->groupBy('ticket_types.name')
                ->orderBy('ticket_types.name')
                ->get()
                ->map(fn ($row) => [
                    'name' => (string) $row->name,
                    'bought' => (int) $row->bought,
                ]);

            $totalBoughtCards = (int) $ticketTypesBought->sum('bought');
        }

        return view('organizer.earnings', compact(
            'gross',
            'commission',
            'net',
            'available',
            'rate',
            'payouts',
            'orders',
            'events',
            'selectedEventId',
            'selectedEvent',
            'ticketTypesBought',
            'totalBoughtCards',
        ));
    }
}
