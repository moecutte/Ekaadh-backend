@extends('layouts.organizer')
@section('title', 'Guests — '.$event->title)
@section('heading', 'Complimentary guests')
@section('actions')
    @if($event->status === 'published' && $guestSlots > 0)
        <a href="{{ route('organizer.events.invitations.create', $event) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand text-white text-sm font-bold rounded-xl hover:bg-brand-dark">+ Send invitations</a>
    @endif
@endsection

@section('content')
@php
    $capacity = $event->ticketTypes->sum('quantity_available');
    $sold = $event->ticketTypes->sum('quantity_sold');
    $queued = count($pending);
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <a href="{{ route('organizer.events.index') }}" class="text-sm font-bold text-mute hover:text-brand">&larr; My Events</a>
        <h2 class="text-lg font-extrabold mt-1">{{ $event->title }}</h2>
        <p class="text-xs text-mute mt-1">{{ $remaining }} seats left · {{ $sold }}/{{ $capacity }} issued (sales + comps)</p>
        <p class="text-xs text-mute mt-1">Complimentary guests: {{ $guestUsed }}/{{ $guestLimit }}. They use the same ticket capacity as public sales.</p>
        @if($guestSlots < 1 && $event->status === 'published')
            <p class="text-xs text-amber-700 mt-1">Guest limit reached. Revoke an invitation to send another.</p>
        @endif
    </div>
</div>

@if($queued > 0)
    <div class="mb-5 rounded-2xl border border-amber-100 bg-amber-50 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-ink">{{ $queued }} invitation(s) queued</p>
                <p class="text-xs text-mute mt-1">
                    @if($event->status === 'published')
                        These guests were added when you created the event but were not sent yet.
                    @else
                        They will send automatically after an admin publishes this event.
                    @endif
                </p>
                <ul class="mt-2 text-xs text-ink space-y-0.5">
                    @foreach($pending as $row)
                        <li>{{ $row['name'] ?: 'Guest' }} · {{ $row['phone'] }} · {{ $row['quantity'] ?? 1 }} × {{ $row['ticket_name'] ?: 'ticket' }}</li>
                    @endforeach
                </ul>
            </div>
            @if($event->status === 'published')
                <form method="POST" action="{{ route('organizer.events.invitations.flush', $event) }}">
                    @csrf
                    <button class="px-4 py-2 rounded-xl bg-brand text-white text-xs font-bold">Send queued now</button>
                </form>
            @endif
        </div>
    </div>
@endif

<div class="hidden md:block bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-[11px] uppercase tracking-wider text-mute bg-slate-50/80">
            <tr>
                <th class="text-left px-4 py-3 font-bold">Guest</th>
                <th class="text-left px-4 py-3 font-bold">Tickets</th>
                <th class="text-left px-4 py-3 font-bold">Delivery</th>
                <th class="text-left px-4 py-3 font-bold">Status</th>
                <th class="text-left px-4 py-3 font-bold">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invitations as $invite)
                <tr class="border-t border-slate-50 align-top">
                    <td class="px-4 py-4">
                        <div class="font-bold">{{ $invite->guest_name ?: 'Guest' }}</div>
                        <div class="text-xs text-mute">{{ $invite->guest_phone }}</div>
                        @if($invite->opened_at)
                            <div class="text-[11px] text-brand mt-1 font-semibold">Opened {{ $invite->opened_at->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <div class="font-semibold">{{ $invite->quantity }} × {{ $invite->ticketType?->name }}</div>
                        <a href="{{ $invite->publicUrl() }}" target="_blank" class="text-[11px] font-bold text-brand">Open guest link</a>
                    </td>
                    <td class="px-4 py-4 text-xs">
                        <div class="font-bold">{{ $invite->channelLabel() }}</div>
                        <div class="text-mute">{{ $invite->deliveryStatus() }}</div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border {{ $invite->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100' }}">
                            {{ $invite->status === 'active' ? 'Active' : 'Revoked' }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        @if($invite->status === 'active')
                            <div class="flex flex-col gap-1.5 items-start">
                                <form method="POST" action="{{ route('organizer.events.invitations.resend', [$event, $invite]) }}">
                                    @csrf
                                    <input type="hidden" name="channel" value="whatsapp">
                                    <button class="text-xs font-bold text-brand">Resend WhatsApp</button>
                                </form>
                                <form method="POST" action="{{ route('organizer.events.invitations.resend', [$event, $invite]) }}">
                                    @csrf
                                    <input type="hidden" name="channel" value="sms">
                                    <button class="text-xs font-bold text-brand">Resend SMS</button>
                                </form>
                                <form method="POST" action="{{ route('organizer.events.invitations.revoke', [$event, $invite]) }}" onsubmit="return confirm('Revoke this invitation and free the seats?')">
                                    @csrf
                                    <button class="text-xs font-bold text-red-400">Revoke</button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-mute">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-mute">
                        No invitations sent yet.
                        @if($event->status === 'published' && $guestSlots > 0)
                            <a href="{{ route('organizer.events.invitations.create', $event) }}" class="text-brand font-bold">Send the first batch</a>
                        @elseif($event->status !== 'published')
                            Add guests on the event form — they send after the event is published.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $invitations->links() }}</div>
@endsection
