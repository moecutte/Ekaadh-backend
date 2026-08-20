<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Setting;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __invoke(): View
    {
        $profile = auth()->user()->organizerProfile;
        $eventIds = $profile?->events()->pluck('id') ?? collect();

        $gross = $eventIds->isEmpty() ? 0 : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->ticketSales()->sum('subtotal');
        $commission = $eventIds->isEmpty() ? 0 : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->ticketSales()->sum('commission_amount');
        $net = $gross - $commission;

        $paidOut = $profile
            ? (float) Payout::query()->where('organizer_id', $profile->id)->where('status', 'paid')->sum('net_payout')
            : 0.0;
        $available = max(0, $net - $paidOut);

        $rate = $profile?->commission_rate ?? Setting::getValue('default_commission_rate', 10);

        $payouts = $profile
            ? Payout::query()
                ->where('organizer_id', $profile->id)
                ->latest()
                ->take(20)
                ->get()
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

        return view('organizer.earnings', compact('gross', 'commission', 'net', 'available', 'rate', 'payouts', 'orders'));
    }
}
