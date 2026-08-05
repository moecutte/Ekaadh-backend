@extends('layouts.app')

@section('title', 'Ekaadh')

@section('content')
@php
    $heroImage = asset('images/hero-event.jpg');
    $catBadge = [
        'Music' => 'bg-purple-100 text-purple-700',
        'Concerts' => 'bg-purple-100 text-purple-700',
        'Sports' => 'bg-green-100 text-green-700',
        'Comedy' => 'bg-pink-100 text-pink-700',
        'Tech' => 'bg-sky-100 text-sky-700',
        'Conferences' => 'bg-sky-100 text-sky-700',
        'Food' => 'bg-orange-100 text-orange-700',
        'Business' => 'bg-indigo-100 text-indigo-700',
        'Culture' => 'bg-amber-100 text-amber-700',
        'Education' => 'bg-teal-100 text-teal-700',
    ];
@endphp

{{-- Hero --}}
<section class="relative min-h-[520px] sm:min-h-[580px] flex items-center overflow-hidden">
    <div class="absolute inset-0 z-0 bg-[#0a1220]">
        <img src="{{ $heroImage }}" alt="Live event" class="w-full h-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-r from-[#0a1220]/95 via-[#0a1220]/70 to-[#0a1220]/20"></div>
    </div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 w-full">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 bg-brand/20 border border-brand/40 text-brand text-xs font-bold px-3 py-1.5 rounded-full mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ __('ui.hero_badge') }}
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-[1.1] mb-5">
                {{ __('ui.hero_title_1') }}<br>
                <span class="text-brand">{{ __('ui.hero_title_2') }}</span>
            </h1>
            <p class="text-slate-300 text-base sm:text-lg mb-8 leading-relaxed max-w-lg">
                {{ __('ui.hero_subtitle') }}
            </p>

            <form action="{{ route('events.index') }}" method="GET" class="bg-white rounded-2xl shadow-2xl max-w-2xl flex items-stretch overflow-hidden">
                <div class="flex items-center gap-2 flex-1 px-4 py-1 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" name="q" placeholder="{{ __('ui.search_events') }}" class="flex-1 outline-none text-sm text-ink placeholder-slate-400 bg-transparent min-w-0 py-3">
                </div>
                <div class="hidden sm:block w-px bg-slate-200 my-3 shrink-0"></div>
                <div class="hidden sm:flex items-center gap-1.5 px-3 min-w-[148px] shrink-0">
                    <select name="category" class="flex-1 outline-none text-sm text-ink bg-transparent cursor-pointer py-3">
                        <option value="">{{ __('ui.all_categories') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hidden sm:block w-px bg-slate-200 my-3 shrink-0"></div>
                <div class="hidden sm:flex items-center gap-1.5 px-3 min-w-[120px] shrink-0">
                    <select name="city" class="flex-1 outline-none text-sm text-ink bg-transparent cursor-pointer py-3">
                        <option value="">{{ __('ui.all_cities') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}">{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-brand hover:bg-brand-dark text-white font-bold px-6 text-sm shrink-0 transition-colors rounded-r-2xl">{{ __('ui.search') }}</button>
            </form>
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    @if($featured->isNotEmpty())
    <section class="pt-10 mb-14">
        <div class="flex items-center gap-3 mb-5">
            <h2 class="text-2xl font-extrabold text-ink">{{ __('ui.featured_trending') }}</h2>
            <span class="bg-brand/10 text-brand text-xs font-extrabold px-2.5 py-0.5 rounded-full">{{ __('ui.hot') }}</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach($featured->take(3) as $i => $event)
                @php $price = $event->ticketTypes->min('price'); @endphp
                <a href="{{ route('events.show', $event->slug) }}"
                   class="relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all group border border-slate-100 {{ $i === 0 ? 'md:col-span-2' : '' }}">
                    <div class="relative overflow-hidden bg-slate-200 {{ $i === 0 ? 'h-72' : 'h-52' }}">
                        @if($event->cover_image)
                            <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <div class="absolute top-3 left-3 bg-brand text-white text-xs font-extrabold px-3 py-1 rounded-full">{{ __('ui.featured') }}</div>
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            @if($event->category)
                                <span class="inline-flex text-[11px] font-bold px-2.5 py-0.5 rounded-full {{ $catBadge[$event->category] ?? 'bg-white/90 text-ink' }}">{{ $event->category }}</span>
                            @endif
                            <h3 class="font-extrabold text-white mt-2 leading-snug {{ $i === 0 ? 'text-2xl' : 'text-base' }}">{{ $event->title }}</h3>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-white/75 text-xs">
                                <span>{{ $event->event_date?->format('M j, Y') }}</span>
                                @if($event->city)<span>{{ $event->city }}</span>@endif
                                @if($price !== null)<span class="font-extrabold text-brand">{{ __('ui.from_price', ['price' => number_format((float)$price, 0)]) }}</span>@endif
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if($categories->isNotEmpty())
    <div class="flex gap-2 overflow-x-auto pb-5 hide-scrollbar">
        <a href="{{ route('home') }}" class="shrink-0 px-4 py-2 rounded-full text-sm font-bold bg-brand text-white shadow-sm">{{ __('ui.all') }}</a>
        @foreach($categories as $cat)
            <a href="{{ route('events.index', ['category' => $cat]) }}" class="shrink-0 px-4 py-2 rounded-full text-sm font-bold bg-white text-mute hover:bg-slate-50 border border-slate-200">{{ $cat }}</a>
        @endforeach
    </div>
    @endif

    <section class="mb-14">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-2xl font-extrabold text-ink">{{ __('ui.upcoming_events') }}</h2>
            <a href="{{ route('events.index') }}" class="text-brand font-bold text-sm hover:underline flex items-center gap-1">
                {{ __('ui.view_all') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($upcoming as $event)
                @include('events._card', ['event' => $event])
            @empty
                <p class="text-mute col-span-full py-8">{{ __('ui.no_upcoming') }}</p>
            @endforelse
        </div>
    </section>

    <section class="mb-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-extrabold text-ink mb-2">{{ __('ui.how_it_works') }}</h2>
            <p class="text-mute text-sm">{{ __('ui.how_it_works_sub') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach([
                ['1', __('ui.step_browse_title'), __('ui.step_browse_desc'), 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                ['2', __('ui.step_buy_title'), __('ui.step_buy_desc'), 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                ['3', __('ui.step_qr_title'), __('ui.step_qr_desc'), 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ] as [$num, $title, $desc, $icon])
                <div class="bg-white rounded-2xl p-6 border border-slate-100 text-center hover:border-brand/30 transition-colors">
                    <div class="relative inline-flex mb-5">
                        <div class="w-14 h-14 bg-brand/10 rounded-2xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </div>
                        <span class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-brand text-white text-xs font-extrabold rounded-full flex items-center justify-center">{{ $num }}</span>
                    </div>
                    <h3 class="font-extrabold text-ink text-base mb-2">{{ $title }}</h3>
                    <p class="text-mute text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mb-16">
        <div class="bg-ink rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
                <h3 class="text-white font-extrabold text-xl mb-1">{{ __('ui.pay_known_title') }}</h3>
                <p class="text-slate-400 text-sm">{{ __('ui.pay_known_desc') }}</p>
            </div>
            <div class="flex gap-4 shrink-0">
                <div class="bg-white/10 border border-white/20 rounded-xl px-5 py-3 text-center">
                    <p class="text-white font-extrabold text-sm">Zaad</p>
                    <p class="text-slate-400 text-xs">Telesom</p>
                </div>
                <div class="bg-white/10 border border-white/20 rounded-xl px-5 py-3 text-center">
                    <p class="text-white font-extrabold text-sm">eDahab</p>
                    <p class="text-slate-400 text-xs">Somtel</p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
