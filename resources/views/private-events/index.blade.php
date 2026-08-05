@extends('layouts.app')

@section('title', __('ui.my_private_events'))

@section('content')
<div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-12">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-brand mb-2">{{ __('ui.private_invitations') }}</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-ink">{{ __('ui.your_events') }}</h1>
                <p class="text-sm text-mute mt-2 max-w-lg">{{ __('ui.private_events_index_sub') }}</p>
            </div>
            <a href="{{ route('private-events.create') }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-brand text-white text-sm font-bold shadow-lg shadow-brand/25 hover:bg-brand-dark hover:-translate-y-0.5 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('ui.create_event') }}
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm font-semibold px-4 py-3.5">{{ session('success') }}</div>
        @endif

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($events as $event)
                @php
                    $capacity = $event->ticketTypes->sum('quantity_available');
                    $sold = $event->ticketTypes->sum('quantity_sold');
                    $thumb = $event->invitationDesign?->thumbnail_url
                        ?: $event->invitationDesign?->graphic_url
                        ?: $event->cover_image;
                    $paid = $event->status === 'published';
                    $pct = $capacity > 0 ? min(100, (int) round(($sold / $capacity) * 100)) : 0;
                @endphp
                <article class="group bg-white rounded-[1.35rem] border border-slate-100/80 shadow-sm hover:shadow-xl hover:shadow-slate-200/80 hover:-translate-y-0.5 transition-all duration-300 overflow-hidden flex flex-col">
                    <div class="relative aspect-[3/4] bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
                        @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $event->title }}"
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-mute">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-xs font-semibold">{{ __('ui.no_design_preview') }}</span>
                            </div>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-ink/70 via-ink/25 to-transparent pointer-events-none"></div>
                        <span class="absolute top-3 left-3 text-[10px] font-extrabold tracking-wide uppercase px-2.5 py-1 rounded-full border backdrop-blur-md
                            {{ $paid ? 'bg-emerald-500/90 text-white border-emerald-400/40' : 'bg-amber-400/95 text-ink border-amber-300/50' }}">
                            {{ $paid ? __('ui.paid') : __('ui.awaiting_payment') }}
                        </span>
                        <div class="absolute bottom-3 left-3 right-3 text-white">
                            <h2 class="font-extrabold text-base leading-snug line-clamp-2 drop-shadow-sm">{{ $event->title }}</h2>
                            <p class="text-[11px] text-white/80 mt-1 font-medium">
                                {{ $event->event_date?->format('M j, Y') }}
                                @if($event->event_time) · {{ date('g:i A', strtotime($event->event_time)) }}@endif
                            </p>
                        </div>
                    </div>

                    <div class="p-4 flex flex-col flex-1 gap-3">
                        <div class="flex items-center gap-1.5 text-mute text-xs min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="truncate">{{ $event->venue }}{{ $event->city ? ', '.$event->city : '' }}</span>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-[11px] font-bold mb-1.5">
                                <span class="text-mute">{{ __('ui.invitations') }}</span>
                                <span class="text-ink">{{ $sold }}/{{ $capacity }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>

                        <div class="mt-auto flex gap-2 pt-1">
                            @if(! $paid)
                                <a href="{{ route('private-events.pay', $event) }}"
                                   class="flex-1 text-center px-3 py-2.5 rounded-xl bg-brand text-white text-xs font-bold hover:bg-brand-dark transition-colors">
                                    {{ __('ui.pay_now') }}
                                </a>
                            @else
                                <a href="{{ route('private-events.invitations.index', $event) }}"
                                   class="flex-1 text-center px-3 py-2.5 rounded-xl bg-brand text-white text-xs font-bold hover:bg-brand-dark transition-colors">
                                    {{ __('ui.invitations') }}
                                </a>
                                <a href="{{ route('private-events.show', $event) }}"
                                   class="px-3 py-2.5 rounded-xl bg-brand-soft text-brand text-xs font-bold hover:bg-brand/15 transition-colors">
                                    {{ __('ui.details') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="sm:col-span-2 lg:col-span-3 relative overflow-hidden rounded-[1.5rem] border border-dashed border-slate-200 bg-white px-6 py-16 text-center">
                    <div class="pointer-events-none absolute -top-16 left-1/2 -translate-x-1/2 w-64 h-64 rounded-full bg-brand/5 blur-3xl"></div>
                    <div class="relative mx-auto w-16 h-16 rounded-2xl bg-brand-soft flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="text-lg font-extrabold text-ink">{{ __('ui.no_private_events') }}</h2>
                    <p class="text-sm text-mute mt-1.5 max-w-sm mx-auto">{{ __('ui.no_private_events_desc') }}</p>
                    <a href="{{ route('private-events.create') }}" class="inline-flex mt-6 px-5 py-3 rounded-2xl bg-brand text-white text-sm font-bold hover:bg-brand-dark transition-colors">
                        {{ __('ui.create_first_event') }}
                    </a>
                </div>
            @endforelse
        </div>

        @if($events->hasPages())
            <div class="mt-8">{{ $events->links() }}</div>
        @endif
    </div>
</div>
@endsection
