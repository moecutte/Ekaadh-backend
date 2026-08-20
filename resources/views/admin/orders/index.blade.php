@extends('layouts.admin')
@section('title', 'Orders')
@section('heading', 'Orders')

@section('content')
@include('admin.partials.orders-payments-tabs')

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-ink text-white rounded-2xl p-4">
        <div class="text-[11px] font-bold uppercase tracking-wider text-white/60">Today’s paid</div>
        <div class="text-2xl font-black mt-1">${{ number_format($ops['today_paid_volume'], 0) }}</div>
        <div class="text-xs text-white/70 mt-0.5">{{ number_format($ops['today_paid_count']) }} successful orders</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-[11px] font-bold uppercase tracking-wider text-mute">Today</div>
        <div class="text-2xl font-black mt-1">{{ number_format($ops['today_total']) }}</div>
        <div class="text-xs text-mute mt-0.5">{{ number_format($ops['today_pending']) }} pending · {{ number_format($ops['today_failed']) }} failed</div>
    </div>
    <a href="{{ route('admin.orders.index', ['scope' => 'attention']) }}" class="bg-white rounded-2xl border {{ $scope === 'attention' ? 'border-amber-300' : 'border-amber-100' }} p-4 shadow-sm hover:border-amber-300 transition-colors">
        <div class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Needs attention</div>
        <div class="text-2xl font-black mt-1 text-amber-700">{{ number_format($ops['attention']) }}</div>
        <div class="text-xs text-mute mt-0.5">Pending or failed in the last 48 hours</div>
    </a>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-[11px] font-bold uppercase tracking-wider text-mute">This view</div>
        <div class="text-2xl font-black mt-1">{{ number_format($totals['count']) }}</div>
        <div class="text-xs text-mute mt-0.5">${{ number_format($totals['paid'], 0) }} paid · {{ number_format($totals['pending']) }} pending</div>
    </div>
</div>

@include('admin.partials.ops-scopes', ['scopes' => $scopes])

<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    @if($scope)
        <input type="hidden" name="scope" value="{{ $scope }}">
    @endif
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Find an order</label>
            <input name="q" value="{{ request('q') }}" placeholder="Paste order #, phone, or buyer name…" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
            <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All statuses</option>
                @foreach(['pending'=>'Pending (awaiting pay)','paid'=>'Paid','failed'=>'Failed','cancelled'=>'Cancelled','refunded'=>'Refunded'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Customer</label>
            <select name="customer_type" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All customers</option>
                <option value="user" @selected(request('customer_type')==='user')>Registered user</option>
                <option value="guest" @selected(request('customer_type')==='guest')>Guest checkout</option>
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Organizer</label>
            <select name="organizer_id" onchange="this.form.event_id.value=''; this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
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
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-slate-50">
        <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Search</button>
        @if($filtersActive)
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear</a>
        @endif
        <span class="text-xs text-mute ml-auto">Click a row to open the order for support.</span>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Order</th>
                    <th class="text-left px-4 py-3">Buyer</th>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Tickets</th>
                    <th class="text-left px-4 py-3">Amount</th>
                    <th class="text-left px-4 py-3">Payment</th>
                    <th class="text-left px-4 py-3">When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $sc = ['paid'=>'bg-emerald-50 text-emerald-700','pending'=>'bg-amber-50 text-amber-700','failed'=>'bg-red-50 text-red-600','cancelled'=>'bg-slate-50 text-mute','refunded'=>'bg-violet-50 text-violet-700'];
                        $pay = $order->payment;
                        $mismatch = $pay && (($pay->status === 'success' && $order->status !== 'paid') || ($pay->status === 'failed' && $order->status === 'paid'));
                    @endphp
                    <tr class="border-t border-slate-50 hover:bg-slate-50/70 cursor-pointer {{ $mismatch ? 'bg-red-50/40' : '' }}" onclick="window.location='{{ route('admin.orders.show', $order->order_number) }}'">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs font-bold text-ink">{{ $order->order_number }}</div>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $sc[$order->status] ?? 'bg-slate-50 text-mute' }}">{{ $order->status }}</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-mute">{{ $order->channelLabel() }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $order->buyer_name }}</div>
                            <div class="text-xs text-mute">{{ $order->buyer_phone }}</div>
                        </td>
                        <td class="px-4 py-3 text-mute">
                            <div class="max-w-[180px] truncate font-medium text-ink">{{ $order->event?->title }}</div>
                            <div class="text-xs">{{ $order->event?->organizer?->business_name }}</div>
                        </td>
                        <td class="px-4 py-3 font-bold">{{ (int) ($order->tickets_qty ?? 0) }}</td>
                        <td class="px-4 py-3">
                            <div class="font-bold">${{ number_format((float) $order->total_amount, 0) }}</div>
                            <div class="text-[11px] text-brand">fee ${{ number_format((float) $order->commission_amount, 0) }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($pay)
                                <div class="text-xs font-bold uppercase text-mute">{{ $pay->provider }}</div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $pay->status === 'success' ? 'bg-emerald-50 text-emerald-700' : ($pay->status === 'failed' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700') }}">{{ $pay->status }}</span>
                                @if($mismatch)
                                    <div class="text-[10px] font-bold text-red-600 mt-0.5">Mismatch</div>
                                @endif
                            @else
                                <span class="text-xs text-mute">No attempt</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-mute text-xs whitespace-nowrap">
                            <div>{{ $order->created_at?->format('M j, g:i A') }}</div>
                            <div class="text-slate-400">{{ $order->created_at?->diffForHumans() }}</div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-mute">No orders in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $orders])
</div>
@endsection
