@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
@php
    $firstName = explode(' ', trim((string) auth()->user()->name))[0] ?: 'there';
@endphp
<section class="panel-hero mb-6">
    <p class="relative z-10 text-[11px] font-black uppercase tracking-[0.18em] text-gold-soft/90">Platform admin</p>
    <h2 class="relative z-10 mt-1 text-2xl font-black tracking-tight text-white">Welcome back, {{ $firstName }}</h2>
    <p class="relative z-10 mt-1 text-sm text-white/70 max-w-xl">A warmer look at organizers, events, tickets, and revenue across Ekaadh today.</p>
</section>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Organizers', $stats['organizers'], '#323891', 'bg-brand', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['Total Events', $stats['events'], '#0284c7', 'bg-sky-500', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['Tickets Sold', number_format($stats['tickets_sold']), '#7c3aed', 'bg-violet-500', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
        ['Platform Revenue', '$'.number_format($stats['platform_revenue'], 0), '#b8892d', 'bg-gold', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ] as [$label, $value, $accent, $iconBg, $icon])
        <div class="panel-stat" style="--stat-accent: {{ $accent }}">
            <div class="w-9 h-9 rounded-xl {{ $iconBg }} text-white mb-3 flex items-center justify-center shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </div>
            <div class="text-2xl font-extrabold tracking-tight text-ink">{{ $value }}</div>
            <div class="text-xs text-mute mt-1">{{ $label }}</div>
        </div>
    @endforeach
</div>

<form method="GET" action="{{ route('admin.dashboard') }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5 flex flex-wrap items-end gap-3">
    <div class="flex-1 min-w-[220px]">
        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Organizer</label>
        <select name="organizer_id" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand">
            <option value="">All organizers</option>
            @foreach($organizers as $org)
                <option value="{{ $org->id }}" @selected((string) ($selectedOrganizer?->id) === (string) $org->id)>{{ $org->business_name }}</option>
            @endforeach
        </select>
    </div>
    @if($selectedOrganizer)
        <a href="{{ route('admin.organizers.show', $selectedOrganizer) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-mute hover:text-ink">Profile</a>
        <a href="{{ route('admin.events.index', ['organizer_id' => $selectedOrganizer->id, 'type' => 'public']) }}" class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">All events</a>
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear</a>
    @endif
</form>

@php
    $statusColors = [
        'draft' => 'bg-slate-50 text-mute border-slate-100',
        'pending_review' => 'bg-amber-50 text-amber-700 border-amber-100',
        'published' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'completed' => 'bg-sky-50 text-sky-700 border-sky-100',
        'cancelled' => 'bg-red-50 text-red-600 border-red-100',
    ];
@endphp
<div id="organizer-events" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-slate-50 flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-bold">{{ $selectedOrganizer ? $selectedOrganizer->business_name.' events' : 'Events' }}</h3>
        <span class="text-[11px] font-bold bg-brand/10 text-brand px-2 py-0.5 rounded-full border border-brand/20">{{ number_format($dashboardEvents->total()) }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Tickets</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dashboardEvents as $event)
                    @php
                        $sold = (int) ($event->tickets_sold ?? 0);
                        $capacity = (int) ($event->tickets_capacity ?? 0);
                        $left = max(0, $capacity - $sold);
                        $viewUrl = $event->is_private
                            ? route('admin.events.index', ['type' => 'private', 'q' => $event->title])
                            : route('events.show', $event->slug);
                    @endphp
                    <tr class="border-t border-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ $viewUrl }}" @unless($event->is_private) target="_blank" @endunless class="font-semibold text-ink hover:text-brand">{{ $event->title }}</a>
                            <div class="text-xs text-mute">{{ $event->organizer?->business_name }} · {{ $event->is_private ? 'Private' : ($event->category ?: 'Public') }}@if($event->city) · {{ $event->city }}@endif</div>
                        </td>
                        <td class="px-4 py-3 text-mute text-xs whitespace-nowrap">{{ $event->event_date?->format('M j, Y') ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-bold">{{ number_format($sold) }} sold</div>
                            <div class="text-xs text-mute">{{ number_format($left) }} left · {{ number_format($capacity) }} total</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $statusColors[$event->status] ?? 'bg-slate-50 text-mute border-slate-100' }}">{{ str_replace('_', ' ', $event->status) }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ $viewUrl }}" @unless($event->is_private) target="_blank" @endunless class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand text-white text-xs font-bold hover:bg-brand-dark">View</a>
                                @if($event->status === 'pending_review')
                                    <form method="POST" action="{{ route('admin.events.approve', $event) }}">@csrf
                                        <button class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold">Publish</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-mute">{{ $selectedOrganizer ? 'This organizer has no events yet.' : 'No events yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $dashboardEvents])
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-5">
    <div id="pending-orgs" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-bold">Pending organizer approvals</h3>
            <span class="text-[11px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-100">{{ number_format($pendingOrgs->total()) }} pending</span>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse($pendingOrgs as $org)
                <div class="px-5 py-3.5 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full overflow-hidden shrink-0">
                        @include('partials.avatar', [
                            'url' => $org->avatarUrl(),
                            'label' => $org->business_name,
                            'initials' => $org->avatarInitials(),
                        ])
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.organizers.show', $org) }}" class="text-sm font-semibold truncate block hover:text-brand">{{ $org->business_name }}</a>
                        <div class="text-xs text-mute">{{ $org->user?->email }} · {{ $org->created_at?->format('M j') }}</div>
                    </div>
                    <form method="POST" action="{{ route('admin.organizers.approve', $org) }}">@csrf
                        <button class="p-1.5 bg-brand text-white rounded-lg text-xs font-bold px-2">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.organizers.reject', $org) }}">@csrf
                        <button class="p-1.5 bg-red-50 text-red-500 rounded-lg text-xs font-bold px-2">Reject</button>
                    </form>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-mute text-sm">No pending organizers.</div>
            @endforelse
        </div>
        @include('admin.partials.pager', ['paginator' => $pendingOrgs, 'simple' => true])
    </div>

    <div id="pending-events" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-bold">Pending event reviews</h3>
            <span class="text-[11px] font-bold bg-violet-50 text-violet-700 px-2 py-0.5 rounded-full border border-violet-100">{{ number_format($pendingEvents->total()) }} pending</span>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse($pendingEvents as $event)
                @php
                    $sold = (int) ($event->tickets_sold ?? 0);
                    $capacity = (int) ($event->tickets_capacity ?? 0);
                    $left = max(0, $capacity - $sold);
                @endphp
                <div class="px-5 py-3.5 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate">{{ $event->title }}</div>
                        <div class="text-xs text-mute">{{ $event->organizer?->business_name }} · {{ $event->event_date?->format('M j') }} · {{ number_format($sold) }} sold · {{ number_format($left) }} left</div>
                    </div>
                    @if($event->is_private)
                        <a href="{{ route('admin.events.index', ['type' => 'private', 'q' => $event->title]) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand text-white text-xs font-bold">View</a>
                    @else
                        <a href="{{ route('events.show', $event->slug) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand text-white text-xs font-bold">View</a>
                    @endif
                    <form method="POST" action="{{ route('admin.events.approve', $event) }}">@csrf
                        <button class="p-1.5 bg-brand text-white rounded-lg text-xs font-bold px-2">Publish</button>
                    </form>
                    <form method="POST" action="{{ route('admin.events.reject', $event) }}">@csrf
                        <button class="p-1.5 bg-red-50 text-red-500 rounded-lg text-xs font-bold px-2">Reject</button>
                    </form>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-mute text-sm">No events awaiting review.</div>
            @endforelse
        </div>
        @include('admin.partials.pager', ['paginator' => $pendingEvents, 'simple' => true])
    </div>
</div>

<div id="recent-orders" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-50 flex justify-between">
        <h3 class="text-sm font-bold">Recent paid orders</h3>
        <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-brand">View all →</a>
    </div>
    <table class="w-full text-sm">
        <thead class="text-[11px] uppercase text-mute bg-slate-50/80"><tr>
            <th class="text-left px-4 py-3">Order</th><th class="text-left px-4 py-3">Buyer</th><th class="text-left px-4 py-3">Event</th><th class="text-left px-4 py-3">Amount</th><th class="text-left px-4 py-3">Commission</th>
        </tr></thead>
        <tbody>
            @forelse($recentOrders as $order)
                <tr class="border-t border-slate-50">
                    <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('admin.orders.show', $order->order_number) }}" class="font-bold text-brand hover:underline">{{ $order->order_number }}</a></td>
                    <td class="px-4 py-3 font-semibold">{{ $order->buyer_name }}</td>
                    <td class="px-4 py-3 text-mute truncate max-w-[160px]">{{ $order->event?->title }}</td>
                    <td class="px-4 py-3 font-bold">${{ number_format((float)$order->total_amount,0) }}</td>
                    <td class="px-4 py-3 text-brand font-bold">${{ number_format((float)$order->commission_amount,0) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-mute">No paid orders yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.pager', ['paginator' => $recentOrders, 'simple' => true])
</div>
@endsection
