@extends('layouts.admin')
@section('title', 'Private Event Categories')
@section('heading', 'Private Event Categories')

@section('content')
<div class="grid lg:grid-cols-3 gap-5 mb-5">
    <div class="lg:col-span-2 space-y-5">
        <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex flex-wrap gap-3">
                <input name="q" value="{{ request('q') }}" placeholder="Search categories…" class="flex-1 min-w-[180px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
                <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status')==='active')>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                </select>
                <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
            </div>
        </form>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
            @forelse($categories as $category)
                <div class="p-4" x-data="{ editing: false }">
                    <div x-show="!editing" class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[140px]">
                            <div class="font-bold">{{ $category->name }}</div>
                            <div class="text-xs text-mute">
                                {{ $category->slug }} · order {{ $category->sort_order }} · {{ number_format($category->events_count) }} events
                                @if($category->requires_couple_names)
                                    · <span class="text-brand font-semibold">couple names required</span>
                                @endif
                            </div>
                        </div>
                        @if($category->is_active)
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-100">active</span>
                        @else
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-slate-50 text-mute border-slate-100">inactive</span>
                        @endif
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" @click="editing = true" class="px-2.5 py-1 rounded-lg bg-slate-100 text-ink text-xs font-bold hover:bg-slate-200">Edit</button>
                            <form method="POST" action="{{ route('admin.private-event-categories.toggle', $category) }}">@csrf
                                <button class="px-2.5 py-1 rounded-lg bg-slate-50 text-mute text-xs font-bold border border-slate-200">{{ $category->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.private-event-categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-bold disabled:opacity-40" @disabled($category->events_count > 0)>Delete</button>
                            </form>
                        </div>
                    </div>

                    <form x-cloak x-show="editing" method="POST" action="{{ route('admin.private-event-categories.update', $category) }}" class="grid sm:grid-cols-4 gap-3 items-end">
                        @csrf
                        @method('PUT')
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Name</label>
                            <input name="name" value="{{ old('name', $category->name) }}" required maxlength="100" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Sort order</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
                            <select name="is_active" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="1" @selected($category->is_active)>Active</option>
                                <option value="0" @selected(! $category->is_active)>Inactive</option>
                            </select>
                        </div>
                        <div class="sm:col-span-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="requires_couple_names" value="0">
                                <input type="checkbox" name="requires_couple_names" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" @checked(old('requires_couple_names', $category->requires_couple_names))>
                                <span class="font-semibold">Require couple names when creating (Aroos / Meher)</span>
                            </label>
                        </div>
                        <div class="sm:col-span-4 flex flex-wrap items-center gap-2">
                            <button class="px-3 py-2 rounded-xl bg-brand text-white text-xs font-bold">Save changes</button>
                            <button type="button" @click="editing = false" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-mute">Cancel</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-mute text-sm">No categories found.</div>
            @endforelse
        </div>
        @include('admin.partials.pager', ['paginator' => $categories])
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 h-fit">
        <h3 class="text-sm font-bold mb-1">Add category</h3>
        <p class="text-xs text-mute mb-4">Examples: Aroos, Meher, Xaflad, Casho. Turn on couple names for wedding-style events.</p>
        <form method="POST" action="{{ route('admin.private-event-categories.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Name *</label>
                <input name="name" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Aroos" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order') }}" min="0" placeholder="Auto" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="requires_couple_names" value="0">
                <input type="checkbox" name="requires_couple_names" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" @checked(old('requires_couple_names'))>
                <span class="font-semibold">Require couple names</span>
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" checked>
                <span class="font-semibold">Active</span>
            </label>
            <button class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Create category</button>
        </form>
    </div>
</div>
@endsection
