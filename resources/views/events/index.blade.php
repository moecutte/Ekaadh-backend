@extends('layouts.app')

@section('title', 'Browse Events')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-extrabold mb-2">Browse Events</h1>
    <p class="text-mute mb-8">Find concerts, sports, tech talks, and more.</p>

    <form method="GET" class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 mb-8 grid sm:grid-cols-4 gap-3 shadow-sm">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search..."
            class="sm:col-span-2 rounded-xl bg-page px-4 py-3 text-sm font-medium outline-none border border-transparent focus:border-brand">
        <select name="category" class="rounded-xl bg-page px-4 py-3 text-sm font-medium outline-none">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="city" class="flex-1 rounded-xl bg-page px-4 py-3 text-sm font-medium outline-none">
                <option value="">All cities</option>
                @foreach($cities as $city)
                    <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-brand text-white font-extrabold px-5 text-sm hover:bg-brand-dark transition">Go</button>
        </div>
    </form>

    <p class="text-xs font-bold uppercase tracking-wider text-mute mb-4">{{ $events->total() }} event{{ $events->total() === 1 ? '' : 's' }}</p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($events as $event)
            @include('events._card', ['event' => $event])
        @empty
            <div class="col-span-full text-center py-16 bg-white rounded-3xl border border-slate-100">
                <p class="font-bold text-lg mb-2">No events found</p>
                <p class="text-mute text-sm mb-4">Try a different search or category.</p>
                <a href="{{ route('events.index') }}" class="text-brand font-bold text-sm">Clear filters</a>
            </div>
        @endforelse
    </div>

    <div class="mt-10">{{ $events->links() }}</div>
</div>
@endsection
