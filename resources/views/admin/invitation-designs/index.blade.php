@extends('layouts.admin')
@section('title', 'Invitation Designs')
@section('heading', 'Invitation Designs')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-mute max-w-xl">Upload graphics, set editable text slots, and control what customers fill when creating private events.</p>
    <a href="{{ route('admin.invitation-designs.create') }}" class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">New design</a>
</div>

<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <div class="flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search designs…" class="flex-1 min-w-[180px] rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
        <select name="category" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) request('category') === (string) $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
        </select>
        <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
    @forelse($designs as $design)
        <div class="p-4 flex flex-wrap items-center gap-4">
            <div class="w-16 h-20 rounded-lg overflow-hidden border border-slate-100 bg-slate-50 shrink-0">
                @if($design->thumbnail_url)
                    <img src="{{ $design->thumbnail_url }}" class="w-full h-full object-cover" alt="">
                @else
                    <div class="w-full h-full" style="background: {{ $design->card_bg ?? '#eee' }};"></div>
                @endif
            </div>
            <div class="flex-1 min-w-[180px]">
                <div class="font-bold">{{ $design->category?->name ?? 'Design' }} · #{{ $design->id }}</div>
                <div class="text-xs text-mute mt-0.5">
                    {{ $design->tier }} · {{ $design->render_mode }}
                    · {{ $design->fields_count }} fields · {{ $design->events_count }} events
                </div>
            </div>
            @if($design->is_active)
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">active</span>
            @else
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-50 text-mute border border-slate-100">inactive</span>
            @endif
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('admin.invitation-designs.edit', $design) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 text-xs font-bold">Edit</a>
                <form method="POST" action="{{ route('admin.invitation-designs.toggle', $design) }}">@csrf
                    <button class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-bold text-mute">{{ $design->is_active ? 'Deactivate' : 'Activate' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.invitation-designs.destroy', $design) }}" onsubmit="return confirm('Delete this design?')">
                    @csrf @method('DELETE')
                    <button class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-bold" @disabled($design->events_count > 0)>Delete</button>
                </form>
            </div>
        </div>
    @empty
        <div class="px-4 py-10 text-center text-mute text-sm">
            No invitation designs yet. Click <strong>New design</strong> to upload a graphic and add text fields.
        </div>
    @endforelse
</div>
<div class="mt-4">{{ $designs->links() }}</div>
@endsection
