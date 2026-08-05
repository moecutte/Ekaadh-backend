@extends('layouts.app')

@section('title', $event->title)

@section('content')
@php
    $starting = $event->ticketTypes->min('price');
    $catBadge = [
        'Music' => 'bg-purple-100 text-purple-700',
        'Sports' => 'bg-green-100 text-green-700',
        'Comedy' => 'bg-pink-100 text-pink-700',
        'Tech' => 'bg-sky-100 text-sky-700',
        'Food' => 'bg-orange-100 text-orange-700',
        'Business' => 'bg-indigo-100 text-indigo-700',
        'Culture' => 'bg-amber-100 text-amber-700',
        'Education' => 'bg-teal-100 text-teal-700',
    ];
@endphp

{{-- Full-bleed hero --}}
<div class="relative h-72 sm:h-96 bg-[#0a1220]">
    @if($event->cover_image)
        <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="w-full h-full object-cover opacity-80">
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 p-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($event->category)
            <span class="inline-flex text-[11px] font-bold px-2.5 py-1 rounded-full {{ $catBadge[$event->category] ?? 'bg-white/90 text-ink' }}">{{ $event->category }}</span>
        @endif
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white mt-2 mb-3 leading-tight">{{ $event->title }}</h1>
        <div class="flex flex-wrap gap-4 text-white/80 text-sm">
            <span class="flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ $event->event_date?->format('M j, Y') }}@if($event->event_time) {{ __('ui.at_time') }} {{ date('g:i A', strtotime($event->event_time)) }}@endif
            </span>
            <span class="flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ $event->venue }}
            </span>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-mute hover:text-brand mb-6">&larr; {{ __('ui.back_to_events') }}</a>

    <div class="grid lg:grid-cols-5 gap-8">
        <div class="lg:col-span-3 space-y-5">
            <div class="bg-white rounded-2xl border border-slate-100 p-6">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.about_this_event') }}</h2>
                <div class="text-mute leading-relaxed text-sm whitespace-pre-line">{{ $event->description }}</div>
            </div>

            @if($event->organizer)
            <div class="bg-white rounded-2xl border border-slate-100 p-6">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.organizer') }}</h2>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center shrink-0 text-brand font-black">
                        {{ substr($event->organizer->business_name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-bold text-ink">{{ $event->organizer->business_name }}</p>
                        <p class="text-sm text-mute">{{ __('ui.verified_organizer') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-100 p-6">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.venue') }}</h2>
                <div class="flex items-start gap-3 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <div>
                        <p class="font-bold text-ink">{{ $event->venue }}</p>
                        <p class="text-sm text-mute">{{ $event->city }}{{ $event->address ? ' · '.$event->address : '' }}</p>
                    </div>
                </div>
                <div class="h-48 bg-slate-100 rounded-xl flex items-center justify-center border border-slate-100">
                    <div class="text-center text-mute">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm font-medium">{{ __('ui.map_view') }}</p>
                        <p class="text-xs">{{ $event->venue }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2" x-data="eventTickets()">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-lg p-5 sticky top-24">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs text-mute">{{ __('ui.starting_from') }}</p>
                    <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">{{ __('ui.tickets_available') }}</span>
                </div>
                <p class="text-3xl font-extrabold text-ink mb-5">
                    ${{ number_format((float) ($starting ?? 0), 0) }}
                    <span class="text-sm font-normal text-mute">{{ __('ui.per_ticket') }}</span>
                </p>

                <div class="space-y-3 mb-4">
                    @foreach($event->ticketTypes as $type)
                        @php $max = min(20, $type->remaining()); @endphp
                        <div class="p-3 border border-slate-100 rounded-xl">
                            <div class="flex items-center justify-between mb-1.5 gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-ink">{{ $type->name }}</p>
                                    <p class="text-brand font-extrabold text-sm">${{ number_format((float) $type->price, 0) }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" @click="dec({{ $type->id }})" class="w-7 h-7 rounded-lg border border-slate-200 flex items-center justify-center text-ink hover:bg-page text-base font-bold leading-none">−</button>
                                    <span class="w-5 text-center text-sm font-bold" x-text="qty[{{ $type->id }}] || 0"></span>
                                    <button type="button" @click="inc({{ $type->id }}, {{ $max }})" class="w-7 h-7 rounded-lg bg-brand hover:bg-brand-dark text-white flex items-center justify-center text-base font-bold leading-none">+</button>
                                </div>
                            </div>
                            <p class="text-xs text-mute">{{ __('ui.remaining', ['count' => $type->remaining()]) }}</p>
                        </div>
                    @endforeach
                </div>

                <div x-show="ticketCount > 0" x-cloak class="flex items-center justify-between py-3 border-t border-slate-100 mb-4">
                    <span class="text-sm font-bold text-ink" x-text="subtotalLabel"></span>
                    <span class="font-extrabold text-ink" x-text="'$' + subtotal.toFixed(0)"></span>
                </div>

                <a
                    :href="checkoutUrl"
                    :class="ticketCount === 0 ? 'pointer-events-none bg-slate-100 text-mute' : 'bg-brand hover:bg-brand-dark text-white shadow-sm shadow-brand/20'"
                    class="w-full block text-center rounded-xl font-bold py-3.5 text-sm transition"
                    x-text="checkoutLabel"
                ></a>

                <div class="flex items-center justify-center gap-1.5 mt-3 text-xs text-mute">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    {{ __('ui.secure_checkout') }}
                </div>
            </div>
        </div>
    </div>

    @if($related->isNotEmpty())
        <section class="mt-16">
            <h2 class="text-xl font-extrabold mb-5">{{ __('ui.you_might_like') }}</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($related as $item)
                    @include('events._card', ['event' => $item])
                @endforeach
            </div>
        </section>
    @endif
</div>

{{-- Mobile CTA --}}
<div class="fixed bottom-0 left-0 right-0 lg:hidden bg-white border-t border-slate-100 px-4 py-3 flex items-center gap-3 shadow-xl z-40" x-data="eventTickets()">
    <div class="shrink-0">
        <p class="text-xs text-mute">{{ __('ui.from') }}</p>
        <p class="font-extrabold text-ink">${{ number_format((float) ($starting ?? 0), 0) }}</p>
    </div>
    <a href="{{ route('checkout.show', $event->slug) }}" class="flex-1 bg-brand text-white font-bold py-3 rounded-xl text-sm text-center">
        {{ __('ui.get_tickets') }}
    </a>
</div>
<div class="h-20 lg:hidden"></div>

<style>[x-cloak] { display: none !important; }</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function eventTickets() {
    const prices = {
        @foreach($event->ticketTypes as $type)
            {{ $type->id }}: {{ (float) $type->price }},
        @endforeach
    };
    const base = @json(route('checkout.show', $event->slug));
    const i18n = {
        selectTickets: @json(__('ui.select_tickets_continue')),
        proceedCheckout: @json(__('ui.proceed_checkout')),
        subtotalTicket: @json(__('ui.subtotal_ticket')),
        subtotalTickets: @json(__('ui.subtotal_tickets')),
    };
    return {
        qty: {
            @foreach($event->ticketTypes as $type)
                {{ $type->id }}: 0,
            @endforeach
        },
        get ticketCount() {
            return Object.values(this.qty).reduce((a, b) => a + (Number(b) || 0), 0);
        },
        get subtotal() {
            return Object.entries(this.qty).reduce((sum, [id, q]) => sum + (prices[id] || 0) * (Number(q) || 0), 0);
        },
        get checkoutUrl() {
            const params = new URLSearchParams();
            Object.entries(this.qty).forEach(([id, q]) => {
                if (Number(q) > 0) params.append('qty[' + id + ']', String(q));
            });
            const qs = params.toString();
            return qs ? base + '?' + qs : base;
        },
        get subtotalLabel() {
            const tpl = this.ticketCount === 1 ? i18n.subtotalTicket : i18n.subtotalTickets;
            return tpl.replace(':count', String(this.ticketCount));
        },
        get checkoutLabel() {
            if (this.ticketCount === 0) return i18n.selectTickets;
            return i18n.proceedCheckout.replace(':amount', this.subtotal.toFixed(0));
        },
        inc(id, max) {
            if ((this.qty[id] || 0) < max) this.qty[id] = (this.qty[id] || 0) + 1;
        },
        dec(id) {
            if ((this.qty[id] || 0) > 0) this.qty[id]--;
        },
    };
}
</script>
@endsection
