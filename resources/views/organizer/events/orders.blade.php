@extends('layouts.organizer')
@section('title', $event->title.' — Orders')
@section('heading', 'Event orders')
@section('actions')
    <a href="{{ route('organizer.events.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-slate-200 text-sm font-bold rounded-xl text-mute hover:text-ink">← Back to events</a>
@endsection

@section('content')
<div class="mb-5">
    <h2 class="text-lg font-extrabold text-ink truncate">{{ $event->title }}</h2>
    <p class="text-xs text-mute mt-0.5">
        {{ $event->event_date?->format('M j, Y') }}
        @if($event->city) · {{ $event->city }}@endif
        · {{ $event->isFreeEvent() ? 'Free' : 'Priced' }}
    </p>
</div>

<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
        <div class="text-2xl font-black text-ink">{{ number_format($totalCards) }}</div>
        <div class="text-xs text-mute mt-1">Total cards</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
        <div class="text-2xl font-black text-brand">{{ number_format($bookedCards) }}</div>
        <div class="text-xs text-mute mt-1">Cards booked so far</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
        <div class="text-2xl font-black text-ink">{{ number_format($remainingCards) }}</div>
        <div class="text-xs text-mute mt-1">Remaining</div>
    </div>
</div>

<form method="GET" action="{{ route('organizer.events.orders', $event) }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="sm:col-span-2 xl:col-span-2">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Search</label>
            <input name="q" value="{{ request('q') }}" placeholder="Order #, name, phone…" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Type</label>
            <select name="type" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All types</option>
                <option value="sale" @selected(request('type') === 'sale')>Ticket sales</option>
                <option value="invitation" @selected(request('type') === 'invitation')>Private invitations</option>
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
            <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All statuses</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                <option value="private_sent" @selected(request('status') === 'private_sent')>Private sent</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled / revoked</option>
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Ticket type</label>
            <select name="ticket_type_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All ticket types</option>
                @foreach($event->ticketTypes as $type)
                    <option value="{{ $type->id }}" @selected((int) request('ticket_type_id') === $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Date from</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Date to</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-slate-50">
        <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Apply filters</button>
        @if($filtersActive)
            <a href="{{ route('organizer.events.orders', $event) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear all</a>
            <span class="text-xs text-mute ml-1">{{ $orders->total() }} result{{ $orders->total() === 1 ? '' : 's' }}</span>
        @endif
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between gap-3">
        <h3 class="text-sm font-bold">Orders & invitations</h3>
        <span class="text-xs text-mute">{{ $orders->total() }} total</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase tracking-wider text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">Order</th>
                    <th class="text-left px-4 py-3 font-bold">Buyer / guest</th>
                    <th class="text-left px-4 py-3 font-bold">Tickets</th>
                    <th class="text-left px-4 py-3 font-bold">Type</th>
                    <th class="text-left px-4 py-3 font-bold">Amount</th>
                    <th class="text-left px-4 py-3 font-bold">Status</th>
                    <th class="text-left px-4 py-3 font-bold">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $ticketQty = (int) $order->items->sum('quantity');
                        $ticketLabel = $order->items
                            ->map(fn ($item) => ($item->ticketType?->name ?: 'Ticket').' ×'.$item->quantity)
                            ->implode(', ');
                        $statusLabel = $order->organizerListStatusLabel();
                        $isInvite = $order->isInvitation();
                    @endphp
                    <tr class="border-t border-slate-50 hover:bg-slate-50/50">
                        <td class="px-4 py-3 font-mono text-xs text-mute">{{ $order->order_number }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $order->buyer_name }}</div>
                            <div class="text-xs text-mute">{{ $order->buyer_phone }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold">{{ $ticketQty }}</div>
                            <div class="text-xs text-mute truncate max-w-[180px]" title="{{ $ticketLabel }}">{{ $ticketLabel ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[11px] font-bold {{ $isInvite ? 'text-violet-700' : 'text-mute' }}">{{ $order->channelLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 font-bold">
                            @if($isInvite || $event->isFreeEvent())
                                —
                            @else
                                ${{ number_format((float) $order->total_amount, 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border
                                @if($isInvite && $order->status === 'paid') bg-violet-50 text-violet-700 border-violet-100
                                @elseif($order->status === 'paid') bg-emerald-50 text-emerald-700 border-emerald-100
                                @elseif($order->status === 'pending') bg-amber-50 text-amber-700 border-amber-100
                                @elseif($order->status === 'failed') bg-red-50 text-red-600 border-red-100
                                @else bg-slate-50 text-slate-600 border-slate-200
                                @endif">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-mute whitespace-nowrap">{{ $order->created_at?->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-mute">
                            @if($filtersActive)
                                No orders match your filters. <a href="{{ route('organizer.events.orders', $event) }}" class="text-brand font-bold">Clear filters</a>
                            @else
                                No orders or invitations for this event yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
