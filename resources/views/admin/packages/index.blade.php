@extends('layouts.admin')
@section('title', 'Pricing Packages')
@section('heading', 'Pricing Packages')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-5 flex flex-wrap items-center justify-between gap-4">
    <div class="min-w-0">
        <p class="font-bold text-sm text-ink">Show packages on Create Event page</p>
        <p class="text-xs text-mute mt-0.5">
            When off, pricing cards are hidden from the public organizer landing page.
            Admin package assignment for organizers still works.
        </p>
    </div>
    <form method="POST" action="{{ route('admin.packages.front-visibility') }}" class="flex items-center gap-3 shrink-0">
        @csrf
        <input type="hidden" name="show_on_front" value="0">
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input
                type="checkbox"
                name="show_on_front"
                value="1"
                class="sr-only peer"
                @checked($showOnFront)
                onchange="this.form.submit()"
            >
            <span class="relative w-11 h-6 rounded-full bg-slate-200 peer-checked:bg-brand transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"></span>
            <span class="text-sm font-bold {{ $showOnFront ? 'text-brand' : 'text-mute' }}">
                {{ $showOnFront ? 'Visible' : 'Hidden' }}
            </span>
        </label>
    </form>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-5">
    <div class="lg:col-span-2 space-y-5">
        <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex flex-wrap gap-3">
                <input name="q" value="{{ request('q') }}" placeholder="Search packages…" class="flex-1 min-w-[180px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
                <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status')==='active')>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                </select>
                <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
                @if(collect(request()->only(['q','status']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty())
                    <a href="{{ route('admin.packages.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear</a>
                @endif
            </div>
        </form>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
            @forelse($packages as $package)
                <div class="p-4" x-data="{ editing: false }">
                    <div x-show="!editing" class="flex flex-wrap items-start gap-3">
                        <div class="flex-1 min-w-[180px]">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold">{{ $package->name }}</span>
                                @if($package->is_highlighted)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-brand/10 text-brand border border-brand/20">Highlighted</span>
                                @endif
                                @if($package->is_default)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100">Default</span>
                                @endif
                            </div>
                            <div class="text-xs text-mute mt-1">
                                {{ $package->displayPrice() }} {{ $package->displayPeriod() }}
                                · commission {{ $package->commission_rate !== null ? number_format((float) $package->commission_rate, 1).'%' : 'platform default' }}
                                · {{ number_format($package->organizers_count) }} organizers
                            </div>
                            <div class="text-xs text-mute mt-0.5">
                                Limits:
                                {{ $package->max_events_per_year ? $package->max_events_per_year.' events/year' : 'unlimited events' }}
                                ·
                                {{ $package->max_tickets_per_event ? number_format($package->max_tickets_per_event).' tickets/event' : 'unlimited tickets' }}
                            </div>
                            @if($package->description)
                                <p class="text-xs text-mute mt-1.5">{{ $package->description }}</p>
                            @endif
                        </div>
                        @if($package->is_active)
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-100">active</span>
                        @else
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-slate-50 text-mute border-slate-100">inactive</span>
                        @endif
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" @click="editing = true" class="px-2.5 py-1 rounded-lg bg-slate-100 text-ink text-xs font-bold hover:bg-slate-200">Edit</button>
                            <form method="POST" action="{{ route('admin.packages.toggle', $package) }}">@csrf
                                <button class="px-2.5 py-1 rounded-lg bg-slate-50 text-mute text-xs font-bold border border-slate-200">{{ $package->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Delete this package?')">
                                @csrf @method('DELETE')
                                <button class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-bold disabled:opacity-40" @disabled($package->organizers_count > 0 || $package->is_default)>Delete</button>
                            </form>
                        </div>
                    </div>

                    <form x-cloak x-show="editing" method="POST" action="{{ route('admin.packages.update', $package) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        @include('admin.packages._form_fields', ['package' => $package])
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <button class="px-3 py-2 rounded-xl bg-brand text-white text-xs font-bold">Save changes</button>
                            <button type="button" @click="editing = false" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-mute">Cancel</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-mute text-sm">No packages found.</div>
            @endforelse
        </div>
        <div>{{ $packages->links() }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 h-fit">
        <h3 class="text-sm font-bold mb-1">Add package</h3>
        <p class="text-xs text-mute mb-4">Packages control organizer commission rates, event limits, and public pricing cards.</p>
        <form method="POST" action="{{ route('admin.packages.store') }}" class="space-y-3">
            @csrf
            @include('admin.packages._form_fields', ['package' => null])
            <button class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Create package</button>
        </form>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
@endsection
