@extends('layouts.app')

@section('title', __('ui.payment_successful'))

@section('content')
@php
    $event = $order->event;
    $timeLabel = $event->event_time ? date('g:i A', strtotime($event->event_time)) : null;
    $ticketCount = $order->items->sum('quantity');
    $ticketSummary = $order->items->map(fn ($i) => $i->quantity.'× '.($i->ticketType->name ?? 'Ticket'))->implode(', ');
    $paymentMethod = $order->payment_method;
    $paymentMethodLabel = match ($paymentMethod) {
        'waafipay' => 'WaafiPay',
        'edahab' => 'eDahab',
        'zaad' => 'Zaad',
        default => $paymentMethod,
    };
    $ticketsUrl = auth()->check() && auth()->user()->isCustomer()
        ? route('tickets.index')
        : route('tickets.index', ['phone' => $order->buyer_phone]);
@endphp

<div class="max-w-xl mx-auto px-4 sm:px-6 py-12">
    <div class="text-center mb-8">
        <div class="relative inline-flex mb-5">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="absolute inset-0 rounded-full bg-green-400/20 animate-ping"></div>
        </div>
        <h1 class="text-3xl font-extrabold text-ink mb-2">{{ __('ui.payment_successful') }}</h1>
        <p class="text-mute text-sm">
            {{ __('ui.order_reference') }}:
            <span class="font-extrabold text-ink">{{ $order->order_number }}</span>
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 p-5 mb-4">
        <h2 class="font-extrabold text-ink text-base mb-4">{{ __('ui.order_details') }}</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <span class="text-mute shrink-0">{{ __('ui.event') }}</span>
                <span class="font-semibold text-ink text-right">{{ $event->title }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-mute shrink-0">{{ __('ui.date') }}</span>
                <span class="font-semibold text-ink text-right">
                    {{ $event->event_date?->format('M j, Y') }}@if($timeLabel) · {{ $timeLabel }}@endif
                </span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-mute shrink-0">{{ __('ui.venue') }}</span>
                <span class="font-semibold text-ink text-right">{{ $event->venue }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-mute shrink-0">{{ __('ui.tickets') }}</span>
                <span class="font-semibold text-ink text-right">{{ $ticketSummary ?: $ticketCount }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-mute shrink-0">{{ __('ui.total_paid') }}</span>
                <span class="font-extrabold text-ink text-right">${{ number_format((float) $order->total, 0) }}</span>
            </div>
            @if($paymentMethod)
            <div class="flex justify-between gap-4">
                <span class="text-mute shrink-0">{{ __('ui.payment_method') }}</span>
                <span class="font-semibold text-green-600 text-right">{{ $paymentMethodLabel }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-brand/5 border border-brand/20 rounded-2xl p-4 mb-5 flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm text-ink leading-relaxed">
            {{ __('ui.ticket_sent_note') }}
        </p>
    </div>

    <div class="flex gap-3 mb-8">
        <a href="{{ $ticketsUrl }}" class="flex-1 bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-xl transition-colors text-sm text-center">
            {{ __('ui.view_my_tickets') }}
        </a>
        <a href="{{ route('home') }}" class="flex-1 border border-slate-200 text-ink font-bold py-3.5 rounded-xl hover:bg-page transition-colors text-sm text-center">
            {{ __('ui.back_to_home') }}
        </a>
    </div>

    <div>
        <h2 class="text-base font-extrabold text-ink mb-3">
            {{ __('ui.your_tickets') }}
        </h2>
        <div class="space-y-4">
            @foreach($tickets as $ticket)
                <a href="{{ route('tickets.show', $ticket->ticket_code) }}" class="block bg-white rounded-2xl overflow-hidden border-2 border-dashed border-brand/30 shadow-sm hover:border-brand/50 transition-colors">
                    <div class="relative h-28 bg-ink">
                        @if($event->cover_image)
                            <img src="{{ $event->cover_image }}" alt="" class="w-full h-full object-cover opacity-40">
                        @endif
                        <div class="absolute inset-0 flex flex-col justify-end p-4">
                            <p class="text-white font-extrabold text-base leading-snug">{{ $event->title }}</p>
                            <p class="text-white/60 text-xs mt-0.5">
                                {{ $event->event_date?->format('M j, Y') }}
                                @if($timeLabel) · {{ $timeLabel }} @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex">
                        <div class="flex-1 p-5 min-w-0">
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.ticket_type') }}</p>
                                    <p class="font-bold text-ink text-sm">{{ $ticket->ticket_type_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.buyer') }}</p>
                                    <p class="font-bold text-ink text-sm truncate">{{ $ticket->holder_name ?: $order->buyer_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.venue') }}</p>
                                    <p class="font-bold text-ink text-sm">{{ $event->city ?: $event->venue }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.ticket_number') }}</p>
                                    <p class="font-bold text-ink text-sm font-mono">{{ $ticket->ticket_code }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs font-extrabold text-green-600">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                {{ strtoupper($ticket->status === 'valid' ? __('ui.valid') : $ticket->status) }}
                            </div>
                        </div>
                        <div class="w-28 sm:w-32 flex items-center justify-center p-4 border-l-2 border-dashed border-slate-200 shrink-0">
                            <div class="rounded-lg overflow-hidden bg-white">
                                <img src="{{ $ticket->qr_image }}" alt="QR {{ $ticket->ticket_code }}" class="w-24 h-24 object-contain">
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
