<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
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
            ->paginate(8, ['*'], 'orgs_page')
            ->withQueryString()
            ->fragment('pending-orgs');

        $pendingEvents = Event::query()
            ->with('organizer:id,business_name')
            ->withSum('ticketTypes as tickets_sold', 'quantity_sold')
            ->withSum('ticketTypes as tickets_capacity', 'quantity_available')
            ->where('status', 'pending_review')
            ->latest()
            ->paginate(8, ['*'], 'events_page')
            ->withQueryString()
            ->fragment('pending-events');

        $recentOrders = Order::query()
            ->commerce()
            ->with('event:id,title')
            ->where('status', 'paid')
            ->latest()
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString()
            ->fragment('recent-orders');

        $organizers = OrganizerProfile::query()
            ->orderBy('business_name')
            ->toBase()
            ->get(['id', 'business_name']);

        $selectedOrganizer = null;
        $organizerId = $request->integer('organizer_id');

        if ($organizerId) {
            $selectedOrganizer = OrganizerProfile::query()->find($organizerId);
        }

        $dashboardEvents = Event::query()
            ->with('organizer:id,business_name')
            ->withSum('ticketTypes as tickets_sold', 'quantity_sold')
            ->withSum('ticketTypes as tickets_capacity', 'quantity_available')
            ->when($selectedOrganizer, fn ($q) => $q->where('organizer_id', $selectedOrganizer->id))
            ->latest()
            ->paginate(10, ['*'], 'org_events_page')
            ->withQueryString()
            ->fragment('organizer-events');

        return view('admin.dashboard', compact(
            'stats',
            'pendingOrgs',
            'pendingEvents',
            'recentOrders',
            'organizers',
            'selectedOrganizer',
            'dashboardEvents'
        ));
    }
}
