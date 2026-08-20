@extends('layouts.admin')
@section('title', 'Payments')
@section('heading', 'Payments')

@section('content')
@include('admin.partials.orders-payments-tabs')

@if($ops['mismatch'] > 0)
    <a href="{{ route('admin.payments.index', ['scope' => 'mismatch']) }}" class="block mb-4 rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700 font-semibold hover:bg-red-100">
        {{ number_format($ops['mismatch']) }} payment{{ $ops['mismatch'] === 1 ? '' : 's' }} do not match the order status. Review mismatches now.
    </a>
@endif

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-ink text-white rounded-2xl p-4">
        <div class="text-[11px] font-bold uppercase tracking-wider text-white/60">Today’s collected</div>
        <div class="text-2xl font-black mt-1">${{ number_format($ops['today_success_volume'], 0) }}</div>
        <div class="text-xs text-white/70 mt-0.5">{{ number_format($ops['today_success_count']) }} successful charges</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-[11px] font-bold uppercase tracking-wider text-mute">Today</div>
        <div class="text-2xl font-black mt-1">{{ number_format($ops['today_total']) }}</div>
        <div class="text-xs text-mute mt-0.5">{{ number_format($ops['today_initiated']) }} waiting · {{ number_format($ops['today_failed']) }} failed</div>
    </div>
    <a href="{{ route('admin.payments.index', ['scope' => 'failed']) }}" class="bg-white rounded-2xl border {{ $scope === 'failed' ? 'border-red-300' : 'border-red-100' }} p-4 shadow-sm hover:border-red-300 transition-colors">
        <div class="text-[11px] font-bold uppercase tracking-wider text-red-600">Failed (48h)</div>
        <div class="text-2xl font-black mt-1 text-red-600">{{ number_format($ops['failed48']) }}</div>
        <div class="text-xs text-mute mt-0.5">Customers who could not complete payment</div>
    </a>
    <a href="{{ route('admin.payments.index', ['scope' => 'stuck']) }}" class="bg-white rounded-2xl border {{ $scope === 'stuck' ? 'border-amber-300' : 'border-amber-100' }} p-4 shadow-sm hover:border-amber-300 transition-colors">
        <div class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Stuck</div>
        <div class="text-2xl font-black mt-1 text-amber-700">{{ number_format($ops['stuck']) }}</div>
        <div class="text-xs text-mute mt-0.5">Initiated over 15 minutes with no result</div>
    </a>
</div>

@include('admin.partials.ops-scopes', ['scopes' => $scopes])

<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    @if($scope)
        <input type="hidden" name="scope" value="{{ $scope }}">
    @endif
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Find a payment</label>
            <input name="q" value="{{ request('q') }}" placeholder="Transaction ID, order #, wallet phone…" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
            <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All statuses</option>
                @foreach(['initiated'=>'Waiting on phone','success'=>'Successful','failed'=>'Failed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Provider</label>
            <select name="provider" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All providers</option>
                @foreach(['zaad'=>'Zaad','edahab'=>'eDahab','mock'=>'Mock','waafipay'=>'WaafiPay'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('provider')===$value)>{{ $label }}</option>
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
            <a href="{{ route('admin.payments.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear</a>
        @endif
        <span class="text-xs text-mute ml-auto">Open a payment to see the gateway reason.</span>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Charge</th>
                    <th class="text-left px-4 py-3">Order</th>
                    <th class="text-left px-4 py-3">Wallet</th>
                    <th class="text-left px-4 py-3">Amount</th>
                    <th class="text-left px-4 py-3">Result</th>
                    <th class="text-left px-4 py-3">When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $sc = ['success'=>'bg-emerald-50 text-emerald-700','initiated'=>'bg-amber-50 text-amber-700','failed'=>'bg-red-50 text-red-600'];
                        $mismatch = $payment->mismatchesOrder();
                        $reason = $payment->status === 'failed' ? $payment->failureMessage() : null;
                    @endphp
                    <tr class="border-t border-slate-50 hover:bg-slate-50/70 cursor-pointer {{ $mismatch ? 'bg-red-50/40' : '' }}" onclick="window.location='{{ route('admin.payments.show', $payment) }}'">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs {{ $payment->transaction_id ? 'text-ink font-bold' : 'text-mute' }}">{{ $payment->transaction_id ?: 'No transaction ID' }}</div>
                            <div class="text-[10px] font-bold uppercase mt-1 text-mute">{{ $payment->provider }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs font-bold">{{ $payment->order?->order_number ?: '—' }}</div>
                            <div class="text-xs text-mute max-w-[180px] truncate">{{ $payment->order?->buyer_name }} · {{ $payment->order?->event?->title }}</div>
                            @if($payment->order)
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $payment->order->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($payment->order->status === 'failed' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700') }}">order {{ $payment->order->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-mute whitespace-nowrap">{{ $payment->phone_number ?: '—' }}</td>
                        <td class="px-4 py-3 font-bold">${{ number_format((float) $payment->amount, 0) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $sc[$payment->status] ?? 'bg-slate-50 text-mute' }}">{{ $payment->status }}</span>
                            @if($payment->isStuck())
                                <div class="text-[10px] font-bold text-amber-700 mt-1">Stuck</div>
                            @endif
                            @if($mismatch)
                                <div class="text-[10px] font-bold text-red-600 mt-1">Does not match order</div>
                            @endif
                            @if($reason)
                                <div class="text-[11px] text-mute mt-1 max-w-[220px] leading-snug">{{ $reason }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-mute text-xs whitespace-nowrap">
                            <div>{{ $payment->created_at?->format('M j, g:i A') }}</div>
                            <div class="text-slate-400">{{ $payment->created_at?->diffForHumans() }}</div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-mute">No payments in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $payments])
</div>
@endsection
