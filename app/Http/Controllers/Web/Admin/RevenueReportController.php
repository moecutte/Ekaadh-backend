<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrganizerProfile;
use App\Models\Payout;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevenueReportController extends Controller
{
    public function index(Request $request): View
    {
        $preset = $request->string('preset')->toString() ?: 'this_month';
        [$from, $to, $preset] = $this->resolveDateRange($request, $preset);

        $channel = $request->string('channel')->toString() ?: 'all';
        if (! in_array($channel, ['all', 'public', 'private'], true)) {
            $channel = 'all';
        }

        $organizerId = $request->integer('organizer_id') ?: null;
        $categoryId = $request->integer('private_category_id') ?: null;
        $paymentMethod = $request->string('payment_method')->trim()->toString() ?: null;
        $search = $request->string('q')->trim()->toString() ?: null;

        $paidBase = fn () => $this->paidOrdersQuery($from, $to, $channel, $organizerId, $categoryId, $paymentMethod, $search);

        $summary = [
            'orders' => (clone $paidBase())->count(),
            'gross' => (float) (clone $paidBase())->sum('orders.subtotal'),
            'service_fees' => (float) (clone $paidBase())->sum('orders.service_fee'),
            'commission' => (float) (clone $paidBase())->sum('orders.commission_amount'),
            'total_collected' => (float) (clone $paidBase())->sum('orders.total_amount'),
        ];
        $summary['platform_revenue'] = $summary['commission'] + $summary['service_fees'];
        $summary['organizer_share'] = max(0, $summary['gross'] - $summary['commission']);
        $summary['avg_order'] = $summary['orders'] > 0
            ? $summary['total_collected'] / $summary['orders']
            : 0.0;

        // Channel split (respects date + other filters except channel itself)
        $publicOnly = fn () => $this->paidOrdersQuery($from, $to, 'public', $organizerId, $categoryId, $paymentMethod, $search);
        $privateOnly = fn () => $this->paidOrdersQuery($from, $to, 'private', $organizerId, $categoryId, $paymentMethod, $search);

        $summary['public_orders'] = (clone $publicOnly())->count();
        $summary['public_collected'] = (float) (clone $publicOnly())->sum('orders.total_amount');
        $summary['private_orders'] = (clone $privateOnly())->count();
        $summary['private_collected'] = (float) (clone $privateOnly())->sum('orders.total_amount');
        $summary['private_gross'] = (float) (clone $privateOnly())->sum('orders.subtotal');
        $summary['private_fees'] = (float) (clone $privateOnly())->sum('orders.service_fee');

        $summary['private_tickets'] = (int) $this->privateTicketsQuery($from, $to, $categoryId, $search)->count();

        $paidOut = (float) Payout::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', '>=', $from->toDateString())
            ->whereDate('paid_at', '<=', $to->toDateString())
            ->when($organizerId, fn ($q) => $q->where('organizer_id', $organizerId))
            ->when($channel === 'private', fn ($q) => $q->whereRaw('1 = 0'))
            ->sum('net_payout');
        $summary['payouts'] = $paidOut;

        $groupByDay = $from->diffInDays($to) <= 45;
        $trend = $this->trendSeries($from, $to, $channel, $organizerId, $categoryId, $paymentMethod, $search, $groupByDay);

        $byMethod = (clone $paidBase())
            ->select([
                DB::raw("COALESCE(orders.payment_method, payments.provider, 'unknown') as method"),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                DB::raw('SUM(orders.total_amount) as total_collected'),
                DB::raw('SUM(orders.commission_amount) as commission'),
                DB::raw('SUM(orders.service_fee) as service_fees'),
            ])
            ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
            ->groupBy(DB::raw("COALESCE(orders.payment_method, payments.provider, 'unknown')"))
            ->orderByDesc('total_collected')
            ->get();

        $byOrganizer = Order::query()
            ->select([
                'organizer_profiles.id',
                'organizer_profiles.business_name',
                DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                DB::raw('SUM(orders.subtotal) as gross'),
                DB::raw('SUM(orders.commission_amount) as commission'),
                DB::raw('SUM(orders.service_fee) as service_fees'),
                DB::raw('SUM(orders.total_amount) as total_collected'),
            ])
            ->join('events', 'events.id', '=', 'orders.event_id')
            ->join('organizer_profiles', 'organizer_profiles.id', '=', 'events.organizer_id')
            ->where('orders.status', 'paid')
            ->where('events.is_private', false)
            ->whereDate('orders.created_at', '>=', $from->toDateString())
            ->whereDate('orders.created_at', '<=', $to->toDateString())
            ->when($channel === 'private', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($organizerId, fn ($q) => $q->where('organizer_profiles.id', $organizerId))
            ->when($paymentMethod, fn ($q) => $q->where('orders.payment_method', $paymentMethod))
            ->when($search, function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('organizer_profiles.business_name', 'like', $like)
                        ->orWhere('events.title', 'like', $like)
                        ->orWhere('orders.order_number', 'like', $like);
                });
            })
            ->groupBy('organizer_profiles.id', 'organizer_profiles.business_name')
            ->orderByDesc('gross')
            ->paginate(10, ['*'], 'organizers_page')
            ->withQueryString();

        $byEvent = Order::query()
            ->select([
                'events.id',
                'events.title',
                'events.is_private',
                DB::raw('COALESCE(organizer_profiles.business_name, owners.name, \'Customer\') as seller_name'),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                DB::raw('SUM(orders.subtotal) as gross'),
                DB::raw('SUM(orders.commission_amount) as commission'),
                DB::raw('SUM(orders.service_fee) as service_fees'),
                DB::raw('SUM(orders.total_amount) as total_collected'),
            ])
            ->join('events', 'events.id', '=', 'orders.event_id')
            ->leftJoin('organizer_profiles', 'organizer_profiles.id', '=', 'events.organizer_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'events.owner_user_id')
            ->where('orders.status', 'paid')
            ->whereDate('orders.created_at', '>=', $from->toDateString())
            ->whereDate('orders.created_at', '<=', $to->toDateString())
            ->when($channel === 'private', fn ($q) => $q->where(function ($q) {
                $q->where('events.is_private', true)->orWhere('orders.source', 'private_event');
            }))
            ->when($channel === 'public', fn ($q) => $q->where('events.is_private', false)
                ->where(function ($q) {
                    $q->whereNull('orders.source')->orWhere('orders.source', '!=', 'private_event');
                }))
            ->when($organizerId, fn ($q) => $q->where('events.organizer_id', $organizerId))
            ->when($categoryId, fn ($q) => $q->where('events.private_event_category_id', $categoryId))
            ->when($paymentMethod, fn ($q) => $q->where('orders.payment_method', $paymentMethod))
            ->when($search, function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('events.title', 'like', $like)
                        ->orWhere('organizer_profiles.business_name', 'like', $like)
                        ->orWhere('owners.name', 'like', $like)
                        ->orWhere('orders.order_number', 'like', $like);
                });
            })
            ->groupBy(
                'events.id',
                'events.title',
                'events.is_private',
                'organizer_profiles.business_name',
                'owners.name'
            )
            ->orderByDesc('gross')
            ->paginate(12, ['*'], 'events_page')
            ->withQueryString();

        $orders = (clone $paidBase())
            ->select('orders.*')
            ->with([
                'event.organizer',
                'event.owner',
                'event.privateEventCategory',
                'payment',
                'user',
            ])
            ->orderByDesc('orders.created_at')
            ->paginate(15, ['*'], 'orders_page')
            ->withQueryString();

        $privateTickets = $this->privateTicketsQuery($from, $to, $categoryId, $search)
            ->when($channel === 'public', fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['event.owner', 'event.privateEventCategory', 'invitation'])
            ->orderByDesc('tickets.created_at')
            ->paginate(15, ['*'], 'tickets_page')
            ->withQueryString();

        $organizers = OrganizerProfile::query()
            ->where('approval_status', 'approved')
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        $privateCategories = Category::query()
            ->where('parent_id', Category::privateRoot()?->id ?? 0)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = Order::query()
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method');

        return view('admin.revenue.index', [
            'summary' => $summary,
            'trend' => $trend,
            'groupByDay' => $groupByDay,
            'byOrganizer' => $byOrganizer,
            'byEvent' => $byEvent,
            'byMethod' => $byMethod,
            'orders' => $orders,
            'privateTickets' => $privateTickets,
            'organizers' => $organizers,
            'privateCategories' => $privateCategories,
            'paymentMethods' => $paymentMethods,
            'from' => $from,
            'to' => $to,
            'preset' => $preset,
            'channel' => $channel,
            'organizerId' => $organizerId,
            'categoryId' => $categoryId,
            'paymentMethod' => $paymentMethod,
            'search' => $search,
        ]);
    }

    private function paidOrdersQuery(
        Carbon $from,
        Carbon $to,
        string $channel,
        ?int $organizerId,
        ?int $categoryId,
        ?string $paymentMethod,
        ?string $search,
    ): Builder {
        return Order::query()
            ->where('orders.status', 'paid')
            ->whereDate('orders.created_at', '>=', $from->toDateString())
            ->whereDate('orders.created_at', '<=', $to->toDateString())
            ->when($channel === 'private', fn ($q) => $q->where(function ($q) {
                $q->where('orders.source', 'private_event')
                    ->orWhereHas('event', fn ($e) => $e->where('is_private', true));
            }))
            ->when($channel === 'public', fn ($q) => $q->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('orders.source')
                        ->orWhereNotIn('orders.source', ['private_event']);
                })->whereHas('event', fn ($e) => $e->where('is_private', false));
            }))
            ->when($organizerId, fn ($q) => $q->whereHas('event', fn ($e) => $e->where('organizer_id', $organizerId)))
            ->when($categoryId, fn ($q) => $q->whereHas('event', fn ($e) => $e->where('private_event_category_id', $categoryId)))
            ->when($paymentMethod, fn ($q) => $q->where('orders.payment_method', $paymentMethod))
            ->when($search, function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('orders.order_number', 'like', $like)
                        ->orWhere('orders.buyer_name', 'like', $like)
                        ->orWhere('orders.buyer_phone', 'like', $like)
                        ->orWhere('orders.buyer_email', 'like', $like)
                        ->orWhereHas('event', function ($e) use ($like) {
                            $e->where('title', 'like', $like)
                                ->orWhereHas('organizer', fn ($o) => $o->where('business_name', 'like', $like))
                                ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', $like));
                        });
                });
            });
    }

    private function privateTicketsQuery(
        Carbon $from,
        Carbon $to,
        ?int $categoryId,
        ?string $search,
    ): Builder {
        return Ticket::query()
            ->whereHas('event', function ($e) use ($categoryId) {
                $e->where('is_private', true)
                    ->when($categoryId, fn ($q) => $q->where('private_event_category_id', $categoryId));
            })
            ->whereDate('tickets.created_at', '>=', $from->toDateString())
            ->whereDate('tickets.created_at', '<=', $to->toDateString())
            ->when($search, function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('tickets.ticket_code', 'like', $like)
                        ->orWhere('tickets.holder_name', 'like', $like)
                        ->orWhere('tickets.ticket_type_name', 'like', $like)
                        ->orWhereHas('event', fn ($e) => $e->where('title', 'like', $like));
                });
            });
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveDateRange(Request $request, string $preset): array
    {
        $today = now()->startOfDay();

        if ($preset === 'custom' || ($request->filled('date_from') && $request->filled('date_to') && $preset === '')) {
            $preset = 'custom';
        }

        return match ($preset) {
            'today' => [$today->copy(), $today->copy()->endOfDay(), 'today'],
            'last_7' => [$today->copy()->subDays(6), $today->copy()->endOfDay(), 'last_7'],
            'last_30' => [$today->copy()->subDays(29), $today->copy()->endOfDay(), 'last_30'],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
                'last_month',
            ],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay(), 'this_year'],
            'custom' => [
                Carbon::parse($request->input('date_from', $today->toDateString()))->startOfDay(),
                Carbon::parse($request->input('date_to', $today->toDateString()))->endOfDay(),
                'custom',
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfDay(), 'this_month'],
        };
    }

    /**
     * @return array{labels: list<string>, gross: list<float>, commission: list<float>, platform: list<float>, private_gross: list<float>}
     */
    private function trendSeries(
        Carbon $from,
        Carbon $to,
        string $channel,
        ?int $organizerId,
        ?int $categoryId,
        ?string $paymentMethod,
        ?string $search,
        bool $groupByDay,
    ): array {
        $periodExpr = $groupByDay
            ? 'DATE(orders.created_at)'
            : "DATE_FORMAT(orders.created_at, '%Y-%m')";

        $rows = $this->paidOrdersQuery($from, $to, $channel, $organizerId, $categoryId, $paymentMethod, $search)
            ->select([
                DB::raw("{$periodExpr} as period"),
                DB::raw('SUM(orders.subtotal) as gross'),
                DB::raw('SUM(orders.commission_amount) as commission'),
                DB::raw('SUM(orders.service_fee) as service_fees'),
                DB::raw('SUM(CASE WHEN orders.source = \'private_event\' OR events.is_private = 1 THEN orders.subtotal ELSE 0 END) as private_gross'),
            ])
            ->leftJoin('events', 'events.id', '=', 'orders.event_id')
            ->groupBy(DB::raw($periodExpr))
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $labels = [];
        $gross = [];
        $commission = [];
        $platform = [];
        $privateGross = [];

        if ($groupByDay) {
            $cursor = $from->copy()->startOfDay();
            $end = $to->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $row = $rows->get($key);
                $labels[] = $cursor->format('M j');
                $g = (float) ($row->gross ?? 0);
                $c = (float) ($row->commission ?? 0);
                $s = (float) ($row->service_fees ?? 0);
                $gross[] = $g;
                $commission[] = $c;
                $platform[] = $c + $s;
                $privateGross[] = (float) ($row->private_gross ?? 0);
                $cursor->addDay();
            }
        } else {
            $cursor = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $row = $rows->get($key);
                $labels[] = $cursor->format('M Y');
                $g = (float) ($row->gross ?? 0);
                $c = (float) ($row->commission ?? 0);
                $s = (float) ($row->service_fees ?? 0);
                $gross[] = $g;
                $commission[] = $c;
                $platform[] = $c + $s;
                $privateGross[] = (float) ($row->private_gross ?? 0);
                $cursor->addMonth();
            }
        }

        return compact('labels', 'gross', 'commission', 'platform', 'privateGross');
    }
}
