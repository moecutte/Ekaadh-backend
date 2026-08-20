@extends('layouts.app')

@section('title', 'Ekaadh')

@push('head')
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@1,9..144,500;1,9..144,600&display=swap" rel="stylesheet">
@endpush

@section('content')
@php
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

@include('events._hero-stage')

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
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/45 to-black/10"></div>
                        <div class="absolute top-3 left-3 bg-brand text-white text-xs font-extrabold px-3 py-1 rounded-full shadow-md shadow-black/25">{{ __('ui.featured') }}</div>
                        @if($event->isExpired())
                            <div class="absolute top-3 right-3 bg-slate-900/90 text-white text-xs font-extrabold px-3 py-1 rounded-full shadow-md shadow-black/25">{{ __('ui.expired') }}</div>
                        @endif
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            @if($event->category)
                                <span class="inline-flex text-[11px] font-bold px-2.5 py-0.5 rounded-full {{ $catBadge[$event->category] ?? 'bg-white text-ink' }} shadow-md shadow-black/20">{{ $event->category }}</span>
                            @endif
                            <h3 class="font-extrabold text-white mt-2 leading-snug drop-shadow-[0_2px_8px_rgba(0,0,0,0.65)] {{ $i === 0 ? 'text-2xl' : 'text-base' }}">{{ $event->title }}</h3>
                            <div class="flex flex-wrap items-end justify-between gap-2 mt-2">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-white text-xs font-semibold drop-shadow-[0_1px_6px_rgba(0,0,0,0.8)]">
                                    <span>{{ $event->event_date?->format('M j, Y') }}</span>
                                    @if($event->city)<span>{{ $event->city }}</span>@endif
                                </div>
                                @if($event->isFreeEvent() || (float) $price === 0.0)
                                    <span class="shrink-0 inline-flex items-center rounded-full bg-emerald-500 text-white text-xs font-extrabold px-2.5 py-1 shadow-lg shadow-black/30">{{ __('ui.free') }}</span>
                                @elseif($price !== null)
                                    <span class="shrink-0 inline-flex items-center rounded-full bg-white text-ink text-xs font-extrabold px-2.5 py-1 shadow-lg shadow-black/30">{{ __('ui.from_price', ['price' => number_format((float)$price, 0)]) }}</span>
                                @endif
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
            <h2 class="text-2xl font-extrabold text-ink">{{ $homeEventsWhen === 'past' ? __('ui.past_events') : __('ui.upcoming_events') }}</h2>
            <a href="{{ route('events.index', ['when' => $homeEventsWhen]) }}" class="text-brand font-bold text-sm hover:underline flex items-center gap-1">
                {{ __('ui.view_all') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($upcoming as $event)
                @include('events._card', ['event' => $event])
            @empty
                <p class="text-mute col-span-full py-8">{{ $homeEventsWhen === 'past' ? __('ui.no_past_events') : __('ui.no_upcoming') }}</p>
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
                [
                    'num' => '1',
                    'title' => __('ui.step_browse_title'),
                    'desc' => __('ui.step_browse_desc'),
                    'paths' => ['M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                ],
                [
                    'num' => '2',
                    'title' => __('ui.step_buy_title'),
                    'desc' => __('ui.step_buy_desc'),
                    'paths' => ['M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ],
                [
                    'num' => '3',
                    'title' => __('ui.step_qr_title'),
                    'desc' => __('ui.step_qr_desc'),
                    'stroke' => '1.5',
                    'paths' => [
                        'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z',
                        'M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z',
                    ],
                ],
            ] as $step)
                <div class="bg-white rounded-2xl p-6 border border-slate-100 text-center hover:border-brand/30 transition-colors">
                    <div class="relative inline-flex mb-5">
                        <div class="w-14 h-14 bg-brand/10 rounded-2xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $step['stroke'] ?? '2' }}">
                                @foreach($step['paths'] as $path)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                                @endforeach
                            </svg>
                        </div>
                        <span class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-brand text-white text-xs font-extrabold rounded-full flex items-center justify-center">{{ $step['num'] }}</span>
                    </div>
                    <h3 class="font-extrabold text-ink text-base mb-2">{{ $step['title'] }}</h3>
                    <p class="text-mute text-sm leading-relaxed">{{ $step['desc'] }}</p>
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
            <div class="w-full sm:w-auto sm:shrink-0 max-w-full">
                @include('partials.operator-logos', ['size' => 'hero'])
            </div>
        </div>
    </section>
</div>
@endsection
