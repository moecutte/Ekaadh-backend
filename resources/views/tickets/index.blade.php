@extends('layouts.app')

@section('title', __('ui.booked_events'))

@section('content')
@php
    $statusClass = [
        'valid' => 'bg-green-100 text-green-700',
        'used' => 'bg-gray-100 text-gray-500',
        'cancelled' => 'bg-red-100 text-red-600',
    ];
    $otpSendUrl = $otpSendUrl ?? url('/api/v1/otp/send');
    $otpVerifyUrl = $otpVerifyUrl ?? url('/api/v1/otp/verify');
@endphp

@if($accountMode)
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8" x-data="{ expanded: null, labels: { viewQr: @js(__('ui.view_qr')), hideQr: @js(__('ui.hide_qr')) } }">
    @if(session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm font-semibold px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-ink">{{ __('ui.booked_events') }}</h1>
            <p class="text-sm text-mute">{{ auth()->user()->name }} · {{ auth()->user()->phone }}</p>
        </div>
        <span class="text-xs bg-green-50 text-green-700 font-semibold px-2.5 py-1 rounded-full shrink-0">{{ __('ui.signed_in') }}</span>
    </div>

    <div class="space-y-4">
        @forelse($tickets as $ticket)
            @php
                $status = strtolower((string) $ticket->status);
                $badge = $statusClass[$status] ?? 'bg-gray-100 text-gray-500';
                $event = $ticket->event;
                $order = $ticket->orderItem?->order;
                $dateLabel = $event?->event_date?->format('M j, Y');
                $timeLabel = $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null;
            @endphp
            <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                <div class="flex items-center gap-4 p-4">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-200 shrink-0">
                        @if($event?->cover_image)
                            <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-ink text-sm leading-snug truncate">{{ $event?->title }}</p>
                        <p class="text-xs text-mute mt-0.5">
                            {{ $dateLabel }}
                            @if($timeLabel) · {{ $timeLabel }} @endif
                        </p>
                        <p class="text-xs text-mute">{{ $ticket->ticket_type_name }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        <span class="text-xs font-extrabold px-2.5 py-1 rounded-full {{ $badge }}">{{ $status === 'valid' ? __('ui.valid') : ucfirst($ticket->status) }}</span>
                        @if($status === 'valid')
                            <button
                                type="button"
                                @click="expanded = expanded === {{ $ticket->id }} ? null : {{ $ticket->id }}"
                                class="text-xs font-bold text-brand hover:underline"
                                x-text="expanded === {{ $ticket->id }} ? labels.hideQr : labels.viewQr"
                            >{{ __('ui.view_qr') }}</button>
                        @else
                            <a href="{{ route('tickets.show', $ticket->ticket_code) }}" class="text-xs font-bold text-brand hover:underline">{{ __('ui.view') }}</a>
                        @endif
                    </div>
                </div>

                <div
                    x-show="expanded === {{ $ticket->id }}"
                    x-cloak
                    class="border-t-2 border-dashed border-slate-200 bg-page p-5"
                >
                    <div class="flex flex-col sm:flex-row gap-5 items-start">
                        <div class="shrink-0 text-center mx-auto sm:mx-0">
                            <div class="rounded-xl overflow-hidden border border-slate-200 inline-block bg-white p-1">
                                <img src="{{ $ticket->qr_image }}" alt="QR {{ $ticket->ticket_code }}" class="w-[114px] h-[114px] object-contain">
                            </div>
                            <p class="text-xs text-mute mt-2 font-mono tracking-wide">{{ $ticket->ticket_code }}</p>
                        </div>
                        <div class="flex-1 space-y-3 w-full">
                            <div>
                                <p class="text-xs text-mute">{{ __('ui.event') }}</p>
                                <p class="font-extrabold text-ink text-sm leading-snug">{{ $event?->title }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.date') }}</p>
                                    <p class="font-semibold text-ink text-xs">{{ $dateLabel ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.time') }}</p>
                                    <p class="font-semibold text-ink text-xs">{{ $timeLabel ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.venue') }}</p>
                                    <p class="font-semibold text-ink text-xs">{{ $event?->venue ?: ($event?->city ?: '—') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-mute">{{ __('ui.type') }}</p>
                                    <p class="font-semibold text-ink text-xs">{{ $ticket->ticket_type_name }}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-xs text-mute">{{ __('ui.buyer') }}</p>
                                    <p class="font-semibold text-ink text-xs">{{ $ticket->holder_name ?: ($order?->buyer_name ?: auth()->user()->name) }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <a href="{{ route('tickets.pdf', $ticket->ticket_code) }}" class="inline-flex items-center gap-1.5 bg-brand text-white text-xs font-bold px-3.5 py-2 rounded-lg hover:bg-brand-dark transition-colors">
                                    {{ __('ui.download_pdf') }}
                                </a>
                                <a href="{{ route('tickets.show', $ticket->ticket_code) }}" class="inline-flex items-center gap-1.5 border border-slate-200 text-ink text-xs font-bold px-3.5 py-2 rounded-lg hover:bg-white transition-colors">
                                    {{ __('ui.full_ticket') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-14 bg-white rounded-2xl border border-slate-100 text-mute">
                <p class="font-semibold text-ink mb-1">{{ __('ui.no_tickets_yet') }}</p>
                <p class="text-sm mb-5">{{ __('ui.tickets_empty_hint') }}</p>
                <a href="{{ route('events.index') }}" class="inline-flex rounded-xl bg-brand text-white font-extrabold px-5 py-3 text-sm hover:bg-brand-dark">{{ __('ui.browse_events_cta') }}</a>
            </div>
        @endforelse
    </div>

    @if($tickets->isNotEmpty())
        <div class="mt-8 text-center">
            <a href="{{ route('events.index') }}" class="text-sm text-brand font-bold hover:underline">{{ __('ui.browse_more_events') }}</a>
        </div>
    @endif
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
@else
<div
    class="max-w-3xl mx-auto px-4 sm:px-6 py-10"
    x-data="findTicketsOtp(@js([
        'phone' => preg_replace('/^\+?252/', '', preg_replace('/\s+/', '', (string) $phone)),
        'otpToken' => $otpToken ?? '',
        'sendUrl' => $otpSendUrl,
        'verifyUrl' => $otpVerifyUrl,
        'indexUrl' => route('tickets.index'),
        'i18n' => [
            'pleaseWait' => __('ui.please_wait'),
            'verifyShowTickets' => __('ui.verify_show_tickets'),
            'findTickets' => __('ui.find_tickets'),
            'enterPhoneNumber' => __('ui.enter_phone_number'),
            'couldNotSendCode' => __('ui.could_not_send_code'),
            'codeSent' => __('ui.code_sent'),
            'testingCode' => __('ui.testing_code'),
            'enterConfirmationCode' => __('ui.enter_confirmation_code'),
            'couldNotVerifyCode' => __('ui.could_not_verify_code'),
            'invalidCode' => __('ui.invalid_code'),
        ],
    ]))"
>
    @if(session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm font-semibold px-4 py-3">{{ session('success') }}</div>
    @endif

    <h1 class="text-3xl font-extrabold mb-2">{{ __('ui.booked_events') }}</h1>
    <p class="text-mute mb-8">
        @guest
            <a href="{{ route('customer.login') }}" class="font-bold text-brand hover:underline">{{ __('ui.sign_in') }}</a>
            {{ __('ui.find_tickets_guest_hint') }}
        @else
            {{ __('ui.find_tickets_phone_hint') }}
        @endguest
    </p>

    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm mb-8 space-y-3">
        <label class="block text-sm font-bold text-ink">{{ __('ui.phone_number') }}</label>
        <div class="flex">
            <span class="flex items-center px-3 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-sm text-mute shrink-0">+252</span>
            <input
                type="tel"
                x-model="phoneLocal"
                placeholder="61 234 5678"
                class="flex-1 rounded-r-xl bg-page border border-slate-200 px-4 py-3 font-medium outline-none focus:border-brand"
            >
        </div>

        <div x-show="otpSent" x-cloak class="space-y-2 pt-1">
            <label class="block text-sm font-bold text-ink">{{ __('ui.confirmation_code') }}</label>
            <input
                type="text"
                inputmode="numeric"
                maxlength="6"
                x-model="otpCode"
                placeholder="123456"
                class="w-full rounded-xl bg-page border border-slate-200 px-4 py-3 tracking-[0.35em] text-center font-bold outline-none focus:border-brand"
            >
            <p class="text-xs text-brand font-semibold" x-show="otpHint" x-text="otpHint"></p>
            <button type="button" @click="sendOtp" :disabled="busy" class="text-xs font-bold text-brand hover:underline">{{ __('ui.resend_code') }}</button>
        </div>

        <p x-show="error" x-cloak class="text-sm text-amber-800 font-semibold" x-text="error"></p>
        @if($error)
            <div class="rounded-xl bg-amber-50 border border-amber-100 text-amber-800 text-sm font-semibold px-4 py-3">{{ $error }}</div>
        @endif

        <button
            type="button"
            @click="continueLookup"
            :disabled="busy"
            class="w-full sm:w-auto rounded-xl bg-brand text-white font-extrabold px-6 py-3 hover:bg-brand-dark transition disabled:opacity-60"
        >
            <span x-text="busy ? i18n.pleaseWait : (otpSent ? i18n.verifyShowTickets : i18n.findTickets)"></span>
        </button>
    </div>

    @if($searched && ! $error)
        @forelse($tickets as $ticket)
            <a href="{{ route('tickets.show', $ticket->ticket_code) }}" class="block bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-4 hover:shadow-md transition">
                <div class="relative h-28 bg-slate-200">
                    @if($ticket->event?->cover_image)
                        <img src="{{ $ticket->event->cover_image }}" class="w-full h-full object-cover" alt="">
                        <div class="absolute inset-0 bg-black/40"></div>
                    @endif
                    <div class="absolute bottom-3 left-4 right-4 text-white">
                        <div class="font-extrabold text-sm">{{ $ticket->event?->title }}</div>
                        <div class="text-xs text-white/70">{{ $ticket->event?->event_date?->format('M j, Y') }}</div>
                    </div>
                    <span class="absolute top-3 right-3 text-[10px] font-extrabold px-2.5 py-1 rounded-full {{ $ticket->status === 'valid' ? 'bg-brand text-white' : 'bg-slate-500 text-white' }}">
                        {{ $ticket->status === 'valid' ? __('ui.valid') : ucfirst($ticket->status) }}
                    </span>
                </div>
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-mute">{{ $ticket->ticket_type_name }}</div>
                        <div class="text-sm font-extrabold text-brand font-mono">{{ $ticket->ticket_code }}</div>
                    </div>
                    <span class="text-sm font-bold text-brand">{{ __('ui.view_qr') }} →</span>
                </div>
            </a>
        @empty
            <div class="text-center py-12 bg-white rounded-2xl border border-slate-100 text-mute">
                {{ __('ui.no_tickets_for_phone') }}
            </div>
        @endforelse
    @endif
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
<script>
function findTicketsOtp(cfg) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
    const i18n = cfg.i18n || {};

    return {
        phoneLocal: cfg.phone || '',
        otpSent: !!cfg.otpToken,
        otpCode: '',
        otpHint: '',
        error: '',
        busy: false,
        i18n,
        get fullPhone() {
            const local = String(this.phoneLocal || '').replace(/\D/g, '');
            return local ? '+252' + local : '';
        },
        async sendOtp() {
            if (!this.fullPhone) {
                this.error = i18n.enterPhoneNumber;
                return false;
            }
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(cfg.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ phone: this.fullPhone, purpose: 'find_tickets' }),
                });
                const text = await res.text();
                let body = {};
                try { body = text ? JSON.parse(text) : {}; } catch (_) {
                    this.error = i18n.couldNotSendCode + ' (' + res.status + ')';
                    return false;
                }
                if (!res.ok) {
                    this.error = body.errors?.phone?.[0] || body.message || i18n.couldNotSendCode;
                    return false;
                }
                this.otpSent = true;
                this.otpHint = body.debug_code
                    ? i18n.testingCode.replace(':code', body.debug_code)
                    : (body.message || i18n.codeSent);
                return true;
            } catch (e) {
                this.error = e.message || i18n.couldNotSendCode;
                return false;
            } finally {
                this.busy = false;
            }
        },
        async continueLookup() {
            if (!this.otpSent) {
                await this.sendOtp();
                return;
            }
            if (!String(this.otpCode || '').trim()) {
                this.error = i18n.enterConfirmationCode;
                return;
            }
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(cfg.verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        phone: this.fullPhone,
                        purpose: 'find_tickets',
                        otp: String(this.otpCode).trim(),
                    }),
                });
                const text = await res.text();
                let body = {};
                try { body = text ? JSON.parse(text) : {}; } catch (_) {
                    this.error = i18n.couldNotVerifyCode + ' (' + res.status + ')';
                    return;
                }
                if (!res.ok) {
                    this.error = body.errors?.otp?.[0] || body.message || i18n.invalidCode;
                    return;
                }
                const url = new URL(cfg.indexUrl, window.location.origin);
                url.searchParams.set('phone', body.phone || this.fullPhone);
                url.searchParams.set('otp_token', body.otp_token);
                window.location.href = url.toString();
            } catch (e) {
                this.error = e.message || i18n.couldNotVerifyCode;
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endif
@endsection
