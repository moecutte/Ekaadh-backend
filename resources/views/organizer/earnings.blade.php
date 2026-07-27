@extends('layouts.organizer')
@section('title', 'Earnings')
@section('heading', 'Earnings')

@section('content')
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Gross Sales', '$'.number_format($gross, 0)],
        ['Commission ('.$rate.'%)', '$'.number_format($commission, 0)],
        ['Net Earnings', '$'.number_format($net, 0)],
        ['Available for Payout', '$'.number_format($available, 0)],
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
                        <tr><td colspan="3" class="px-4 py-8 text-center text-mute">No sales yet.</td></tr>
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
                        <tr><td colspan="3" class="px-4 py-8 text-center text-mute">No payouts recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
