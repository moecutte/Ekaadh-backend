<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $profile = $user->organizerProfile;

        $eventIds = $profile?->events()->pluck('id') ?? collect();

        $stats = [
            'events' => $eventIds->count(),
            'tickets_sold' => $eventIds->isEmpty() ? 0 : (int) DB::table('ticket_types')->whereIn('event_id', $eventIds)->sum('quantity_sold'),
            'gross' => $eventIds->isEmpty() ? 0 : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->sum('subtotal'),
            'commission' => $eventIds->isEmpty() ? 0 : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->sum('commission_amount'),
        ];
        $stats['net'] = $stats['gross'] - $stats['commission'];

        $recentOrders = $eventIds->isEmpty()
            ? collect()
            : Order::query()
                ->with('event')
                ->whereIn('event_id', $eventIds)
                ->where('status', 'paid')
                ->latest()
                ->take(8)
                ->get();

        $defaultCommission = (float) Setting::getValue('default_commission_rate', 10);

        return view('organizer.dashboard', compact('profile', 'stats', 'recentOrders', 'defaultCommission'));
    }
}
