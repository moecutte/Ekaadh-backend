@extends('layouts.organizer')
@section('title', 'My Events')
@section('heading', 'My Events')
@section('actions')
    <a href="{{ route('organizer.events.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand text-white text-sm font-bold rounded-xl hover:bg-brand-dark">+ New Event</a>
@endsection

@section('content')
@php
    $statuses = ['draft', 'pending_review', 'published', 'completed', 'cancelled'];
    $statusLabels = [
        'draft' => 'Draft',
        'pending_review' => 'Under review',
        'published' => 'Published',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    $activeStatus = request('status');
    $queryWithoutStatus = request()->except('status', 'page');
@endphp

<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('organizer.events.index', $queryWithoutStatus) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-colors {{ $activeStatus === null || $activeStatus === '' ? 'bg-brand text-white border-brand' : 'bg-white text-mute border-slate-200 hover:border-brand/40 hover:text-ink' }}">
        All
        <span class="opacity-70">{{ $statusCounts->sum() }}</span>
    </a>
    @foreach($statuses as $s)
        <a href="{{ route('organizer.events.index', array_merge($queryWithoutStatus, ['status' => $s])) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-colors {{ $activeStatus === $s ? 'bg-brand text-white border-brand' : 'bg-white text-mute border-slate-200 hover:border-brand/40 hover:text-ink' }}">
            {{ $statusLabels[$s] }}
            <span class="opacity-70">{{ $statusCounts[$s] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    @if(request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
    @endif
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Search</label>
            <input name="q" value="{{ request('q') }}" placeholder="Title, venue, city, category…" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Category</label>
            <select name="category" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All categories</option>
                @foreach($filterOptions['categories'] as $category)
                    <option value="{{ $category }}" @selected(request('category')===$category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">City</label>
            <select name="city" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All cities</option>
                @foreach($filterOptions['cities'] as $city)
                    <option value="{{ $city }}" @selected(request('city')===$city)>{{ $city }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Event date from</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Event date to</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Sort by</label>
            <select name="sort" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="" @selected(!request('sort'))>Newest first</option>
                <option value="date_asc" @selected(request('sort')==='date_asc')>Event date (soonest)</option>
                <option value="date_desc" @selected(request('sort')==='date_desc')>Event date (latest)</option>
                <option value="title" @selected(request('sort')==='title')>Title A–Z</option>
            </select>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-slate-50">
        <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Apply filters</button>
        @if($filtersActive)
            <a href="{{ route('organizer.events.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear all</a>
            <span class="text-xs text-mute ml-1">{{ $events->total() }} result{{ $events->total() === 1 ? '' : 's' }}</span>
        @endif
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-[11px] uppercase tracking-wider text-mute bg-slate-50/80">
            <tr>
                <th class="text-left px-4 py-3 font-bold">Event</th>
                <th class="text-left px-4 py-3 font-bold">Date</th>
                <th class="text-left px-4 py-3 font-bold">Status</th>
                <th class="text-left px-4 py-3 font-bold">Sold</th>
                <th class="text-left px-4 py-3 font-bold">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                @php
                    $sold = $event->ticketTypes->sum('quantity_sold');
                    $total = $event->ticketTypes->sum('quantity_available');
                    $pct = $total > 0 ? round(($sold / $total) * 100) : 0;
                @endphp
                <tr class="border-t border-slate-50 hover:bg-slate-50/50">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-16 h-10 rounded-lg overflow-hidden bg-slate-100 shrink-0">
                                @if($event->cover_image)<img src="{{ $event->cover_image }}" class="w-full h-full object-cover" alt="">@endif
                            </div>
                            <div>
                                <div class="font-bold truncate max-w-[200px]">{{ $event->title }}</div>
                                <div class="text-xs text-mute">
                                    {{ $event->category }}@if($event->city) · {{ $event->city }}@endif
                                    · {{ $event->isFreeEvent() ? 'Free' : 'Priced' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">{{ $event->event_date?->format('M j, Y') }}</td>
                    <td class="px-4 py-4">
                        @if($event->isExpired())
                            <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border bg-slate-100 text-slate-600 border-slate-200">{{ __('ui.expired') }}</span>
                        @else
                        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border
                            {{ $event->status === 'published' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($event->status === 'pending_review' ? 'bg-amber-50 text-amber-700 border-amber-100' : ($event->status === 'cancelled' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-slate-50 text-slate-600 border-slate-200')) }}">
                            {{ $statusLabels[$event->status] ?? ucfirst(str_replace('_',' ',$event->status)) }}
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-brand rounded-full" style="width:{{ $pct }}%"></div></div>
                            <span class="text-xs text-mute">{{ $sold }}/{{ $total }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            @if($event->needsPackagePayment())
                                <a href="{{ route('organizer.events.pay', $event) }}" class="text-xs font-bold text-amber-700">Pay package</a>
                            @endif
                            <a href="{{ route('events.show', $event->slug) }}" target="_blank" class="text-xs font-bold text-mute hover:text-brand">View</a>
                            <a href="{{ route('organizer.events.edit', $event) }}" class="text-xs font-bold text-brand">Edit</a>
                            <a href="{{ route('organizer.events.invitations.index', $event) }}" class="text-xs font-bold text-brand">
                                Guests
                                @if(($event->invitations_count ?? 0) > 0 || $event->pendingInviteCount() > 0)
                                    <span class="text-mute">({{ $event->invitations_count + $event->pendingInviteCount() }})</span>
                                @endif
                            </a>
                            <form method="POST" action="{{ route('organizer.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-bold text-red-400">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-mute">
                    @if($filtersActive)
                        No events match your filters. <a href="{{ route('organizer.events.index') }}" class="text-brand font-bold">Clear filters</a>
                    @else
                        No events yet. <a href="{{ route('organizer.events.create') }}" class="text-brand font-bold">Create one</a>
                    @endif
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $events->links() }}</div>
@endsection
