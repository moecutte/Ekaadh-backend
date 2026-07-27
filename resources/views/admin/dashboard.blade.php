@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Organizers', $stats['organizers'], 'bg-brand/10 text-brand', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['Total Events', $stats['events'], 'bg-sky-50 text-sky-600', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['Tickets Sold', number_format($stats['tickets_sold']), 'bg-violet-50 text-violet-600', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
        ['Platform Revenue', '$'.number_format($stats['platform_revenue'], 0), 'bg-amber-50 text-amber-600', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ] as [$label, $value, $color, $icon])
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <div class="w-9 h-9 rounded-xl {{ $color }} mb-3 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </div>
            <div class="text-2xl font-extrabold tracking-tight">{{ $value }}</div>
            <div class="text-xs text-mute mt-1">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-bold">Pending organizer approvals</h3>
            <span class="text-[11px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-100">{{ $pendingOrgs->count() }} pending</span>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse($pendingOrgs as $org)
                <div class="px-5 py-3.5 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-brand/10 text-brand text-xs font-black flex items-center justify-center">{{ substr($org->business_name,0,1) }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate">{{ $org->business_name }}</div>
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
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-bold">Pending event reviews</h3>
            <span class="text-[11px] font-bold bg-violet-50 text-violet-700 px-2 py-0.5 rounded-full border border-violet-100">{{ $pendingEvents->count() }} pending</span>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse($pendingEvents as $event)
                <div class="px-5 py-3.5 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate">{{ $event->title }}</div>
                        <div class="text-xs text-mute">{{ $event->organizer?->business_name }} · {{ $event->event_date?->format('M j') }}</div>
                    </div>
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
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
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
                    <td class="px-4 py-3 font-mono text-xs text-mute">{{ $order->order_number }}</td>
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
</div>
@endsection
