@extends('layouts.organizer')
@section('title', 'Earnings')
@section('heading', 'Earnings & Payouts')

@section('content')
<form method="GET" action="{{ route('organizer.earnings') }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1 min-w-0">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Event</label>
            <select name="event_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm" onchange="this.form.submit()">
                <option value="">All events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected((string) $selectedEventId === (string) $event->id)>
                        {{ $event->title }}
                        @if($event->event_date) — {{ $event->event_date->format('M j, Y') }}@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Apply</button>
            @if($selectedEventId)
                <a href="{{ route('organizer.earnings') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear</a>
            @endif
        </div>
    </div>
    @if($selectedEvent)
        <p class="text-xs text-mute mt-3 pt-3 border-t border-slate-50">
            Showing earnings and related payouts for <span class="font-bold text-ink">{{ $selectedEvent->title }}</span>.
        </p>
    @endif
</form>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
        <div class="text-2xl font-black text-brand">{{ number_format($totalBoughtCards) }}</div>
        <div class="text-xs text-mute mt-1">Total bought tickets</div>
    </div>
    @forelse($ticketTypesBought as $type)
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <div class="text-2xl font-black text-ink">{{ number_format($type['bought']) }}</div>
            <div class="text-xs text-mute mt-1 truncate" title="{{ $type['name'] }}">{{ $type['name'] }}</div>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm sm:col-span-1 xl:col-span-2">
            <div class="text-2xl font-black text-ink">0</div>
            <div class="text-xs text-mute mt-1">No ticket types sold yet</div>
        </div>
    @endforelse
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Gross Sales', '$'.number_format($gross, 0)],
        ['Commission ('.number_format((float) $rate, 1).'%)', '$'.number_format($commission, 0)],
        ['Net Earnings', '$'.number_format($net, 0)],
        [$selectedEventId ? 'Available (this event)' : 'Available for Payout', '$'.number_format($available, 0)],
    ] as [$label, $value])
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <div class="text-2xl font-black">{{ $value }}</div>
            <div class="text-xs text-mute mt-1">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 font-bold text-sm">Recent paid sales</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-[11px] text-mute uppercase bg-slate-50/80"><tr>
                    <th class="text-left px-4 py-3">Order</th><th class="text-left px-4 py-3">Event</th><th class="text-left px-4 py-3">Net</th>
                </tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="border-t border-slate-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 truncate max-w-[140px]">{{ $order->event?->title }}</td>
                            <td class="px-4 py-3 font-bold">${{ number_format((float)$order->subtotal - (float)$order->commission_amount, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-mute">No sales yet{{ $selectedEventId ? ' for this event' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 font-bold text-sm">Payout history</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-[11px] text-mute uppercase bg-slate-50/80"><tr>
                    <th class="text-left px-4 py-3">Period</th><th class="text-left px-4 py-3">Net</th><th class="text-left px-4 py-3">Status</th>
                </tr></thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr class="border-t border-slate-50">
                            <td class="px-4 py-3 text-xs">{{ $payout->period_start?->format('M j') }} – {{ $payout->period_end?->format('M j, Y') }}</td>
                            <td class="px-4 py-3 font-bold">${{ number_format((float)$payout->net_payout, 0) }}</td>
                            <td class="px-4 py-3"><span class="text-[11px] font-bold {{ $payout->status==='paid' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($payout->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-mute">No payouts recorded yet{{ $selectedEventId ? ' for this event’s sales period' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
