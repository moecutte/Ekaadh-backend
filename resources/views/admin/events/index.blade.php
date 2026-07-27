@extends('layouts.admin')
@section('title', 'Events')
@section('heading', 'Events')

@section('content')
@php
    $type = $type ?? 'public';
    $isPrivateTab = $type === 'private';
    $tabQuery = request()->except(['type', 'page']);
@endphp

<div class="mb-5 flex gap-1 p-1 bg-white rounded-2xl border border-slate-100 shadow-sm w-fit">
    <a href="{{ route('admin.events.index', array_merge($tabQuery, ['type' => 'public'])) }}"
       class="px-4 py-2 rounded-xl text-sm font-bold transition {{ ! $isPrivateTab ? 'bg-brand text-white shadow-sm' : 'text-mute hover:text-ink hover:bg-slate-50' }}">
        Public
        <span class="ml-1 text-[11px] font-extrabold opacity-80">{{ number_format($tabCounts['public'] ?? 0) }}</span>
    </a>
    <a href="{{ route('admin.events.index', array_merge($tabQuery, ['type' => 'private'])) }}"
       class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isPrivateTab ? 'bg-brand text-white shadow-sm' : 'text-mute hover:text-ink hover:bg-slate-50' }}">
        Private
        <span class="ml-1 text-[11px] font-extrabold opacity-80">{{ number_format($tabCounts['private'] ?? 0) }}</span>
    </a>
</div>

<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="sm:col-span-2 lg:col-span-2">
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Search</label>
            <input name="q" value="{{ request('q') }}"
                   placeholder="{{ $isPrivateTab ? 'Title, city, venue, buyer…' : 'Title, city, venue, organizer…' }}"
                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
            <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="">All statuses</option>
                @foreach(['draft','pending_review','published','completed','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ str_replace('_',' ', $s) }}</option>
                @endforeach
            </select>
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
        @unless($isPrivateTab)
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Organizer</label>
                <select name="organizer_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <option value="">All organizers</option>
                    @foreach($filterOptions['organizers'] as $org)
                        <option value="{{ $org->id }}" @selected((string) request('organizer_id') === (string) $org->id)>{{ $org->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Featured</label>
                <select name="featured" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <option value="">All</option>
                    <option value="1" @selected(request('featured')==='1')>Featured only</option>
                    <option value="0" @selected(request('featured')==='0')>Not featured</option>
                </select>
            </div>
        @endunless
        <div>
            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Per page</label>
            <select name="per_page" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                @foreach([10, 20, 50, 100] as $n)
                    <option value="{{ $n }}" @selected((int) ($perPage ?? 20) === $n)>{{ $n }}</option>
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
    </div>
    <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-slate-50">
        <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Apply filters</button>
        @if($filtersActive)
            <a href="{{ route('admin.events.index', ['type' => $type]) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">Clear filters</a>
        @endif
        <span class="text-xs text-mute ml-1">{{ number_format($events->total()) }} {{ $isPrivateTab ? 'private' : 'public' }} event{{ $events->total() === 1 ? '' : 's' }}</span>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                <tr>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">{{ $isPrivateTab ? 'Buyer' : 'Organizer' }}</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    <tr class="border-t border-slate-50 align-top">
                        <td class="px-4 py-3">
                            <div class="font-bold flex items-center gap-2 flex-wrap">
                                {{ $event->title }}
                                @if($event->is_featured)<span class="text-[10px] font-bold text-amber-600 bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded">Featured</span>@endif
                            </div>
                            <div class="text-xs text-mute">
                                {{ $isPrivateTab ? ($event->privateEventCategory?->name ?? 'Private') : $event->category }}
                                @if($event->city) · {{ $event->city }}@endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($isPrivateTab)
                                <div class="font-semibold text-ink">{{ $event->owner?->name ?? '—' }}</div>
                                <div class="text-xs text-mute">
                                    @if($event->owner?->phone){{ $event->owner->phone }}@endif
                                    @if($event->owner?->email)
                                        @if($event->owner?->phone) · @endif{{ $event->owner->email }}
                                    @endif
                                </div>
                            @else
                                <div class="text-mute">{{ $event->organizer?->business_name ?? '—' }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-mute whitespace-nowrap">{{ $event->event_date?->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.events.status', $event) }}" class="flex items-center gap-1">
                                @csrf
                                <select name="status" class="rounded-lg border border-slate-200 px-2 py-1 text-xs" onchange="this.form.submit()">
                                    @foreach(['draft','pending_review','published','completed','cancelled'] as $s)
                                        <option value="{{ $s }}" @selected($event->status===$s)>{{ str_replace('_',' ', $s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @if($event->status === 'pending_review')
                                    <form method="POST" action="{{ route('admin.events.approve', $event) }}">@csrf
                                        <button class="px-2.5 py-1 rounded-lg bg-brand text-white text-xs font-bold">Publish</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.events.reject', $event) }}">@csrf
                                        <button class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-bold">Reject</button>
                                    </form>
                                @endif
                                @unless($isPrivateTab)
                                    <form method="POST" action="{{ route('admin.events.feature', $event) }}">@csrf
                                        <button class="px-2.5 py-1 rounded-lg bg-slate-50 text-mute text-xs font-bold border border-slate-200">{{ $event->is_featured ? 'Unfeature' : 'Feature' }}</button>
                                    </form>
                                    <a href="{{ route('events.show', $event->slug) }}" target="_blank" class="px-2.5 py-1 rounded-lg text-xs font-bold text-brand">View ↗</a>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-mute">No {{ $isPrivateTab ? 'private' : 'public' }} events found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($events->total() > 0)
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-xs text-mute">
            Showing {{ $events->firstItem() }}–{{ $events->lastItem() }} of {{ number_format($events->total()) }}
        </p>
        <div>{{ $events->onEachSide(1)->links() }}</div>
    </div>
@endif
@endsection
