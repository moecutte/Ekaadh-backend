@extends('layouts.app')

@section('title', $event->title)

@php
    $shareUrl = route('events.show', $event->slug);
    $shareText = __('ui.share_event_text', ['title' => $event->title]);
    $shareDesc = \Illuminate\Support\Str::limit(strip_tags((string) $event->description), 160);
@endphp

@push('head')
    <meta name="description" content="{{ $shareDesc }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Ekaadh">
    <meta property="og:title" content="{{ $event->title }}">
    <meta property="og:description" content="{{ $shareDesc }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    @if($event->cover_image)
        <meta property="og:image" content="{{ $event->cover_image }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $event->title }}">
    <meta name="twitter:description" content="{{ $shareDesc }}">
    @if($event->cover_image)
        <meta name="twitter:image" content="{{ $event->cover_image }}">
    @endif
@endpush

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
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white mt-2 mb-3 leading-tight">{{ $event->title }}</h1>
        @if($event->isExpired())
            <span class="inline-flex text-[11px] font-extrabold uppercase tracking-wide px-2.5 py-1 rounded-full bg-white/20 text-white mb-3">{{ __('ui.expired') }}</span>
        @endif
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
            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.about_this_event') }}</h2>
                <div class="text-mute leading-relaxed text-sm whitespace-pre-line">{{ $event->description }}</div>
            </div>

            @if($event->speakers->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.speakers_guests') }}</h2>
                <div class="space-y-4">
                    @foreach($event->speakers as $speaker)
                        <div class="flex items-start gap-3">
                            @if($speaker->photo)
                                <img src="{{ $speaker->photo }}" alt="{{ $speaker->name }}" class="w-14 h-14 rounded-full object-cover shrink-0">
                            @else
                                <div class="w-14 h-14 rounded-full bg-brand/10 text-brand font-extrabold flex items-center justify-center shrink-0 text-sm">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($speaker->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-bold text-ink">{{ $speaker->name }}</p>
                                @if($speaker->role)
                                    <p class="text-xs font-semibold text-brand mt-0.5">{{ $speaker->role }}</p>
                                @endif
                                @if($speaker->bio)
                                    <p class="text-sm text-mute mt-1 leading-relaxed">{{ $speaker->bio }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($event->programmeItems->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.event_programme') }}</h2>
                <ol class="space-y-3">
                    @foreach($event->programmeItems as $item)
                        <li class="flex gap-3">
                            <div class="w-20 sm:w-28 shrink-0 text-[11px] sm:text-xs font-extrabold text-brand pt-0.5">{{ $item->timeRangeLabel() }}</div>
                            <div class="min-w-0">
                                <p class="font-bold text-ink text-sm">{{ $item->title }}</p>
                                @if($item->description)
                                    <p class="text-sm text-mute mt-0.5">{{ $item->description }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
            @endif

            @if($event->galleryImages->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6" x-data="{ open: null }">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.event_gallery') }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach($event->galleryImages as $image)
                        <button type="button" @click="open = @js($image->path)" class="aspect-square rounded-xl overflow-hidden bg-slate-100">
                            <img src="{{ $image->path }}" alt="{{ $event->title }}" class="w-full h-full object-cover hover:scale-105 transition">
                        </button>
                    @endforeach
                </div>
                <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4" @click="open = null" @keydown.escape.window="open = null">
                    <img :src="open" alt="" class="max-h-[90vh] max-w-full rounded-xl shadow-2xl" @click.stop>
                </div>
            </div>
            @endif

            @if($event->organizer)
            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6">
                <h2 class="text-xl font-extrabold text-ink mb-4">{{ __('ui.organizer') }}</h2>
                <div class="flex items-center gap-3">
                    @include('partials.avatar', [
                        'url' => $event->organizer->avatarUrl(),
                        'label' => $event->organizer->business_name,
                        'initials' => $event->organizer->avatarInitials(),
                        'class' => 'w-12 h-12',
                        'rounded' => 'rounded-xl',
                        'text' => 'text-base',
                    ])
                    <div>
                        <p class="font-bold text-ink">{{ $event->organizer->business_name }}</p>
                        <p class="text-sm text-mute">{{ __('ui.verified_organizer') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6">
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
            <div class="bg-white rounded-2xl border border-slate-100 shadow-lg p-4 sm:p-5 lg:sticky lg:top-24">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs text-mute">{{ __('ui.starting_from') }}</p>
                    @if($event->isExpired())
                        <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full">{{ __('ui.expired') }}</span>
                    @else
                        <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">{{ __('ui.tickets_available') }}</span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-ink mb-5">
                    @if($event->isFreeEvent() || (float) ($starting ?? 0) === 0.0)
                        {{ __('ui.free') }}
                    @else
                        ${{ number_format((float) ($starting ?? 0), 0) }}
                        <span class="text-sm font-normal text-mute">{{ __('ui.per_ticket') }}</span>
                    @endif
                </p>

                @if($event->isExpired())
                    <p class="text-sm text-mute mb-4">{{ __('ui.event_expired_hint') }}</p>
                    <span class="w-full block text-center rounded-xl font-bold py-3.5 text-sm bg-slate-100 text-mute">{{ __('ui.expired') }}</span>
                @else
                <div class="space-y-3 mb-4">
                    @foreach($event->ticketTypes as $type)
                        @php $max = min(20, $type->remaining()); @endphp
                        <div class="p-3 border border-slate-100 rounded-xl">
                            <div class="flex items-center justify-between mb-1.5 gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-ink">{{ $type->name }}</p>
                                    <p class="text-brand font-extrabold text-sm">{{ $event->isFreeEvent() || (float) $type->price === 0.0 ? __('ui.free') : '$'.number_format((float) $type->price, 0) }}</p>
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
                    {{ $event->isFreeEvent() ? __('ui.claim_free_tickets') : __('ui.secure_checkout') }}
                </div>
                @endif

                <div
                    class="mt-5 pt-5 border-t border-slate-100"
                    x-data='eventShare(@json($shareUrl), @json($shareText), @json(__("ui.link_copied")))'
                >
                    <p class="text-xs font-bold text-mute mb-3">{{ __('ui.share_event') }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="nativeShare()"
                            class="col-span-2 inline-flex items-center justify-center gap-2 rounded-xl bg-ink text-white text-sm font-bold py-2.5 hover:bg-ink/90 transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                            {{ __('ui.share') }}
                        </button>
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 text-sm font-bold py-2.5 text-ink hover:bg-page transition"
                        >{{ __('ui.share_whatsapp') }}</a>
                        <a
                            :href="facebookUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 text-sm font-bold py-2.5 text-ink hover:bg-page transition"
                        >{{ __('ui.share_facebook') }}</a>
                        <a
                            :href="xUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 text-sm font-bold py-2.5 text-ink hover:bg-page transition"
                        >{{ __('ui.share_x') }}</a>
                        <button
                            type="button"
                            @click="copyLink()"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 text-sm font-bold py-2.5 text-ink hover:bg-page transition"
                            x-text="copied ? copiedLabel : copyLabel"
                        ></button>
                    </div>
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
        <p class="text-xs text-mute">{{ $event->isFreeEvent() ? __('ui.free') : __('ui.from') }}</p>
        <p class="font-extrabold text-ink">{{ $event->isFreeEvent() || (float) ($starting ?? 0) === 0.0 ? __('ui.free') : '$'.number_format((float) ($starting ?? 0), 0) }}</p>
    </div>
    <a href="{{ route('checkout.show', $event->slug) }}" class="flex-1 bg-brand text-white font-bold py-3 rounded-xl text-sm text-center">
        {{ __('ui.get_tickets') }}
    </a>
</div>
<div class="h-20 lg:hidden"></div>

<style>[x-cloak] { display: none !important; }</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function eventShare(url, text, copiedLabel) {
    return {
        url,
        text,
        copied: false,
        copiedLabel,
        copyLabel: @json(__('ui.copy_link')),
        get whatsappUrl() {
            return 'https://wa.me/?text=' + encodeURIComponent(this.text + ' ' + this.url);
        },
        get facebookUrl() {
            return 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(this.url);
        },
        get xUrl() {
            return 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(this.text) + '&url=' + encodeURIComponent(this.url);
        },
        async nativeShare() {
            if (navigator.share) {
                try {
                    await navigator.share({ title: this.text, text: this.text, url: this.url });
                    return;
                } catch (e) {
                    if (e && e.name === 'AbortError') return;
                }
            }
            this.copyLink();
        },
        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.url);
            } catch (e) {
                const input = document.createElement('input');
                input.value = this.url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
            }
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        },
    };
}
function eventTickets() {
    const prices = {
        @foreach($event->ticketTypes as $type)
            {{ $type->id }}: {{ (float) $type->price }},
        @endforeach
    };
    const base = @json(route('checkout.show', $event->slug));
    const i18n = {
        selectTickets: @json(__('ui.select_tickets_continue')),
        claimFreeTickets: @json(__('ui.claim_free_tickets')),
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
            @if($event->isFreeEvent())
            return i18n.claimFreeTickets;
            @else
            return i18n.proceedCheckout.replace(':amount', this.subtotal.toFixed(0));
            @endif
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
