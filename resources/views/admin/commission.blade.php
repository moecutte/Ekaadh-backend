@extends('layouts.admin')
@section('title', 'Commission')
@section('heading', 'Commission Settings')

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-sm font-bold mb-1">Platform defaults</h3>
        <p class="text-xs text-mute mb-5">Fallback when an organizer has no package rate and no custom override.</p>
        <form method="POST" action="{{ route('admin.commission.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Default commission rate (%)</label>
                <input type="number" step="0.1" min="0" max="100" name="default_commission_rate" value="{{ old('default_commission_rate', $defaultRate) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Service fee ($)</label>
                <input type="number" step="0.01" min="0" max="100" name="service_fee" value="{{ old('service_fee', $serviceFee) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
            <button class="px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Save settings</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50">
            <h3 class="text-sm font-bold">Organizer overrides</h3>
            <p class="text-xs text-mute mt-0.5">Resolution: override → package rate → {{ $defaultRate }}% default.</p>
        </div>
        <div class="divide-y divide-slate-50 max-h-[420px] overflow-y-auto">
            @forelse($organizers as $org)
                @php $effective = $org->effectiveCommissionRate($defaultRate); @endphp
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate">{{ $org->business_name }}</div>
                        <div class="text-xs text-mute">
                            {{ $org->user?->email }}
                            · {{ $org->package?->name ?? 'No package' }}
                            · effective {{ number_format($effective, 1) }}%
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.organizers.commission', $org) }}" class="flex items-center gap-1.5">
                        @csrf
                        <input type="number" step="0.1" min="0" max="100" name="commission_rate" value="{{ $org->commission_rate }}" placeholder="{{ number_format($effective, 1) }}" class="w-16 rounded-lg border border-slate-200 px-2 py-1.5 text-xs text-center">
                        <span class="text-xs text-mute">%</span>
                        <button class="text-xs font-bold text-brand">Save</button>
                    </form>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-mute text-sm">No approved organizers yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
