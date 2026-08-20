<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\PaginatesFilteredLists;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    use PaginatesFilteredLists;

    public function index(Request $request): View
    {
        $scope = $this->scope($request);

        $query = Order::query()->commerce();
        $this->applyScope($query, $scope);
        $this->applyFilters($query, $request);

        $stats = (clone $query)->toBase()->selectRaw("
            COUNT(*) as `count`,
            COALESCE(SUM(CASE WHEN orders.status = 'paid' THEN orders.total_amount ELSE 0 END), 0) as paid,
            COALESCE(SUM(CASE WHEN orders.status = 'paid' THEN orders.commission_amount ELSE 0 END), 0) as commission,
            COALESCE(SUM(CASE WHEN orders.status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN orders.status = 'failed' THEN 1 ELSE 0 END), 0) as failed
        ")->first();

        $totals = [
            'count' => (int) ($stats->count ?? 0),
            'paid' => (float) ($stats->paid ?? 0),
            'commission' => (float) ($stats->commission ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'failed' => (int) ($stats->failed ?? 0),
        ];

        $orders = $this->paginateFiltered(
            (clone $query)
                ->select([
                    'orders.id',
                    'orders.order_number',
                    'orders.buyer_name',
                    'orders.buyer_phone',
                    'orders.user_id',
                    'orders.event_id',
                    'orders.total_amount',
                    'orders.commission_amount',
                    'orders.status',
                    'orders.payment_method',
                    'orders.source',
                    'orders.created_at',
                ])
                ->with([
                    'event:id,title,organizer_id,is_private',
                    'event.organizer:id,business_name',
                    'payment:id,order_id,status,provider',
                ])
                ->withSum('items as tickets_qty', 'quantity')
                ->orderByDesc('orders.id'),
            $totals['count'],
            $request
        );

        $filterOptions = [
            'organizers' => OrganizerProfile::query()
                ->orderBy('business_name')
                ->toBase()
                ->get(['id', 'business_name']),
            'events' => Event::query()
                ->when($request->integer('organizer_id'), fn ($q, $id) => $q->where('organizer_id', $id))
                ->orderByDesc('id')
                ->limit(80)
                ->toBase()
                ->get(['id', 'title']),
        ];

        $filtersActive = $this->filtersActive($request);
        $ops = $this->opsSnapshot();
        $scopes = $this->scopeLinks($request, $ops);

        return view('admin.orders.index', compact(
            'orders',
            'totals',
            'filterOptions',
            'filtersActive',
            'ops',
            'scope',
            'scopes'
        ));
    }

    public function show(string $order): View
    {
        $model = Order::query()
            ->when(
                ctype_digit($order),
                fn ($q) => $q->where(fn ($inner) => $inner->where('id', (int) $order)->orWhere('order_number', $order)),
                fn ($q) => $q->where('order_number', $order)
            )
            ->with([
                'event.organizer',
                'items.ticketType',
                'items.tickets',
                'payment',
                'user',
            ])
            ->firstOrFail();

        $model->payment?->makeVisible('raw_response');

        return view('admin.orders.show', ['order' => $model]);
    }

    private function scope(Request $request): string
    {
        $scope = $request->string('scope')->toString();

        return in_array($scope, ['today', 'paid_today', 'attention', 'private'], true) ? $scope : '';
    }

    private function applyScope(Builder $query, string $scope): void
    {
        $today = now()->startOfDay();

        match ($scope) {
            'today' => $query->where('orders.created_at', '>=', $today),
            'paid_today' => $query->where('orders.status', 'paid')->where('orders.created_at', '>=', $today),
            'attention' => $query->whereIn('orders.status', ['pending', 'failed'])
                ->where('orders.created_at', '>=', now()->subHours(48)),
            'private' => $query->where('orders.source', 'private_event'),
            default => null,
        };
    }

    /**
     * @return array<string, int|float>
     */
    private function opsSnapshot(): array
    {
        $today = now()->startOfDay();
        $row = Order::query()
            ->commerce()
            ->where('created_at', '>=', $today)
            ->toBase()
            ->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) as paid_count,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_volume,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed
            ")
            ->first();

        $attention = (int) Order::query()
            ->commerce()
            ->whereIn('status', ['pending', 'failed'])
            ->where('created_at', '>=', now()->subHours(48))
            ->count();

        return [
            'today_total' => (int) ($row->total ?? 0),
            'today_paid_count' => (int) ($row->paid_count ?? 0),
            'today_paid_volume' => (float) ($row->paid_volume ?? 0),
            'today_pending' => (int) ($row->pending ?? 0),
            'today_failed' => (int) ($row->failed ?? 0),
            'attention' => $attention,
        ];
    }

    /**
     * @param  array<string, int|float>  $ops
     * @return list<array{key: string, label: string, url: string, active: bool, count: int|null}>
     */
    private function scopeLinks(Request $request, array $ops): array
    {
        $base = $request->except(['scope', 'page', 'status', 'date_from', 'date_to']);
        $active = $this->scope($request);

        return [
            ['key' => '', 'label' => 'All', 'url' => route('admin.orders.index', $base), 'active' => $active === '', 'count' => null],
            ['key' => 'today', 'label' => 'Today', 'url' => route('admin.orders.index', $base + ['scope' => 'today']), 'active' => $active === 'today', 'count' => $ops['today_total']],
            ['key' => 'paid_today', 'label' => 'Paid today', 'url' => route('admin.orders.index', $base + ['scope' => 'paid_today']), 'active' => $active === 'paid_today', 'count' => $ops['today_paid_count']],
            ['key' => 'attention', 'label' => 'Needs attention', 'url' => route('admin.orders.index', $base + ['scope' => 'attention']), 'active' => $active === 'attention', 'count' => $ops['attention']],
            ['key' => 'private', 'label' => 'Private events', 'url' => route('admin.orders.index', $base + ['scope' => 'private']), 'active' => $active === 'private', 'count' => null],
        ];
    }

    private function filtersActive(Request $request): bool
    {
        return collect($request->only([
            'q', 'status', 'customer_type', 'organizer_id', 'event_id', 'date_from', 'date_to', 'scope',
        ]))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = $request->string('q')->trim()->toString()) {
            $this->ensureEventJoin($query);
            $this->ensureOrganizerJoin($query);
            $query->where(function ($q) use ($search) {
                $q->where('orders.order_number', 'like', $search.'%')
                    ->orWhere('orders.buyer_name', 'like', '%'.$search.'%')
                    ->orWhere('orders.buyer_phone', 'like', '%'.$search.'%')
                    ->orWhere('orders.buyer_email', 'like', '%'.$search.'%')
                    ->orWhere('events.title', 'like', '%'.$search.'%')
                    ->orWhere('organizer_profiles.business_name', 'like', '%'.$search.'%');
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('orders.status', $status);
        }

        if ($customerType = $request->string('customer_type')->toString()) {
            if ($customerType === 'guest') {
                $query->whereNull('orders.user_id');
            } elseif ($customerType === 'user') {
                $query->whereNotNull('orders.user_id');
            }
        }

        if ($organizerId = $request->integer('organizer_id')) {
            $this->ensureEventJoin($query);
            $query->where('events.organizer_id', $organizerId);
        }

        if ($eventId = $request->integer('event_id')) {
            $query->where('orders.event_id', $eventId);
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->where('orders.created_at', '>=', $from.' 00:00:00');
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->where('orders.created_at', '<=', $to.' 23:59:59');
        }
    }

    private function ensureEventJoin(Builder $query): void
    {
        if ($this->queryHasJoin($query, 'events')) {
            return;
        }

        $query->join('events', 'events.id', '=', 'orders.event_id');
    }

    private function ensureOrganizerJoin(Builder $query): void
    {
        $this->ensureEventJoin($query);

        if ($this->queryHasJoin($query, 'organizer_profiles')) {
            return;
        }

        $query->leftJoin('organizer_profiles', 'organizer_profiles.id', '=', 'events.organizer_id');
    }
}
