@extends('layouts.admin')
@section('title', 'Payouts')
@section('heading', 'Payouts')

@section('content')
<div class="grid lg:grid-cols-3 gap-5 mb-6">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50">
            <h3 class="text-sm font-bold">Organizer balances</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Organizer</th>
                    <th class="text-left px-4 py-3">Events</th>
                    <th class="text-left px-4 py-3">Gross</th>
                    <th class="text-left px-4 py-3">Pending payout</th>
                    <th class="text-left px-4 py-3">Last paid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($organizers as $row)
                    <tr class="border-t border-slate-50">
                        <td class="px-4 py-3 font-semibold">{{ $row['profile']->business_name }}</td>
                        <td class="px-4 py-3 text-mute">{{ $row['events'] }}</td>
                        <td class="px-4 py-3">${{ number_format($row['gross'], 0) }}</td>
                        <td class="px-4 py-3 font-bold text-brand">${{ number_format($row['pending'], 0) }}</td>
                        <td class="px-4 py-3 text-mute text-xs">{{ $row['last_paid'] ? \Illuminate\Support\Carbon::parse($row['last_paid'])->format('M j, Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-mute">No approved organizers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h3 class="text-sm font-bold mb-1">Record payout</h3>
        <p class="text-xs text-mute mb-4">Creates a paid payout for the selected period.</p>
        <form method="POST" action="{{ route('admin.payouts.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Organizer</label>
                <select name="organizer_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="">Select…</option>
                    @foreach($organizers as $row)
                        <option value="{{ $row['profile']->id }}">{{ $row['profile']->business_name }} (${{ number_format($row['pending'], 0) }} pending)</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">From</label>
                    <input type="date" name="period_start" required value="{{ now()->startOfMonth()->toDateString() }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">To</label>
                    <input type="date" name="period_end" required value="{{ now()->toDateString() }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Notes</label>
                <input name="notes" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="Optional">
            </div>
            <button class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Record payout</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-50">
        <h3 class="text-sm font-bold">Payout history</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
            <tr>
                <th class="text-left px-4 py-3">Organizer</th>
                <th class="text-left px-4 py-3">Period</th>
                <th class="text-left px-4 py-3">Gross</th>
                <th class="text-left px-4 py-3">Commission</th>
                <th class="text-left px-4 py-3">Net paid</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payouts as $payout)
                <tr class="border-t border-slate-50">
                    <td class="px-4 py-3 font-semibold">{{ $payout->organizer?->business_name }}</td>
                    <td class="px-4 py-3 text-mute text-xs">{{ $payout->period_start?->format('M j') }} – {{ $payout->period_end?->format('M j, Y') }}</td>
                    <td class="px-4 py-3">${{ number_format((float)$payout->gross_sales, 0) }}</td>
                    <td class="px-4 py-3">${{ number_format((float)$payout->commission_deducted, 0) }}</td>
                    <td class="px-4 py-3 font-bold text-brand">${{ number_format((float)$payout->net_payout, 0) }}</td>
                    <td class="px-4 py-3">
                        @if($payout->status === 'paid')
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">paid</span>
                        @else
                            <form method="POST" action="{{ route('admin.payouts.paid', $payout) }}">@csrf
                                <button class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Mark paid</button>
                            </form>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-mute">{{ $payout->paidBy?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-mute">No payouts recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $payouts->links() }}</div>
@endsection
