@extends('layouts.admin')
@section('title', 'Cities')
@section('heading', 'Cities')

@section('content')
<div class="grid lg:grid-cols-3 gap-5 mb-5">
    <div class="lg:col-span-2 space-y-5">
        <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex flex-wrap gap-3">
                <input name="q" value="{{ request('q') }}" placeholder="Search cities…" class="flex-1 min-w-[180px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
                <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status')==='active')>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                </select>
                <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
                @if(collect(request()->only(['q','status']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty())
                    <a href="{{ route('admin.cities.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear</a>
                @endif
            </div>
        </form>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
            @forelse($cities as $city)
                <div class="p-4" x-data="{ editing: false }">
                    <div x-show="!editing" class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[140px]">
                            <div class="font-bold">{{ $city->name }}</div>
                            <div class="text-xs text-mute">{{ $city->slug }} · order {{ $city->sort_order }} · {{ number_format($city->events_count) }} events</div>
                        </div>
                        @if($city->is_active)
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-100">active</span>
                        @else
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-slate-50 text-mute border-slate-100">inactive</span>
                        @endif
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" @click="editing = true" class="px-2.5 py-1 rounded-lg bg-slate-100 text-ink text-xs font-bold hover:bg-slate-200">Edit</button>
                            <form method="POST" action="{{ route('admin.cities.toggle', $city) }}">@csrf
                                <button class="px-2.5 py-1 rounded-lg bg-slate-50 text-mute text-xs font-bold border border-slate-200">{{ $city->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.cities.destroy', $city) }}" onsubmit="return confirm('Delete this city?')">
                                @csrf @method('DELETE')
                                <button class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-bold disabled:opacity-40" @disabled($city->events_count > 0)>Delete</button>
                            </form>
                        </div>
                    </div>

                    <form x-cloak x-show="editing" method="POST" action="{{ route('admin.cities.update', $city) }}" class="grid sm:grid-cols-4 gap-3 items-end">
                        @csrf
                        @method('PUT')
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Name</label>
                            <input name="name" value="{{ old('name', $city->name) }}" required maxlength="100" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Sort order</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $city->sort_order) }}" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
                            <select name="is_active" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="1" @selected($city->is_active)>Active</option>
                                <option value="0" @selected(! $city->is_active)>Inactive</option>
                            </select>
                        </div>
                        <div class="sm:col-span-4 flex flex-wrap items-center gap-2">
                            <button class="px-3 py-2 rounded-xl bg-brand text-white text-xs font-bold">Save changes</button>
                            <button type="button" @click="editing = false" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-mute">Cancel</button>
                            <span class="text-xs text-mute">Renaming updates events that use this city.</span>
                        </div>
                    </form>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-mute text-sm">No cities found.</div>
            @endforelse
        </div>
        @include('admin.partials.pager', ['paginator' => $cities])
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 h-fit">
        <h3 class="text-sm font-bold mb-1">Add city</h3>
        <p class="text-xs text-mute mb-4">Active cities appear in organizer event forms and public filters.</p>
        <form method="POST" action="{{ route('admin.cities.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Name *</label>
                <input name="name" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Hargeisa" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order') }}" min="0" placeholder="Auto" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" @checked(old('is_active', '1') == '1' || old('is_active') === true || old('is_active') === 1 || old('is_active') === null)>
                <span class="font-semibold">Active</span>
            </label>
            <button class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Create city</button>
        </form>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
@endsection
