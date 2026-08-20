@extends('layouts.admin')
@section('title', 'Invitees')
@section('heading', 'Invitees')

@section('content')
@php
    $selectedId = $event?->id;
    $statusColors = [
        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'revoked' => 'bg-red-50 text-red-600 border-red-100',
        'sent' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'failed' => 'bg-red-50 text-red-600 border-red-100',
        'skipped' => 'bg-slate-50 text-mute border-slate-100',
        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
    ];
@endphp

<p class="text-sm text-mute mb-5 max-w-2xl">
    People who received a complimentary invitation are <span class="font-semibold text-ink">invitees</span>, not ticket buyers.
    That includes private events and complimentary guests on public organizer events. Pick an event on the left to see who was invited.
</p>

<div class="grid lg:grid-cols-12 gap-5 items-start">
    <aside class="lg:col-span-4 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <form method="GET" class="p-4 border-b border-slate-50">
            @if($selectedId)
                <input type="hidden" name="event" value="{{ $selectedId }}">
            @endif
            <label class="text-[11px] font-bold uppercase text-mute block mb-1.5">Invitations</label>
            <input name="event_q" value="{{ $eventSearch }}" placeholder="Search title, host, city…"
                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-brand">
        </form>
        <div class="max-h-[70vh] overflow-y-auto divide-y divide-slate-50">
            @forelse($events as $row)
                <a href="{{ route('admin.invitees.index', array_filter(['event' => $row->id, 'event_q' => $eventSearch ?: null])) }}"
                   class="block px-4 py-3.5 transition-colors {{ $selectedId === $row->id ? 'bg-brand-soft' : 'hover:bg-slate-50' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-ink truncate">{{ $row->title }}</p>
                            <p class="text-[11px] text-mute mt-0.5 truncate">
                                {{ $row->inviteHostName() }}
                                @if(! $row->is_private)
                                    · Public
                                @endif
                                @if($row->event_date)
                                    · {{ $row->event_date->format('M j, Y') }}
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-[11px] font-extrabold px-2 py-0.5 rounded-full border {{ $selectedId === $row->id ? 'bg-brand text-white border-brand' : 'bg-slate-50 text-mute border-slate-100' }}">
                            {{ number_format($row->invitees_count) }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="px-4 py-10 text-center text-sm text-mute">No invitations yet.</div>
            @endforelse
        </div>
        @if($events->hasPages())
            <div class="border-t border-slate-50">
                @include('admin.partials.pager', ['paginator' => $events, 'simple' => true])
            </div>
        @endif
    </aside>

    <section class="lg:col-span-8 min-w-0">
        @if(! $event)
            <div class="bg-white rounded-2xl border border-dashed border-slate-200 shadow-sm px-6 py-16 text-center">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-soft flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h2 class="text-lg font-extrabold text-ink">Select an invitation</h2>
                <p class="text-sm text-mute mt-2 max-w-md mx-auto">Invitees stay hidden until you choose which event they belong to. That keeps them off the buyer list and grouped by event.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-brand mb-1">Invitation</p>
                        <h2 class="text-xl font-extrabold text-ink truncate">{{ $event->title }}</h2>
                        <p class="text-sm text-mute mt-1">
                            {{ $event->is_private ? 'Host' : 'Organizer' }}: <span class="font-semibold text-ink">{{ $event->inviteHostName() }}</span>
                            @if($event->is_private && $event->owner?->phone)
                                · {{ $event->owner->phone }}
                            @elseif(! $event->is_private && $event->organizer?->user?->phone)
                                · {{ $event->organizer->user->phone }}
                            @endif
                            @if($event->event_date)
                                · {{ $event->event_date->format('M j, Y') }}
                            @endif
                            @if($event->city)
                                · {{ $event->city }}
                            @endif
                            @if(! $event->is_private)
                                · Public event
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('admin.events.index', ['type' => $event->is_private ? 'private' : 'public', 'q' => $event->title]) }}" class="text-xs font-bold text-brand hover:underline shrink-0">View event</a>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
                <div class="bg-white rounded-2xl border border-slate-100 p-3.5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase text-mute">Invitees</div>
                    <div class="text-xl font-black mt-0.5">{{ number_format($stats['invitees']) }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-3.5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase text-mute">Active</div>
                    <div class="text-xl font-black mt-0.5 text-emerald-700">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-3.5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase text-mute">Opened</div>
                    <div class="text-xl font-black mt-0.5 text-brand">{{ number_format($stats['opened']) }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-3.5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase text-mute">Seats issued</div>
                    <div class="text-xl font-black mt-0.5">{{ number_format($stats['seats']) }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-3.5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase text-mute">Failed send</div>
                    <div class="text-xl font-black mt-0.5 {{ $stats['failed'] ? 'text-red-600' : '' }}">{{ number_format($stats['failed']) }}</div>
                </div>
            </div>

            <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-4">
                <input type="hidden" name="event" value="{{ $event->id }}">
                @if($eventSearch !== '')
                    <input type="hidden" name="event_q" value="{{ $eventSearch }}">
                @endif
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Find invitee</label>
                        <input name="q" value="{{ $search }}" placeholder="Name or phone…"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-brand">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Status</label>
                        <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                            <option value="">All</option>
                            <option value="active" @selected($status === 'active')>Active</option>
                            <option value="revoked" @selected($status === 'revoked')>Revoked</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Opened</label>
                        <select name="opened" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                            <option value="">All</option>
                            <option value="yes" @selected($opened === 'yes')>Opened</option>
                            <option value="no" @selected($opened === 'no')>Not opened</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Channel</label>
                        <select name="channel" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                            <option value="">All</option>
                            <option value="whatsapp" @selected($channel === 'whatsapp')>WhatsApp</option>
                            <option value="sms" @selected($channel === 'sms')>SMS</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button class="px-4 py-2 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
                    <a href="{{ route('admin.invitees.index', ['event' => $event->id, 'event_q' => $eventSearch ?: null]) }}" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-mute hover:text-ink">Reset</a>
                </div>
            </form>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="text-[11px] uppercase text-mute bg-slate-50/80">
                        <tr>
                            <th class="text-left px-4 py-3">Invitee</th>
                            <th class="text-left px-4 py-3">Seats</th>
                            <th class="text-left px-4 py-3">Sent via</th>
                            <th class="text-left px-4 py-3">Opened</th>
                            <th class="text-left px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invitees as $invite)
                            <tr class="border-t border-slate-50 align-top">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-ink">{{ $invite->guest_name ?: 'Guest' }}</div>
                                    <div class="text-xs text-mute mt-0.5">{{ $invite->guest_phone }}</div>
                                    <a href="{{ $invite->publicUrl() }}" target="_blank" class="text-[11px] font-bold text-brand hover:underline mt-1 inline-block">Open invite</a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $invite->quantity }} × {{ $invite->ticketType?->name ?: 'Ticket' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xs font-bold text-ink">{{ $invite->channelLabel() }}</div>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border mt-1 inline-block {{ $statusColors[$invite->deliveryStatus()] ?? 'bg-slate-50 text-mute border-slate-100' }}">
                                        {{ $invite->deliveryStatus() }}
                                    </span>
                                    @if($invite->last_sent_at)
                                        <div class="text-[11px] text-mute mt-1">{{ $invite->last_sent_at->format('M j, g:ia') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if($invite->wasOpened())
                                        <span class="font-bold text-brand">Yes</span>
                                        <div class="text-mute mt-0.5">{{ $invite->opened_at->diffForHumans() }}</div>
                                    @else
                                        <span class="text-mute">Not yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $statusColors[$invite->status] ?? 'bg-slate-50 text-mute border-slate-100' }}">
                                        {{ $invite->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-sm text-mute">
                                    No invitees for this invitation yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($invitees)
                    @include('admin.partials.pager', ['paginator' => $invitees])
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
