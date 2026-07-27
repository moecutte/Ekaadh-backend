@extends('layouts.app')

@section('title', 'Invitations — '.$event->title)

@section('content')
@php
    $thumb = $event->invitationDesign?->thumbnail_url
        ?: $event->invitationDesign?->graphic_url
        ?: $event->cover_image;
    $capacity = $event->ticketTypes->sum('quantity_available');
    $sold = $event->ticketTypes->sum('quantity_sold');
@endphp
<div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12">
        <div class="flex flex-wrap items-start justify-between gap-5 mb-8">
            <div class="flex items-start gap-4 min-w-0">
                @if($thumb)
                    <div class="w-16 h-[5.25rem] sm:w-20 sm:h-[6.5rem] rounded-xl overflow-hidden border border-slate-100 shadow-md shrink-0 bg-slate-100">
                        <img src="{{ $thumb }}" alt="" class="w-full h-full object-cover">
                    </div>
                @endif
                <div class="min-w-0">
                    <a href="{{ route('private-events.show', $event) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-mute hover:text-brand transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Event details
                    </a>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-ink mt-2 truncate">{{ $event->title }}</h1>
                    <p class="text-sm text-mute mt-1.5">{{ $remaining }} seats remaining · {{ $sold }}/{{ $capacity }} assigned</p>
                </div>
            </div>
            <a href="{{ route('private-events.invitations.create', $event) }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-brand text-white text-sm font-bold shadow-lg shadow-brand/25 hover:bg-brand-dark hover:-translate-y-0.5 transition-all shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Send invitations
            </a>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm font-semibold px-4 py-3.5">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm font-semibold px-4 py-3.5">{{ session('error') }}</div>
        @endif

        {{-- Mobile-friendly cards + desktop table --}}
        <div class="hidden md:block bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] uppercase tracking-[0.14em] text-mute bg-slate-50/90 border-b border-slate-100">
                        <th class="text-left px-5 py-3.5 font-bold">Guest</th>
                        <th class="text-left px-5 py-3.5 font-bold">Tickets</th>
                        <th class="text-left px-5 py-3.5 font-bold">Delivery</th>
                        <th class="text-left px-5 py-3.5 font-bold">Status</th>
                        <th class="text-left px-5 py-3.5 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invitations as $invite)
                        <tr class="border-t border-slate-50 align-top hover:bg-brand-soft/30 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-ink">{{ $invite->guest_name ?: 'Guest' }}</div>
                                <div class="text-xs text-mute mt-0.5">{{ $invite->guest_phone }}</div>
                                @if($invite->opened_at)
                                    <div class="text-[11px] text-brand mt-1.5 font-semibold">Opened {{ $invite->opened_at->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-ink">{{ $invite->quantity }} × {{ $invite->ticketType?->name }}</div>
                                <a href="{{ $invite->publicUrl() }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-brand mt-1 hover:underline">
                                    Open guest link
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </td>
                            <td class="px-5 py-4 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-mute">SMS</span>
                                    <span class="font-bold text-ink">{{ $invite->sms_status }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-mute">WA</span>
                                    <span class="font-semibold text-ink">{{ $invite->whatsapp_status }}</span>
                                </div>
                                @if($invite->last_sent_at)
                                    <div class="text-mute mt-1.5">{{ $invite->last_sent_at->format('M j, g:ia') }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border
                                    {{ $invite->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100' }}">
                                    {{ ucfirst($invite->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($invite->status === 'active')
                                    <div class="flex flex-col gap-2.5 items-start">
                                        <form method="POST" action="{{ route('private-events.invitations.resend', [$event, $invite]) }}">
                                            @csrf
                                            <button class="text-xs font-bold text-brand hover:underline">Resend</button>
                                        </form>
                                        <form method="POST" action="{{ route('private-events.invitations.phone', [$event, $invite]) }}" class="flex gap-1.5 items-center">
                                            @csrf
                                            <input name="phone" value="{{ $invite->guest_phone }}" class="w-32 rounded-lg border border-slate-200 px-2 py-1.5 text-xs outline-none focus:border-brand">
                                            <button class="text-xs font-bold text-mute hover:text-brand">Update</button>
                                        </form>
                                        <form method="POST" action="{{ route('private-events.invitations.revoke', [$event, $invite]) }}" onsubmit="return confirm('Revoke this invitation?')">
                                            @csrf
                                            <button class="text-xs font-bold text-red-400 hover:text-red-600">Revoke</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-mute">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="mx-auto w-12 h-12 rounded-2xl bg-brand-soft flex items-center justify-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-sm text-mute">No invitations yet.</p>
                                <a href="{{ route('private-events.invitations.create', $event) }}" class="inline-block mt-2 text-sm font-bold text-brand hover:underline">Send the first batch</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-3">
            @forelse($invitations as $invite)
                <article class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-ink truncate">{{ $invite->guest_name ?: 'Guest' }}</p>
                            <p class="text-xs text-mute mt-0.5">{{ $invite->guest_phone }}</p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border shrink-0
                            {{ $invite->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100' }}">
                            {{ ucfirst($invite->status) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold">{{ $invite->quantity }} × {{ $invite->ticketType?->name }}</span>
                        <span class="text-mute">SMS {{ $invite->sms_status }}</span>
                    </div>
                    @if($invite->status === 'active')
                        <div class="flex flex-wrap gap-3 pt-1 border-t border-slate-50">
                            <a href="{{ $invite->publicUrl() }}" target="_blank" class="text-xs font-bold text-brand">Open link</a>
                            <form method="POST" action="{{ route('private-events.invitations.resend', [$event, $invite]) }}">
                                @csrf
                                <button class="text-xs font-bold text-brand">Resend</button>
                            </form>
                            <form method="POST" action="{{ route('private-events.invitations.revoke', [$event, $invite]) }}" onsubmit="return confirm('Revoke this invitation?')">
                                @csrf
                                <button class="text-xs font-bold text-red-400">Revoke</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <div class="bg-white rounded-2xl border border-dashed border-slate-200 p-10 text-center text-sm text-mute">
                    No invitations yet.
                    <a href="{{ route('private-events.invitations.create', $event) }}" class="block mt-2 font-bold text-brand">Send the first batch</a>
                </div>
            @endforelse
        </div>

        @if($invitations->hasPages())
            <div class="mt-6">{{ $invitations->links() }}</div>
        @endif
    </div>
</div>
@endsection
