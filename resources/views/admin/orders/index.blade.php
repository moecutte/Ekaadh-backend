@extends('layouts.admin')
@section('title', 'Orders')
@section('heading', 'Orders & Payments')

@section('content')
<div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Matching orders</div>
        <div class="text-xl font-black mt-1">{{ number_format($totals['count']) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Paid volume</div>
        <div class="text-xl font-black mt-1">${{ number_format($totals['paid'], 0) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Commission earned</div>
        <div class="text-xl font-black mt-1 text-brand">${{ number_format($totals['commission'], 0) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Pending orders</div>
        <div class="text-xl font-black mt-1">{{ $totals['pending'] }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Failed orders</div>
        <div class="text-xl font-black mt-1 text-red-500">{{ $totals['failed'] }}</div>
    </div>
</div>

<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Search</label>
            <input name="q" value="{{ request('q') }}" placeholder="Order #, buyer, phone, email, event, organizer…" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
            <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All statuses</option>
                @foreach(['pending','paid','failed','cancelled','refunded'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Payment method</label>
            <select name="payment_method" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All methods</option>
                @foreach(['zaad','edahab','mock'] as $m)
                    <option value="{{ $m }}" @selected(request('payment_method')===$m)>{{ ucfirst($m) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Customer type</label>
            <select name="customer_type" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All customers</option>
                <option value="user" @selected(request('customer_type')==='user')>Registered user</option>
                <option value="guest" @selected(request('customer_type')==='guest')>Guest</option>
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Organizer</label>
            <select name="organizer_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All organizers</option>
                @foreach($filterOptions['organizers'] as $org)
                    <option value="{{ $org->id }}" @selected((string) request('organizer_id') === (string) $org->id)>{{ $org->business_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Event</label>
            <select name="event_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All events</option>
                @foreach($filterOptions['events'] as $event)
                    <option value="{{ $event->id }}" @selected((string) request('event_id') === (string) $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Order date from</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Order date to</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-slate-50">
        <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Apply filters</button>
        @if($filtersActive)
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear all</a>
            <span class="text-xs text-mute ml-1">Showing filtered results</span>
        @endif
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
            <tr>
                <th class="text-left px-4 py-3">Order</th>
                <th class="text-left px-4 py-3">Buyer</th>
                <th class="text-left px-4 py-3">Event</th>
                <th class="text-left px-4 py-3">Amount</th>
                <th class="text-left px-4 py-3">Commission</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Method</th>
                <th class="text-left px-4 py-3">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr class="border-t border-slate-50 align-top">
                    <td class="px-4 py-3">
                        <div class="font-mono text-xs text-mute">{{ $order->order_number }}</div>
                        @if($order->user_id)
                            <span class="text-[10px] font-bold text-brand bg-brand/10 px-1.5 py-0.5 rounded">User</span>
                        @else
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">Guest</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-semibold">{{ $order->buyer_name }}</div>
                        <div class="text-xs text-mute">{{ $order->buyer_phone }}</div>
                    </td>
                    <td class="px-4 py-3 text-mute">
                        <div class="max-w-[160px] truncate">{{ $order->event?->title }}</div>
                        <div class="text-xs">{{ $order->event?->organizer?->business_name }}</div>
                    </td>
                    <td class="px-4 py-3 font-bold">${{ number_format((float)$order->total_amount, 0) }}</td>
                    <td class="px-4 py-3 text-brand font-bold">${{ number_format((float)$order->commission_amount, 0) }}</td>
                    <td class="px-4 py-3">
                        @php $sc = ['paid'=>'bg-emerald-50 text-emerald-700','pending'=>'bg-amber-50 text-amber-700','failed'=>'bg-red-50 text-red-600','cancelled'=>'bg-slate-50 text-mute','refunded'=>'bg-violet-50 text-violet-700']; @endphp
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $sc[$order->status] ?? 'bg-slate-50 text-mute' }}">{{ $order->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-mute text-xs uppercase">{{ $order->payment_method ?: ($order->payment?->provider ?: '—') }}</td>
                    <td class="px-4 py-3 text-mute text-xs whitespace-nowrap">{{ $order->created_at?->format('M j, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-mute">No orders found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
