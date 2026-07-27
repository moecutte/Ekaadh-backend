<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()
            ->with(['event.organizer', 'payment', 'items', 'user'])
            ->latest();

        $this->applyFilters($query, $request);

        $orders = $query->paginate(25)->withQueryString();

        $filtered = function () use ($request) {
            $q = Order::query();
            $this->applyFilters($q, $request);

            return $q;
        };

        $totals = [
            'paid' => (float) $filtered()->where('status', 'paid')->sum('total_amount'),
            'commission' => (float) $filtered()->where('status', 'paid')->sum('commission_amount'),
            'pending' => $filtered()->where('status', 'pending')->count(),
            'failed' => $filtered()->where('status', 'failed')->count(),
            'count' => $filtered()->count(),
        ];

        $filterOptions = [
            'organizers' => OrganizerProfile::query()->orderBy('business_name')->get(['id', 'business_name']),
            'events' => Event::query()->orderByDesc('event_date')->limit(100)->get(['id', 'title']),
        ];

        $filtersActive = collect($request->only([
            'q', 'status', 'payment_method', 'customer_type', 'organizer_id', 'event_id', 'date_from', 'date_to',
        ]))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        return view('admin.orders.index', compact('orders', 'totals', 'filterOptions', 'filtersActive'));
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('buyer_phone', 'like', "%{$search}%")
                    ->orWhere('buyer_email', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('event', fn ($e) => $e->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('event.organizer', fn ($o) => $o->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($method = $request->string('payment_method')->toString()) {
            $query->where(function ($q) use ($method) {
                $q->where('payment_method', $method)
                    ->orWhereHas('payment', fn ($p) => $p->where('provider', $method));
            });
        }

        if ($customerType = $request->string('customer_type')->toString()) {
            if ($customerType === 'guest') {
                $query->whereNull('user_id');
            } elseif ($customerType === 'user') {
                $query->whereNotNull('user_id');
            }
        }

        if ($organizerId = $request->integer('organizer_id')) {
            $query->whereHas('event', fn ($e) => $e->where('organizer_id', $organizerId));
        }

        if ($eventId = $request->integer('event_id')) {
            $query->where('event_id', $eventId);
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->whereDate('created_at', '<=', $to);
        }
    }
}
