@extends('layouts.admin')
@section('title', 'System settings')
@section('heading', 'System settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-4">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
        <div>
            <h3 class="text-sm font-bold">Platform</h3>
            <p class="text-xs text-mute mt-0.5">Name shown across the product. Commission and service fee are under <a href="{{ route('admin.commission.edit') }}" class="font-bold text-brand hover:underline">Commission</a>.</p>
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Platform name</label>
            <input name="platform_name" value="{{ old('platform_name', $platformName) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
        <div>
            <h3 class="text-sm font-bold">Private events</h3>
            <p class="text-xs text-mute mt-0.5">Capacity pricing customers pay when creating a private invitation.</p>
        </div>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Ticket price ($)</label>
                <input type="number" step="0.01" min="0" name="private_ticket_price" value="{{ old('private_ticket_price', $privateTicketPrice) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Max tickets</label>
                <input type="number" min="1" name="private_ticket_max" value="{{ old('private_ticket_max', $privateTicketMax) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Premium design surcharge ($)</label>
                <input type="number" step="0.01" min="0" name="private_premium_design_surcharge" value="{{ old('private_premium_design_surcharge', $privatePremiumSurcharge) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
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
@endsection
