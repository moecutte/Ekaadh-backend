@extends('layouts.admin')
@section('title', 'System settings')
@section('heading', 'System settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-4xl space-y-4">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
        <div>
            <h3 class="text-sm font-bold">Platform</h3>
            <p class="text-xs text-mute mt-0.5">Name shown across the product.</p>
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Platform name</label>
            <input name="platform_name" value="{{ old('platform_name', $platformName) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
        <div>
            <h3 class="text-sm font-bold">Public events — fees & commission</h3>
            <p class="text-xs text-mute mt-0.5">Defaults for priced and free public events. Organizer overrides can change the commission rate below.</p>
        </div>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Default commission rate (%)</label>
                <input type="number" step="0.1" min="0" max="100" name="default_commission_rate" value="{{ old('default_commission_rate', $defaultRate) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
                <p class="text-[11px] text-mute mt-1">Priced public events (per ticket subtotal).</p>
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Service fee ($)</label>
                <input type="number" step="0.01" min="0" max="100" name="service_fee" value="{{ old('service_fee', $serviceFee) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
                <p class="text-[11px] text-mute mt-1">Buyer fee on priced ticket checkouts.</p>
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Free ticket organizer fee ($)</label>
                <input type="number" step="0.01" min="0" max="100" name="free_ticket_organizer_fee" value="{{ old('free_ticket_organizer_fee', $freeTicketFee) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
                <p class="text-[11px] text-mute mt-1">Charged to the organizer after admin publish (capacity × this fee) before a free event goes live.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
        <div>
            <h3 class="text-sm font-bold">Private events</h3>
            <p class="text-xs text-mute mt-0.5">Capacity pricing customers pay when creating a private invitation. Standard tickets are the base price; premium designs add the surcharge (e.g. $0.80 + $0.20 = $1.00).</p>
        </div>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Standard ticket price ($)</label>
                <input type="number" step="0.01" min="0" name="private_ticket_price" value="{{ old('private_ticket_price', $privateTicketPrice) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Max tickets</label>
                <input type="number" min="1" name="private_ticket_max" value="{{ old('private_ticket_max', $privateTicketMax) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Premium design surcharge ($)</label>
                <input type="number" step="0.01" min="0" name="private_premium_design_surcharge" value="{{ old('private_premium_design_surcharge', $privatePremiumSurcharge) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
                <p class="text-[11px] text-mute mt-1">Premium ticket total = standard + surcharge.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="show_organizer_packages_on_front" value="1" class="mt-1 rounded border-slate-300 text-brand" @checked(old('show_organizer_packages_on_front', $showOrganizerPackagesOnFront))>
            <span>
                <span class="text-sm font-bold block">Show organizer packages on the public site</span>
                <span class="text-xs text-mute">Pricing cards on the Create Event landing page.</span>
            </span>
        </label>
    </div>

    <div class="flex justify-end">
        <button class="px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Save settings</button>
    </div>
</form>

<div class="max-w-4xl mt-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50">
            <h3 class="text-sm font-bold">Organizer commission overrides</h3>
            <p class="text-xs text-mute mt-0.5">Resolution: override → package rate → {{ number_format((float) $defaultRate, 1) }}% default. Saved per organizer.</p>
        </div>
        <div class="divide-y divide-slate-50">
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
        @include('admin.partials.pager', ['paginator' => $organizers, 'simple' => true])
    </div>
</div>
@endsection
