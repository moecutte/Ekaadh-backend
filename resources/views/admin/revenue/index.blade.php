@extends('layouts.admin')
@section('title', 'Revenue Report')
@section('heading', 'Revenue Report')

@section('content')
@php
    $filtersActive = $channel !== 'all'
        || $organizerId
        || $categoryId
        || $paymentMethod
        || $search
        || $preset === 'custom';
@endphp

<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach([
            'today' => 'Today',
            'last_7' => 'Last 7 days',
            'last_30' => 'Last 30 days',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'this_year' => 'This year',
            'custom' => 'Custom',
        ] as $key => $label)
            <button type="submit" name="preset" value="{{ $key }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors {{ $preset === $key ? 'bg-brand text-white border-brand' : 'bg-white text-mute border-slate-200 hover:border-brand hover:text-brand' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-2 mb-4 items-center">
        <span class="text-[11px] font-bold uppercase text-mute mr-1">Channel</span>
        @foreach(['all' => 'All sales', 'public' => 'Public events', 'private' => 'Private tickets'] as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['channel' => $key, 'orders_page' => null, 'events_page' => null, 'organizers_page' => null, 'tickets_page' => null]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors {{ $channel === $key ? 'bg-ink text-white border-ink' : 'bg-white text-mute border-slate-200 hover:border-ink hover:text-ink' }}">
                {{ $label }}
            </a>
        @endforeach
        <input type="hidden" name="channel" value="{{ $channel }}">
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="xl:col-span-2">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Search</label>
            <input type="text" name="q" value="{{ $search }}" placeholder="Order #, buyer, event, organizer…"
                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">From</label>
            <input type="date" name="date_from" value="{{ $from->toDateString() }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">To</label>
            <input type="date" name="date_to" value="{{ $to->toDateString() }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Payment method</label>
            <select name="payment_method" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All methods</option>
                @foreach($paymentMethods as $m)
                    <option value="{{ $m }}" @selected($paymentMethod === $m)>{{ ucfirst($m) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Organizer</label>
            <select name="organizer_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All organizers</option>
                @foreach($organizers as $org)
                    <option value="{{ $org->id }}" @selected((string) $organizerId === (string) $org->id)>{{ $org->business_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Private category</label>
            <select name="private_category_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All categories</option>
                @foreach($privateCategories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 xl:col-span-6 flex flex-wrap items-center gap-2 pt-1">
            <button type="submit" name="preset" value="{{ $preset === 'custom' || request()->filled('date_from') ? 'custom' : $preset }}" class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Apply filters</button>
            @if($filtersActive || $preset !== 'this_month')
                <a href="{{ route('admin.revenue.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Reset</a>
            @endif
            <p class="text-xs text-mute ml-1">
                Paid orders
                <span class="font-semibold text-ink">{{ $from->format('M j, Y') }}</span>
                –
                <span class="font-semibold text-ink">{{ $to->format('M j, Y') }}</span>
                @if($channel !== 'all')
                    · <span class="font-semibold text-ink">{{ $channel === 'private' ? 'Private tickets' : 'Public events' }}</span>
                @endif
            </p>
        </div>
    </div>
</form>

{{-- Summary cards --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
    @foreach([
        ['Gross sales', '$'.number_format($summary['gross'], 0), 'Ticket subtotals before fees', 'text-sky-600'],
        ['Platform revenue', '$'.number_format($summary['platform_revenue'], 0), 'Commission + service fees', 'text-brand'],
        ['Total collected', '$'.number_format($summary['total_collected'], 0), number_format($summary['orders']).' paid orders', 'text-ink'],
        ['Avg order', '$'.number_format($summary['avg_order'], 0), 'Across filtered paid orders', 'text-violet-600'],
    ] as [$label, $value, $hint, $color])
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <div class="text-xs text-mute">{{ $label }}</div>
            <div class="text-2xl font-black mt-1 {{ $color }}">{{ $value }}</div>
            <div class="text-[11px] text-mute mt-1">{{ $hint }}</div>
        </div>
    @endforeach
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Public events</div>
        <div class="text-xl font-black mt-1">${{ number_format($summary['public_collected'], 0) }}</div>
        <div class="text-[11px] text-mute mt-1">{{ number_format($summary['public_orders']) }} paid orders</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm ring-1 ring-brand/10">
        <div class="text-xs text-mute">Private tickets</div>
        <div class="text-xl font-black mt-1 text-brand">${{ number_format($summary['private_collected'], 0) }}</div>
        <div class="text-[11px] text-mute mt-1">
            {{ number_format($summary['private_orders']) }} orders · {{ number_format($summary['private_tickets']) }} tickets issued
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Private fees</div>
        <div class="text-xl font-black mt-1">${{ number_format($summary['private_fees'], 0) }}</div>
        <div class="text-[11px] text-mute mt-1">Service fees on private checkouts</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Organizer payouts</div>
        <div class="text-xl font-black mt-1">${{ number_format($summary['payouts'], 0) }}</div>
        <div class="text-[11px] text-mute mt-1">Paid out in this period</div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-5">
    <div class="mb-4">
        <h3 class="text-sm font-bold">Revenue trend</h3>
        <p class="text-xs text-mute">{{ $groupByDay ? 'Daily' : 'Monthly' }} gross, platform revenue, and private ticket sales</p>
    </div>
    <div class="h-64">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-bold">By payment method</h3>
            <span class="text-[10px] font-bold text-mute uppercase">{{ $byMethod->count() }} methods</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                    <tr>
                        <th class="text-left px-4 py-3">Method</th>
                        <th class="text-left px-4 py-3">Orders</th>
                        <th class="text-left px-4 py-3">Collected</th>
                        <th class="text-left px-4 py-3">Fees / Comm</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byMethod as $row)
                        <tr class="border-t border-slate-50">
                            <td class="px-4 py-3 font-semibold uppercase text-xs">{{ $row->method }}</td>
                            <td class="px-4 py-3 text-mute">{{ number_format($row->orders_count) }}</td>
                            <td class="px-4 py-3 font-bold">${{ number_format((float) $row->total_collected, 0) }}</td>
                            <td class="px-4 py-3 text-brand font-bold">${{ number_format((float) $row->commission + (float) $row->service_fees, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-mute">No paid orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-bold">Top organizers</h3>
            <span class="text-[10px] font-bold text-mute uppercase">Public events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                    <tr>
                        <th class="text-left px-4 py-3">Organizer</th>
                        <th class="text-left px-4 py-3">Orders</th>
                        <th class="text-left px-4 py-3">Gross</th>
                        <th class="text-left px-4 py-3">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byOrganizer as $row)
                        <tr class="border-t border-slate-50">
                            <td class="px-4 py-3 font-semibold">{{ $row->business_name }}</td>
                            <td class="px-4 py-3 text-mute">{{ number_format($row->orders_count) }}</td>
                            <td class="px-4 py-3 font-bold">${{ number_format((float) $row->gross, 0) }}</td>
                            <td class="px-4 py-3 text-brand font-bold">${{ number_format((float) $row->commission, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-mute">No organizer sales in this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pager', ['paginator' => $byOrganizer])
    </div>
</div>

{{-- Events table --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-slate-50 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-bold">Sales by event</h3>
            <p class="text-xs text-mute mt-0.5">Public and private invitation events · {{ number_format($byEvent->total()) }} total</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Seller / Owner</th>
                    <th class="text-left px-4 py-3">Orders</th>
                    <th class="text-left px-4 py-3">Gross</th>
                    <th class="text-left px-4 py-3">Collected</th>
                    <th class="text-left px-4 py-3">Platform</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byEvent as $row)
                    <tr class="border-t border-slate-50">
                        <td class="px-4 py-3 font-semibold max-w-[200px] truncate">{{ $row->title }}</td>
                        <td class="px-4 py-3">
                            @if($row->is_private)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-brand/10 text-brand">Private</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-mute">Public</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-mute">{{ $row->seller_name }}</td>
                        <td class="px-4 py-3 text-mute">{{ number_format($row->orders_count) }}</td>
                        <td class="px-4 py-3 font-bold">${{ number_format((float) $row->gross, 0) }}</td>
                        <td class="px-4 py-3 font-bold">${{ number_format((float) $row->total_collected, 0) }}</td>
                        <td class="px-4 py-3 text-brand font-bold">${{ number_format((float) $row->commission + (float) $row->service_fees, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-mute">No event sales in this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $byEvent])
</div>

{{-- Orders detail --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-slate-50">
        <h3 class="text-sm font-bold">Paid orders</h3>
        <p class="text-xs text-mute mt-0.5">{{ number_format($orders->total()) }} matching orders</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Order</th>
                    <th class="text-left px-4 py-3">Buyer</th>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Channel</th>
                    <th class="text-left px-4 py-3">Amount</th>
                    <th class="text-left px-4 py-3">Platform</th>
                    <th class="text-left px-4 py-3">Method</th>
                    <th class="text-left px-4 py-3">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $isPrivate = $order->source === 'private_event' || (bool) $order->event?->is_private;
                        $platformCut = (float) $order->commission_amount + (float) $order->service_fee;
                    @endphp
                    <tr class="border-t border-slate-50 align-top hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs text-mute">{{ $order->order_number }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $order->buyer_name ?: '—' }}</div>
                            <div class="text-xs text-mute">{{ $order->buyer_phone }}</div>
                        </td>
                        <td class="px-4 py-3 text-mute">
                            <div class="max-w-[180px] truncate font-medium text-ink">{{ $order->event?->title }}</div>
                            <div class="text-xs">
                                @if($isPrivate)
                                    {{ $order->event?->owner?->name ?: 'Customer' }}
                                    @if($order->event?->privateEventCategory)
                                        · {{ $order->event->privateEventCategory->name }}
                                    @endif
                                @else
                                    {{ $order->event?->organizer?->business_name }}
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($isPrivate)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-brand/10 text-brand">Private</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-mute">Public</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-bold">${{ number_format((float) $order->total_amount, 0) }}</td>
                        <td class="px-4 py-3 text-brand font-bold">${{ number_format($platformCut, 0) }}</td>
                        <td class="px-4 py-3 text-mute text-xs uppercase">{{ $order->payment_method ?: ($order->payment?->provider ?: '—') }}</td>
                        <td class="px-4 py-3 text-mute text-xs whitespace-nowrap">{{ $order->created_at?->format('M j, Y g:ia') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-mute">No paid orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $orders])
</div>

{{-- Private tickets issued --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5" x-show="true">
    <div class="px-5 py-4 border-b border-slate-50 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-bold">Private tickets issued</h3>
            <p class="text-xs text-mute mt-0.5">Invitation tickets for private events · {{ number_format($privateTickets->total()) }} total</p>
        </div>
        @if($channel === 'public')
            <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-amber-50 text-amber-700">Hidden while viewing Public only — switch channel to All or Private</span>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Ticket</th>
                    <th class="text-left px-4 py-3">Holder</th>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Issued</th>
                </tr>
            </thead>
            <tbody>
                @forelse($privateTickets as $ticket)
                    <tr class="border-t border-slate-50 align-top hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-mono text-xs">{{ $ticket->ticket_code }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $ticket->holder_name ?: ($ticket->invitation?->guest_name ?: '—') }}</div>
                            @if($ticket->invitation?->guest_phone)
                                <div class="text-xs text-mute">{{ $ticket->invitation->guest_phone }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-mute">
                            <div class="max-w-[180px] truncate font-medium text-ink">{{ $ticket->event?->title }}</div>
                            <div class="text-xs">{{ $ticket->event?->owner?->name }}</div>
                        </td>
                        <td class="px-4 py-3 text-mute">{{ $ticket->ticket_type_name }}</td>
                        <td class="px-4 py-3">
                            @php $tsc = ['valid'=>'bg-emerald-50 text-emerald-700','used'=>'bg-slate-100 text-mute','cancelled'=>'bg-red-50 text-red-600']; @endphp
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $tsc[$ticket->status] ?? 'bg-slate-50 text-mute' }}">{{ $ticket->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-mute text-xs whitespace-nowrap">{{ $ticket->created_at?->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-mute">No private tickets in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $privateTickets])
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const labels = @json($trend['labels']);
    const ctx = document.getElementById('revenueChart');
    if (!ctx || typeof Chart === 'undefined') return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Gross sales',
                    data: @json($trend['gross']),
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.10)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: labels.length > 31 ? 0 : 3,
                },
                {
                    label: 'Platform revenue',
                    data: @json($trend['platform']),
                    borderColor: '#323891',
                    backgroundColor: 'rgba(50, 56, 145, 0.10)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: labels.length > 31 ? 0 : 3,
                },
                {
                    label: 'Private ticket sales',
                    data: @json($trend['privateGross']),
                    borderColor: '#d97706',
                    backgroundColor: 'rgba(217, 119, 6, 0.08)',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2,
                    borderDash: [5, 4],
                    pointRadius: labels.length > 31 ? 0 : 3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, font: { family: 'Plus Jakarta Sans', size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: (c) => `${c.dataset.label}: $${Number(c.parsed.y).toLocaleString()}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12, font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    ticks: { callback: (v) => '$' + Number(v).toLocaleString(), font: { size: 11 } },
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                },
            },
        },
    });
})();
</script>
@endsection
