@extends('layouts.admin')
@section('title', 'Organizers')
@section('heading', 'Organizers')

@section('content')
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input name="q" value="{{ request('q') }}" placeholder="Search business, email, phone…" class="flex-1 min-w-[200px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
    <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        <option value="">All statuses</option>
        @foreach(['pending','approved','rejected'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <select name="package_id" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        <option value="">All packages</option>
        @foreach($packages as $package)
            <option value="{{ $package->id }}" @selected((string) request('package_id') === (string) $package->id)>{{ $package->name }}</option>
        @endforeach
    </select>
    <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
            <tr>
                <th class="text-left px-4 py-3">Business</th>
                <th class="text-left px-4 py-3">Contact</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Package</th>
                <th class="text-left px-4 py-3">Commission</th>
                <th class="text-left px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($organizers as $org)
                @php $effectiveRate = $org->effectiveCommissionRate($defaultRate); @endphp
                <tr class="border-t border-slate-50 align-top">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            @include('partials.avatar', [
                                'url' => $org->avatarUrl(),
                                'label' => $org->business_name,
                                'initials' => $org->avatarInitials(),
                            ])
                            <div class="min-w-0">
                                <div class="font-bold truncate">{{ $org->business_name }}</div>
                                <div class="text-xs text-mute">Joined {{ $org->created_at?->format('M j, Y') }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-mute">
                        <div>{{ $org->user?->name }}</div>
                        <div class="text-xs">{{ $org->user?->email }}</div>
                        <div class="text-xs">{{ $org->user?->phone }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @php $colors = ['pending'=>'bg-amber-50 text-amber-700 border-amber-100','approved'=>'bg-emerald-50 text-emerald-700 border-emerald-100','rejected'=>'bg-red-50 text-red-600 border-red-100']; @endphp
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $colors[$org->approval_status] ?? 'bg-slate-50 text-mute' }}">{{ $org->approval_status }}</span>
                        @if($org->hasIdentityDocuments())
                            <div class="text-[10px] font-bold text-emerald-600 mt-1">ID on file</div>
                        @else
                            <div class="text-[10px] font-bold text-amber-600 mt-1">No ID</div>
                        @endif
                        @if($org->rejection_reason)
                            <div class="text-xs text-red-500 mt-1 max-w-[180px]">{{ $org->rejection_reason }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.organizers.package', $org) }}" class="flex items-center gap-1">
                            @csrf
                            <select name="package_id" class="rounded-lg border border-slate-200 px-2 py-1 text-xs max-w-[120px]" onchange="this.form.submit()">
                                <option value="">No package</option>
                                @foreach($packages as $package)
                                    <option value="{{ $package->id }}" @selected((string) $org->package_id === (string) $package->id)>{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.organizers.commission', $org) }}" class="flex items-center gap-1">
                            @csrf
                            <input type="number" step="0.1" min="0" max="100" name="commission_rate" value="{{ $org->commission_rate }}" placeholder="{{ number_format($effectiveRate, 1) }}" class="w-16 rounded-lg border border-slate-200 px-2 py-1 text-xs">
                            <span class="text-xs text-mute">%</span>
                            <button class="text-[10px] font-bold text-brand">Save</button>
                        </form>
                        <div class="text-[10px] text-mute mt-0.5">
                            @if($org->commission_rate !== null)
                                override · effective {{ number_format($effectiveRate, 1) }}%
                            @elseif($org->package?->commission_rate !== null)
                                from {{ $org->package->name }} · {{ number_format($effectiveRate, 1) }}%
                            @else
                                default {{ number_format($defaultRate, 1) }}%
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1.5 items-center">
                            <a href="{{ route('admin.organizers.show', $org) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 text-ink text-xs font-bold hover:bg-slate-200">View</a>
                            @if($org->approval_status !== 'approved')
                                <form method="POST" action="{{ route('admin.organizers.approve', $org) }}" class="flex items-center gap-1">
                                    @csrf
                                    <select name="package_id" class="rounded-lg border border-slate-200 px-2 py-1 text-xs max-w-[100px]">
                                        @foreach($packages->where('is_active', true) as $package)
                                            <option value="{{ $package->id }}" @selected((string) ($org->package_id ?? $packages->firstWhere('is_default')?->id) === (string) $package->id)>{{ $package->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="px-2.5 py-1 rounded-lg bg-brand text-white text-xs font-bold">Approve</button>
                                </form>
                            @endif
                            @if($org->approval_status !== 'rejected')
                                <form method="POST" action="{{ route('admin.organizers.reject', $org) }}" class="flex gap-1">
                                    @csrf
                                    <input name="rejection_reason" placeholder="Reason" class="w-28 rounded-lg border border-slate-200 px-2 py-1 text-xs">
                                    <button class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-bold">Reject</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-mute">No organizers found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.pager', ['paginator' => $organizers])
</div>
@endsection
