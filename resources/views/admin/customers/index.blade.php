@extends('layouts.admin')
@section('title', 'Customers')
@section('heading', 'Customers')

@section('content')
<div class="grid sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Registered users</div>
        <div class="text-xl font-black mt-1">{{ number_format($totals['users']) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Guest customers</div>
        <div class="text-xl font-black mt-1">{{ number_format($totals['guests']) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Active accounts</div>
        <div class="text-xl font-black mt-1 text-brand">{{ number_format($totals['active']) }}</div>
    </div>
</div>

<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input name="q" value="{{ request('q') }}" placeholder="Search name, email, phone…" class="flex-1 min-w-[200px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
    <select name="type" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        <option value="">All types</option>
        <option value="user" @selected(request('type')==='user')>User</option>
        <option value="guest" @selected(request('type')==='guest')>Guest</option>
    </select>
    <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
            <tr>
                <th class="text-left px-4 py-3">Customer</th>
                <th class="text-left px-4 py-3">Contact</th>
                <th class="text-left px-4 py-3">Type</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Orders</th>
                <th class="text-left px-4 py-3">Since</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr class="border-t border-slate-50 align-top">
                    <td class="px-4 py-3">
                        <div class="font-bold">{{ $customer->name ?: '—' }}</div>
                    </td>
                    <td class="px-4 py-3 text-mute">
                        <div class="text-xs">{{ $customer->phone ?: '—' }}</div>
                        @if($customer->email)
                            <div class="text-xs">{{ $customer->email }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($customer->type === 'user')
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-brand/10 text-brand border-brand/20">User</span>
                        @else
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-amber-50 text-amber-700 border-amber-100">Guest</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($customer->type === 'user')
                            @php $colors = ['active'=>'bg-emerald-50 text-emerald-700 border-emerald-100','inactive'=>'bg-slate-50 text-mute border-slate-100','suspended'=>'bg-red-50 text-red-600 border-red-100']; @endphp
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $colors[$customer->status] ?? 'bg-slate-50 text-mute border-slate-100' }}">{{ $customer->status }}</span>
                        @else
                            <span class="text-xs text-mute">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-semibold">{{ number_format($customer->orders_count) }}</td>
                    <td class="px-4 py-3 text-mute text-xs">{{ $customer->joined_label }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-mute">No customers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
