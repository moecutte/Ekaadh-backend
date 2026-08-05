@extends('layouts.organizer')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
@if($profile && $profile->approval_status === 'pending')
    <div class="mb-5 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 p-4 text-sm flex flex-wrap items-center justify-between gap-3">
        <div>
            <strong>Pending approval.</strong> You can explore the dashboard, but event management unlocks after an admin approves your account.
        </div>
        <a href="{{ route('organizer.application.edit') }}" class="shrink-0 px-3 py-2 rounded-xl bg-white border border-amber-200 text-amber-900 text-xs font-bold hover:bg-amber-100">Update application</a>
    </div>
@elseif($profile && $profile->approval_status === 'rejected')
    <div class="mb-5 rounded-xl bg-red-50 border border-red-100 text-red-700 p-4 text-sm flex flex-wrap items-center justify-between gap-3">
        <div>
            <strong>Application rejected.</strong> {{ $profile->rejection_reason ?: 'Contact support for details.' }}
        </div>
        <a href="{{ route('organizer.application.edit') }}" class="shrink-0 px-3 py-2 rounded-xl bg-white border border-red-200 text-red-800 text-xs font-bold hover:bg-red-100">Update &amp; resubmit</a>
    </div>
@endif

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Events', $stats['events'], 'bg-brand/10 text-brand', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['Tickets Sold', number_format($stats['tickets_sold']), 'bg-sky-50 text-sky-600', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
        ['Gross Revenue', '$'.number_format($stats['gross'], 0), 'bg-violet-50 text-violet-600', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
        ['Net Earnings', '$'.number_format($stats['net'], 0), 'bg-amber-50 text-amber-600', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
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

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
        <h3 class="text-sm font-bold">Recent paid orders</h3>
        <span class="text-xs text-mute">Commission default {{ $defaultCommission }}%</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase tracking-wider text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">Order</th>
                    <th class="text-left px-4 py-3 font-bold">Buyer</th>
                    <th class="text-left px-4 py-3 font-bold">Event</th>
                    <th class="text-left px-4 py-3 font-bold">Amount</th>
                    <th class="text-left px-4 py-3 font-bold">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                    <tr class="border-t border-slate-50">
                        <td class="px-4 py-3 font-mono text-xs text-mute">{{ $order->order_number }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $order->buyer_name }}</td>
                        <td class="px-4 py-3 text-mute truncate max-w-[160px]">{{ $order->event?->title }}</td>
                        <td class="px-4 py-3 font-bold">${{ number_format((float) $order->total_amount, 0) }}</td>
                        <td class="px-4 py-3 text-xs text-mute">{{ $order->created_at?->format('M j') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-mute">No paid orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
