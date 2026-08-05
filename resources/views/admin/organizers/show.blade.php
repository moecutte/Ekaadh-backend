@extends('layouts.admin')
@section('title', $organizer->business_name)
@section('heading', $organizer->business_name)

@section('actions')
    <a href="{{ route('admin.organizers.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-mute hover:text-ink">← Back to organizers</a>
@endsection

@section('content')
@php
    $statusColors = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'rejected' => 'bg-red-50 text-red-600 border-red-100',
    ];
    $effectiveRate = $organizer->effectiveCommissionRate($defaultRate);
@endphp

<div class="grid sm:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Events</div>
        <div class="text-xl font-black mt-1">{{ number_format($stats['events']) }}</div>
        <div class="text-[11px] text-mute mt-0.5">{{ number_format($stats['published']) }} published</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Paid orders</div>
        <div class="text-xl font-black mt-1">{{ number_format($stats['orders']) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Gross sales</div>
        <div class="text-xl font-black mt-1">${{ number_format($stats['gross'], 0) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <div class="text-xs text-mute">Commission earned</div>
        <div class="text-xl font-black mt-1 text-brand">${{ number_format($stats['commission'], 0) }}</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold">Business profile</h3>
                    <p class="text-xs text-mute mt-0.5">Joined {{ $organizer->created_at?->format('M j, Y g:i A') }}</p>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $statusColors[$organizer->approval_status] ?? 'bg-slate-50 text-mute' }}">{{ $organizer->approval_status }}</span>
            </div>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Business name</dt>
                    <dd class="font-semibold">{{ $organizer->business_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Business phone</dt>
                    <dd>{{ $organizer->business_phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">City</dt>
                    <dd>{{ $organizer->city ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Pricing package</dt>
                    <dd class="font-semibold">{{ $organizer->package?->name ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-mute mb-1">About the business</dt>
                    <dd class="text-sm leading-relaxed whitespace-pre-line">{{ $organizer->business_description ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Commission rate</dt>
                    <dd>
                        <span class="font-semibold">{{ number_format((float) $effectiveRate, 1) }}%</span>
                        @if($organizer->commission_rate !== null)
                            <span class="text-xs text-mute">(custom override)</span>
                        @elseif($organizer->package?->commission_rate !== null)
                            <span class="text-xs text-mute">(from {{ $organizer->package->name }} package)</span>
                        @else
                            <span class="text-xs text-mute">(platform default)</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">ID type</dt>
                    <dd>{{ $organizer->idTypeLabel() ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">ID number</dt>
                    <dd class="font-mono text-xs">{{ $organizer->id_number ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-mute mb-1">Identity documents</dt>
                    <dd class="flex flex-wrap gap-2 mt-1">
                        @if($organizer->documentUrl('id_front'))
                            <a href="{{ $organizer->documentUrl('id_front') }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand/10 text-brand text-xs font-bold hover:bg-brand/20">ID front</a>
                        @endif
                        @if($organizer->documentUrl('id_back'))
                            <a href="{{ $organizer->documentUrl('id_back') }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand/10 text-brand text-xs font-bold hover:bg-brand/20">ID back</a>
                        @endif
                        @if($organizer->documentUrl('business_license'))
                            <a href="{{ $organizer->documentUrl('business_license') }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-ink text-xs font-bold hover:bg-slate-200">Business license</a>
                        @endif
                        @unless($organizer->hasIdentityDocuments())
                            <span class="text-mute">No documents uploaded</span>
                        @endunless
                    </dd>
                </div>
                @if($organizer->approval_status === 'approved')
                    <div>
                        <dt class="text-xs font-bold text-mute mb-1">Approved at</dt>
                        <dd>{{ $organizer->approved_at?->format('M j, Y g:i A') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-mute mb-1">Approved by</dt>
                        <dd>{{ $organizer->approver?->name ?: '—' }}</dd>
                    </div>
                @endif
                @if($organizer->rejection_reason)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-bold text-mute mb-1">Rejection reason</dt>
                        <dd class="text-red-600">{{ $organizer->rejection_reason }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h3 class="text-sm font-bold mb-4">Account contact</h3>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Name</dt>
                    <dd class="font-semibold">{{ $organizer->user?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Account status</dt>
                    <dd>
                        @php $userStatus = $organizer->user?->status; @endphp
                        @if($userStatus)
                            @php $uc = ['active'=>'bg-emerald-50 text-emerald-700 border-emerald-100','inactive'=>'bg-slate-50 text-mute border-slate-100','suspended'=>'bg-red-50 text-red-600 border-red-100']; @endphp
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $uc[$userStatus] ?? 'bg-slate-50 text-mute' }}">{{ $userStatus }}</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Email</dt>
                    <dd>{{ $organizer->user?->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Phone</dt>
                    <dd>{{ $organizer->user?->phone ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-50">
                <h3 class="text-sm font-bold">Events</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                    <tr>
                        <th class="text-left px-4 py-3">Title</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizer->events as $event)
                        <tr class="border-t border-slate-50">
                            <td class="px-4 py-3 font-semibold">{{ $event->title }}</td>
                            <td class="px-4 py-3 text-mute text-xs">{{ $event->event_date?->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-50 text-mute border border-slate-100">{{ $event->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-mute">No events yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h3 class="text-sm font-bold mb-1">Actions</h3>
            <p class="text-xs text-mute mb-4">Approve, assign a package, or set a commission override.</p>

            <div class="space-y-3">
                @if($organizer->approval_status !== 'approved')
                    <form method="POST" action="{{ route('admin.organizers.approve', $organizer) }}" class="space-y-2">
                        @csrf
                        <label class="text-xs font-bold text-mute block">Assign package on approve</label>
                        <select name="package_id" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                            @foreach($packages->where('is_active', true) as $package)
                                <option value="{{ $package->id }}" @selected((string) ($organizer->package_id ?? $packages->firstWhere('is_default')?->id) === (string) $package->id)>{{ $package->name }}</option>
                            @endforeach
                        </select>
                        <button class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Approve organizer</button>
                    </form>
                @endif

                @if($organizer->approval_status !== 'rejected')
                    <form method="POST" action="{{ route('admin.organizers.reject', $organizer) }}" class="space-y-2">
                        @csrf
                        <input name="rejection_reason" placeholder="Rejection reason (optional)" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        <button class="w-full py-2.5 rounded-xl bg-red-50 text-red-600 text-sm font-bold hover:bg-red-100">Reject organizer</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.organizers.package', $organizer) }}" class="pt-2 border-t border-slate-50 space-y-2">
                    @csrf
                    <label class="text-xs font-bold text-mute block">Pricing package</label>
                    <div class="flex items-center gap-2">
                        <select name="package_id" class="flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                            <option value="">No package</option>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}" @selected((string) $organizer->package_id === (string) $package->id)>{{ $package->name }}</option>
                            @endforeach
                        </select>
                        <button class="px-3 py-2.5 rounded-xl bg-slate-100 text-sm font-bold hover:bg-slate-200">Save</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.organizers.commission', $organizer) }}" class="pt-2 border-t border-slate-50 space-y-2">
                    @csrf
                    <label class="text-xs font-bold text-mute block">Commission override (%)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.1" min="0" max="100" name="commission_rate" value="{{ $organizer->commission_rate }}" placeholder="{{ number_format($effectiveRate, 1) }}" class="flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        <button class="px-3 py-2.5 rounded-xl bg-slate-100 text-sm font-bold hover:bg-slate-200">Save</button>
                    </div>
                    <p class="text-[11px] text-mute">Leave blank to use package rate, then platform default ({{ number_format($defaultRate, 1) }}%). Effective rate: {{ number_format($effectiveRate, 1) }}%.</p>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-50">
                <h3 class="text-sm font-bold">Recent payouts</h3>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($organizer->payouts as $payout)
                    <div class="px-5 py-3 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold">${{ number_format((float) $payout->net_payout, 0) }}</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-50 text-mute border border-slate-100">{{ $payout->status }}</span>
                        </div>
                        <div class="text-xs text-mute mt-1">
                            {{ $payout->period_start?->format('M j') }} – {{ $payout->period_end?->format('M j, Y') }}
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-mute text-sm">No payouts recorded.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
