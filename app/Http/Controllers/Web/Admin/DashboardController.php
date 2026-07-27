<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'organizers' => OrganizerProfile::query()->where('approval_status', 'approved')->count(),
            'events' => Event::query()->count(),
            'tickets_sold' => (int) DB::table('ticket_types')->sum('quantity_sold'),
            'platform_revenue' => (float) Order::query()->where('status', 'paid')->sum('commission_amount'),
            'gross_sales' => (float) Order::query()->where('status', 'paid')->sum('subtotal'),
        ];

        $pendingOrgs = OrganizerProfile::query()
            ->with('user')
            ->where('approval_status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        $pendingEvents = Event::query()
            ->with('organizer')
            ->where('status', 'pending_review')
            ->latest()
            ->take(10)
            ->get();

        $recentOrders = Order::query()
            ->with(['event.organizer'])
            ->where('status', 'paid')
            ->latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'pendingOrgs', 'pendingEvents', 'recentOrders'));
    }
}
