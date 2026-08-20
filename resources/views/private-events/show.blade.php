@extends('layouts.app')

@section('title', $event->title)

@section('content')
@php
    $thumb = $event->invitationDesign?->thumbnail_url
        ?: $event->invitationDesign?->graphic_url
        ?: $event->cover_image;
    $pct = $capacity > 0 ? min(100, (int) round(($sold / $capacity) * 100)) : 0;
    $card = 'rounded-[1.75rem] bg-white border border-slate-100 shadow-[0_18px_40px_-28px_rgba(15,26,46,0.35)] overflow-hidden';
    $bar = 'h-1 bg-gradient-to-r from-brand via-[#4a51b8] to-brand/40';
@endphp
<div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
    <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-7">
        <a href="{{ route('private-events.index') }}" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-mute hover:text-brand transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('ui.my_private_events') }}
        </a>

        <header class="mt-4 mb-4 {{ $card }}">
            <div class="{{ $bar }}"></div>
            <div class="px-5 sm:px-6 py-4 sm:py-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-3.5 min-w-0">
                        <div class="w-14 h-[4.5rem] sm:w-16 sm:h-[5.25rem] rounded-xl overflow-hidden border border-slate-100 shrink-0 bg-slate-100">
                            @if($thumb)
                                <img src="{{ $thumb }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-mute text-[10px] font-semibold">{{ __('ui.no_preview') }}</div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.event_details') }}</p>
                            <h1 class="mt-1 text-xl sm:text-2xl font-extrabold tracking-tight text-ink leading-tight">{{ $event->title }}</h1>
                            <p class="text-sm text-mute mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $event->event_date?->format('M j, Y') }}
                                    @if($event->event_time) · {{ date('g:i A', strtotime($event->event_time)) }}@endif
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $event->venue }}
                                </span>
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2.5">
                                <a href="{{ route('private-events.invitations.index', $event) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-brand text-white text-sm font-bold shadow-lg shadow-brand/20 hover:bg-brand-dark transition-colors">
                                    {{ __('ui.manage_invitations') }}
                                </a>
                                <a href="{{ route('private-events.capacity.create', $event) }}"
                                   class="inline-flex px-4 py-2 rounded-2xl border border-slate-200 bg-white text-ink text-sm font-bold hover:bg-slate-50 transition-colors">
                                    {{ __('ui.buy_more_tickets') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="{{ $card }}">
            <div class="{{ $bar }}"></div>
            <div class="px-5 sm:px-7 py-5 sm:py-6">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand mb-4">{{ __('ui.capacity_used') }}</p>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 text-center">
                        <div class="text-2xl font-black text-brand tabular-nums">{{ $capacity }}</div>
                        <div class="text-[10px] font-bold text-mute uppercase tracking-wider mt-1">{{ __('ui.paid_seats') }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 text-center">
                        <div class="text-2xl font-black text-ink tabular-nums">{{ $sold }}</div>
                        <div class="text-[10px] font-bold text-mute uppercase tracking-wider mt-1">{{ __('ui.invited') }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 text-center">
                        <div class="text-2xl font-black text-ink tabular-nums">{{ $remaining }}</div>
                        <div class="text-[10px] font-bold text-mute uppercase tracking-wider mt-1">{{ __('ui.remaining_label') }}</div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-[11px] font-bold mb-2">
                        <span class="text-mute">{{ __('ui.capacity_used') }}</span>
                        <span class="text-ink">{{ $pct }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        @if($event->description)
            <div class="mt-5 {{ $card }}">
                <div class="{{ $bar }}"></div>
                <div class="px-5 sm:px-7 py-6">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand mb-3">{{ __('ui.about') }}</p>
                    <div class="text-sm text-ink/80 leading-relaxed whitespace-pre-line">{{ $event->description }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
