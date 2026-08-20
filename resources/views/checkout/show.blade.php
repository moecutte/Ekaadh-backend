@extends('layouts.app')

@section('title', __('ui.checkout').' — '.$event->title)

@section('content')
@php
    $oldQty = old('qty', request()->query('qty', []));
    $defaultName = $customer?->name ?? '';
    $defaultEmail = $customer?->email ?? '';
    if ($defaultEmail && str_ends_with(strtolower($defaultEmail), '@ekaadh.local')) {
        $defaultEmail = '';
    }
    $defaultPhone = $customer?->phone ?? '+252';
    $oldPhone = old('buyer_phone', $defaultPhone);
    $phoneLocal = preg_replace('/^\+?252/', '', preg_replace('/\s+/', '', (string) $oldPhone));
    $timeLabel = $event->event_time ? date('g:i A', strtotime($event->event_time)) : null;
    $signedIn = (bool) $customer;
    $waafiSandbox = (bool) ($waafiSandbox ?? false);
    $lockPhone = $signedIn && ! $waafiSandbox;
    $eventExpired = $event->isExpired();
@endphp

@if($eventExpired)
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-16 text-center">
    <h1 class="text-2xl font-extrabold text-ink mb-2">{{ $event->title }}</h1>
    <p class="text-sm text-mute mb-2">{{ $event->event_date?->format('M j, Y') }}@if($timeLabel) · {{ $timeLabel }}@endif</p>
    <span class="inline-flex text-xs font-extrabold uppercase tracking-wide px-3 py-1 rounded-full bg-slate-100 text-slate-600 mb-4">{{ __('ui.expired') }}</span>
    <p class="text-mute mb-6">{{ __('ui.event_expired_hint') }}</p>
    <a href="{{ route('events.show', $event->slug) }}" class="inline-block rounded-xl bg-brand text-white font-extrabold px-6 py-3">{{ __('ui.back_to_events') }}</a>
</div>
@else
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 min-h-[70vh]" x-data="checkoutWizard()">
    <div class="mb-10">
        <div class="flex items-center">
            <template x-for="(label, i) in stepLabels" :key="label">
                <div class="flex items-center flex-1 last:flex-none">
                    <div class="flex flex-col items-center">
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-sm transition-all"
                            :class="i + 1 < step
                                ? 'bg-brand text-white'
                                : i + 1 === step
                                    ? 'bg-brand text-white ring-4 ring-brand/20'
                                    : 'bg-slate-100 text-mute'"
                        >
                            <template x-if="i + 1 < step">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="i + 1 >= step">
                                <span x-text="i + 1"></span>
                            </template>
                        </div>
                        <span
                            class="text-xs font-semibold mt-1.5 hidden sm:block"
                            :class="i + 1 === step ? 'text-brand' : 'text-mute'"
                            x-text="label"
                        ></span>
                    </div>
                    <template x-if="i < stepLabels.length - 1">
                        <div
                            class="flex-1 h-0.5 mx-2 mb-5"
                            :class="i + 1 < step ? 'bg-brand' : 'bg-slate-100'"
                        ></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store', $event->slug) }}" x-ref="checkoutForm" @submit="prepareSubmit">
        @csrf

        @foreach($event->ticketTypes as $type)
            <input type="hidden" :name="'qty[{{ $type->id }}]'" :value="qty[{{ $type->id }}] || 0">
        @endforeach
        <input type="hidden" name="buyer_phone" :value="chargeFullPhone">
        <input type="hidden" name="otp_phone" :value="otpFullPhone">
        <input type="hidden" name="payment_method" :value="payment || ''">
        <input type="hidden" name="otp_token" :value="otpToken">
        <input type="hidden" name="wallet_pin" :value="walletPin">

        {{-- Step 1: Select Tickets --}}
        <div x-show="step === 1" x-cloak class="space-y-5">
            <h2 class="text-xl sm:text-2xl font-extrabold text-ink">{{ __('ui.order_summary') }}</h2>

            <div class="bg-white rounded-2xl border border-slate-100 p-5">
                <div class="flex gap-4 pb-5 mb-5 border-b border-slate-100">
                    <div class="w-20 h-20 rounded-xl overflow-hidden bg-slate-200 shrink-0">
                        @if($event->cover_image)
                            <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-ink leading-snug">{{ $event->title }}</h3>
                        <p class="text-xs text-mute flex items-center gap-1 mt-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $event->event_date?->format('M j, Y') }}@if($timeLabel) · {{ $timeLabel }}@endif
                        </p>
                        <p class="text-xs text-mute flex items-center gap-1 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $event->venue }}
                        </p>
                    </div>
                </div>

                <div class="space-y-3 mb-5">
                    @foreach($event->ticketTypes as $type)
                        @php $max = min(20, $type->remaining()); @endphp
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $type->name }}</p>
                                <p class="text-xs text-mute">{{ $event->isFreeEvent() || (float) $type->price === 0.0 ? __('ui.free') : '$'.number_format((float) $type->price, 0) }} · {{ __('ui.left', ['count' => $type->remaining()]) }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="dec({{ $type->id }})" class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center font-bold text-ink hover:bg-page leading-none">−</button>
                                <span class="w-6 text-center font-bold" x-text="qty[{{ $type->id }}] || 0"></span>
                                <button type="button" @click="inc({{ $type->id }}, {{ $max }})" class="w-8 h-8 rounded-lg bg-brand text-white flex items-center justify-center font-bold hover:bg-brand-dark leading-none">+</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm" x-show="ticketCount > 0 && !isFree">
                        <span class="text-mute">{{ __('ui.service_fee') }}</span>
                        <span class="font-bold">${{ number_format($serviceFee, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-ink">{{ __('ui.total') }}</span>
                        <span class="text-2xl font-extrabold text-ink" x-text="'$' + total.toFixed(0)"></span>
                    </div>
                </div>
            </div>

            <button
                type="button"
                @click="goStep(2)"
                :disabled="ticketCount < 1"
                class="w-full bg-brand hover:bg-brand-dark disabled:bg-slate-100 disabled:text-mute text-white font-bold py-4 rounded-xl transition-colors"
            >
                {{ __('ui.continue_to_details') }}
            </button>
        </div>

        {{-- Step 2: Your Details --}}
        <div x-show="step === 2" x-cloak class="space-y-5">
            <h2 class="text-xl sm:text-2xl font-extrabold text-ink">{{ __('ui.step_your_details') }}</h2>
            @if($signedIn)
                <div class="rounded-2xl bg-brand/5 border border-brand/20 px-4 py-3 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <p class="text-sm text-ink leading-relaxed">
                        {{ __('ui.signed_in_as', ['name' => $customer->name]) }}.
                        {{ __('ui.checkout_account_note') }}
                    </p>
                </div>
            @else
                <p class="text-sm text-mute">{{ __('ui.guest_checkout_note') }}</p>
            @endif

            <div class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-ink mb-1.5">{{ __('ui.full_name') }}</label>
                    <input
                        type="text"
                        name="buyer_name"
                        x-model="name"
                        placeholder="e.g. Faadumo Hassan"
                        required
                        @if($signedIn) readonly @endif
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand {{ $signedIn ? 'bg-slate-50 text-mute cursor-not-allowed' : 'bg-page' }}"
                    >
                </div>
                <div>
                    <label class="block text-sm font-bold text-ink mb-1.5">
                        {{ __('ui.phone_number') }}
                        <span class="text-brand text-xs font-semibold">{{ __('ui.phone_required_payment') }}</span>
                    </label>
                    <div class="flex">
                        <span class="flex items-center px-3 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-sm text-mute shrink-0">+252</span>
                        <input
                            type="tel"
                            x-model="phoneLocal"
                            placeholder="63 1234567"
                            required
                            @if($signedIn) readonly @endif
                            class="flex-1 border border-slate-200 rounded-r-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand {{ $signedIn ? 'bg-slate-50 text-mute cursor-not-allowed' : 'bg-page' }}"
                        >
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-ink mb-1.5">
                        {{ __('ui.email_address') }}
                        <span class="text-mute text-xs font-normal">{{ __('ui.optional') }}</span>
                    </label>
                    <input
                        type="email"
                        name="buyer_email"
                        x-model="email"
                        placeholder="yourname@example.com"
                        @if($signedIn) readonly @endif
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand {{ $signedIn ? 'bg-slate-50 text-mute cursor-not-allowed' : 'bg-page' }}"
                    >
                </div>
            </div>

            <p x-show="otpError" x-cloak class="text-sm text-red-600 font-semibold" x-text="otpError"></p>

            <div class="flex gap-3">
                <button type="button" @click="step = 1" class="flex-1 border border-slate-200 text-ink font-bold py-3.5 rounded-xl hover:bg-page transition-colors">
                    {{ __('ui.back') }}
                </button>
                <button
                    type="button"
                    @click="continueFromDetails"
                    :disabled="otpBusy"
                    class="flex-1 bg-brand hover:bg-brand-dark disabled:opacity-60 text-white font-bold py-3.5 rounded-xl transition-colors"
                >
                    <span x-text="signedIn ? (isFree ? i18n.claimFreeTickets : i18n.continueToPayment) : (otpBusy ? i18n.sendingCode : i18n.continue)"></span>
                </button>
            </div>
        </div>

        {{-- Step 3: Confirm OTP (guests only) --}}
        <div x-show="step === 3 && !signedIn" x-cloak class="space-y-5">
            <h2 class="text-xl sm:text-2xl font-extrabold text-ink">{{ __('ui.confirm_phone') }}</h2>
            <p class="text-sm text-mute">
                {{ __('ui.enter_code_sent_to') }} <span class="font-bold text-ink" x-text="fullPhone"></span>.
                <span class="block mt-1 text-brand font-semibold" x-show="otpHint" x-text="otpHint"></span>
            </p>

            <div class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-ink mb-1.5">{{ __('ui.confirmation_code') }}</label>
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        x-model="otpCode"
                        placeholder="123456"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm tracking-[0.35em] text-center font-bold outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand bg-page"
                    >
                </div>
                <p x-show="otpError" x-cloak class="text-sm text-red-600 font-semibold" x-text="otpError"></p>
                <button
                    type="button"
                    @click="verifyCheckoutOtp"
                    :disabled="otpBusy"
                    class="w-full bg-brand hover:bg-brand-dark disabled:opacity-60 text-white font-bold py-4 rounded-xl"
                >
                    <span x-text="otpBusy ? i18n.checking : i18n.confirmContinuePayment"></span>
                </button>
                <button type="button" @click="resendCheckoutOtp" :disabled="otpBusy" class="w-full text-sm font-bold text-brand hover:underline py-2">
                    {{ __('ui.resend_code') }}
                </button>
            </div>

            <button type="button" @click="step = 2; otpToken = ''; otpCode = ''" class="w-full border border-slate-200 text-ink font-bold py-3 rounded-xl hover:bg-page transition-colors text-sm">
                {{ __('ui.back_to_details') }}
            </button>
        </div>

        {{-- Payment step (3 signed-in / 4 guest) --}}
        <div x-show="!isFree && step === paymentStep" x-cloak class="space-y-5">
            <h2 class="text-xl sm:text-2xl font-extrabold text-ink">{{ __('ui.step_payment') }}</h2>
            <p class="text-sm text-mute">{{ __('ui.choose_mobile_money') }}</p>

            @if(! empty($waafiSandbox) && ! empty($waafiTestWallets))
                <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4 text-sm text-ink">
                    <p class="font-extrabold mb-1">WaafiPay sandbox</p>
                    @if(! config('waafipay.has_sandbox_credentials'))
                        <p class="text-xs font-semibold text-red-700 mb-3">{{ __('ui.payment_failed_sandbox_credentials') }}</p>
                    @endif
                    <p class="text-xs text-mute mb-3">Use a test wallet and PIN <span class="font-bold text-ink">1212</span>. Enter the local number after +252.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($waafiTestWallets as $wallet)
                            <button
                                type="button"
                                @click="chargePhoneLocal = '{{ $wallet['local'] }}'"
                                class="px-3 py-1.5 rounded-lg bg-white border border-amber-200 text-xs font-bold hover:border-brand"
                            >
                                {{ $wallet['brand'] }} · {{ $wallet['local'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 min-[480px]:grid-cols-2 gap-3 sm:gap-4">
                <button
                    type="button"
                    @click="payment = 'waafipay'; payNotice = ''"
                    class="relative border-2 rounded-2xl p-4 sm:p-5 text-left transition-all bg-white min-w-0"
                    :class="payment === 'waafipay' ? 'border-brand bg-brand/5' : 'border-slate-100 hover:border-brand/40'"
                >
                    <div x-show="payment === 'waafipay'" class="absolute top-3 right-3 w-5 h-5 bg-brand rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    @include('partials.operator-logos', ['class' => 'mb-3 pr-6'])
                    <p class="font-extrabold text-ink text-base sm:text-lg">WaafiPay</p>
                    <p class="text-xs text-mute mt-0.5">{{ __('ui.mobile_money_waafipay') }}</p>
                </button>

                <button
                    type="button"
                    @click="payNotice = i18n.edahabUnavailable"
                    class="relative border-2 rounded-2xl p-4 sm:p-5 text-left transition-all bg-white border-slate-100 hover:border-brand/40 min-w-0"
                >
                    <img src="{{ asset('images/somtel-logo.png') }}" alt="Somtel eDahab" class="h-12 sm:h-16 w-full max-w-[220px] object-contain object-left mb-3">
                    <p class="font-extrabold text-ink text-base sm:text-lg">eDahab</p>
                    <p class="text-xs text-mute mt-0.5">{{ __('ui.mobile_money_somtel') }}</p>
                </button>
            </div>
            <p x-show="payNotice" x-cloak x-text="payNotice" class="text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3"></p>

            <div x-show="payment" x-cloak class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4">
                <p class="text-sm font-bold text-ink"
                   x-text="(lockPhone ? i18n.chargeAccountPhone : i18n.enterNumberToCharge).replace(':method', 'WaafiPay')"
                ></p>
                <div class="flex">
                    <span class="flex items-center px-3 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-sm text-mute shrink-0">+252</span>
                    <input
                        type="tel"
                        x-model="chargePhoneLocal"
                        placeholder="611111111"
                        @if($lockPhone) readonly @endif
                        class="flex-1 border border-slate-200 rounded-r-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand {{ $lockPhone ? 'bg-slate-50 text-mute cursor-not-allowed' : 'bg-page' }}"
                    >
                </div>
                <div class="flex items-center justify-between bg-page rounded-xl p-4">
                    <span class="text-sm font-semibold text-mute">{{ __('ui.total_to_charge') }}</span>
                    <span class="text-xl font-extrabold text-ink" x-text="'$' + total.toFixed(0)"></span>
                </div>

                @if($allowForceFail ?? false)
                <label class="flex items-center gap-2 text-xs text-mute cursor-pointer">
                    <input type="checkbox" name="force_fail" value="1" class="rounded border-slate-300">
                    {{ __('ui.simulate_failed_payment') }}
                </label>
                @endif

                <button
                    type="submit"
                    :disabled="submitting || !payment || ticketCount < 1 || (!signedIn && !otpToken)"
                    class="w-full bg-brand hover:bg-brand-dark disabled:bg-slate-100 disabled:text-mute text-white font-extrabold py-4 rounded-xl transition-colors text-base"
                >
                    <span x-text="i18n.payWithMethod.replace(':amount', total.toFixed(0)).replace(':method', 'WaafiPay')"></span>
                </button>
                <div class="flex items-center justify-center gap-2 text-xs text-mute pt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    {{ __('ui.encryption_sms_note') }}
                </div>
            </div>

            <button type="button" @click="step = signedIn ? 2 : 3" class="w-full border border-slate-200 text-ink font-bold py-3 rounded-xl hover:bg-page transition-colors text-sm">
                {{ __('ui.back') }}
            </button>
        </div>
    </form>

    @include('partials.wallet-pin-modal')
</div>

<style>[x-cloak] { display: none !important; }</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function checkoutWizard() {
    const prices = {
        @foreach($event->ticketTypes as $type)
            {{ $type->id }}: {{ (float) $type->price }},
        @endforeach
    };
    const fee = {{ (float) $serviceFee }};
    const isFree = {{ ! empty($isFreeEvent) ? 'true' : 'false' }};
    const qty = {
        @foreach($event->ticketTypes as $type)
            {{ $type->id }}: {{ (int) ($oldQty[$type->id] ?? 0) }},
        @endforeach
    };

    const hasQty = Object.values(qty).some((q) => Number(q) > 0);
    const signedIn = {{ $signedIn ? 'true' : 'false' }};
    const lockPhone = {{ $lockPhone ? 'true' : 'false' }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
    const otpSendUrl = @json($otpSendUrl);
    const otpVerifyUrl = @json($otpVerifyUrl);
    const initialStep = {{ $errors->any() ? ($signedIn ? 3 : 4) : 1 }};
    const i18n = {
        stepSelectTickets: @json(__('ui.step_select_tickets')),
        stepYourDetails: @json(__('ui.step_your_details')),
        stepConfirm: @json(__('ui.step_confirm')),
        stepPayment: @json(__('ui.step_payment')),
        continueToPayment: @json(__('ui.continue_to_payment')),
        claimFreeTickets: @json(__('ui.claim_free_tickets')),
        sendingCode: @json(__('ui.sending_code')),
        continue: @json(__('ui.continue')),
        checking: @json(__('ui.checking')),
        confirmContinuePayment: @json(__('ui.confirm_continue_payment')),
        chargeAccountPhone: @json(__('ui.charge_account_phone')),
        enterNumberToCharge: @json(__('ui.enter_number_to_charge')),
        payWithMethod: @json(__('ui.pay_with_method')),
        edahabUnavailable: @json(__('ui.edahab_unavailable')),
        namePhoneRequired: @json(__('ui.name_phone_required')),
        couldNotSendCode: @json(__('ui.could_not_send_code')),
        codeSent: @json(__('ui.code_sent')),
        testingCode: @json(__('ui.testing_code')),
        enterConfirmationCode: @json(__('ui.enter_confirmation_code')),
        couldNotVerifyCode: @json(__('ui.could_not_verify_code')),
        invalidCode: @json(__('ui.invalid_code')),
        confirmPhoneFirst: @json(__('ui.confirm_phone_first')),
        walletPinRequired: @json(__('ui.wallet_pin_required')),
    };

    return {
        step: hasQty && initialStep === 1 ? 1 : initialStep,
        signedIn,
        lockPhone,
        isFree,
        i18n,
        get stepLabels() {
            if (this.isFree) {
                return this.signedIn
                    ? [i18n.stepSelectTickets, i18n.stepYourDetails]
                    : [i18n.stepSelectTickets, i18n.stepYourDetails, i18n.stepConfirm];
            }
            return this.signedIn
                ? [i18n.stepSelectTickets, i18n.stepYourDetails, i18n.stepPayment]
                : [i18n.stepSelectTickets, i18n.stepYourDetails, i18n.stepConfirm, i18n.stepPayment];
        },
        get paymentStep() {
            return this.signedIn ? 3 : 4;
        },
        qty,
        name: @json(old('buyer_name', $defaultName)),
        email: @json(old('buyer_email', $defaultEmail)),
        phoneLocal: @json($phoneLocal),
        chargePhoneLocal: @json($phoneLocal),
        otpPhoneLocal: '',
        payment: @json(old('payment_method', 'waafipay')),
        payNotice: '',
        submitting: false,
        otpBusy: false,
        otpCode: '',
        otpToken: @json(old('otp_token', '')),
        otpError: '',
        otpHint: '',
        sandbox: {{ $waafiSandbox ? 'true' : 'false' }},
        showPinModal: {{ $errors->has('wallet_pin') ? 'true' : 'false' }},
        walletPin: '',
        pinError: @json($errors->first('wallet_pin') ?: ''),
        pinReady: false,

        init() {
            if (this.showPinModal) this.openPinModal();
        },

        get ticketCount() {
            return Object.values(this.qty).reduce((a, b) => a + (Number(b) || 0), 0);
        },
        get subtotal() {
            return Object.entries(this.qty).reduce((sum, [id, q]) => sum + (prices[id] || 0) * (Number(q) || 0), 0);
        },
        get total() {
            return this.subtotal + (this.ticketCount > 0 ? fee : 0);
        },
        get fullPhone() {
            const local = String(this.phoneLocal || '').replace(/\D/g, '');
            return local ? '+252' + local : '';
        },
        get chargeFullPhone() {
            const local = String(this.chargePhoneLocal || this.phoneLocal || '').replace(/\D/g, '');
            return local ? '+252' + local : '';
        },
        get otpFullPhone() {
            const local = String(this.otpPhoneLocal || this.phoneLocal || '').replace(/\D/g, '');
            return local ? '+252' + local : '';
        },

        inc(id, max) {
            if ((this.qty[id] || 0) < max) this.qty[id] = (this.qty[id] || 0) + 1;
        },
        dec(id) {
            if ((this.qty[id] || 0) > 0) this.qty[id]--;
        },
        goStep(n) {
            if (n === 2 && this.ticketCount < 1) return;
            this.step = n;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        async continueFromDetails() {
            if (!String(this.name || '').trim() || !String(this.phoneLocal || '').replace(/\D/g, '')) {
                this.otpError = i18n.namePhoneRequired;
                return;
            }
            this.otpError = '';
            if (this.signedIn) {
                this.chargePhoneLocal = this.chargePhoneLocal || this.phoneLocal;
                if (this.isFree) {
                    this.$refs.checkoutForm?.requestSubmit();
                    return;
                }
                this.goStep(3);
                return;
            }
            const sent = await this.sendCheckoutOtp();
            if (sent) this.goStep(3);
        },
        async sendCheckoutOtp() {
            this.otpBusy = true;
            this.otpError = '';
            try {
                const res = await fetch(otpSendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ phone: this.fullPhone, purpose: 'checkout' }),
                });
                const text = await res.text();
                let body = {};
                try { body = text ? JSON.parse(text) : {}; } catch (_) {
                    this.otpError = i18n.couldNotSendCode + ' (' + res.status + ')';
                    return false;
                }
                if (!res.ok) {
                    this.otpError = body.errors?.phone?.[0] || body.errors?.buyer_phone?.[0] || body.message || i18n.couldNotSendCode;
                    return false;
                }
                this.otpHint = body.debug_code
                    ? i18n.testingCode.replace(':code', body.debug_code)
                    : (body.message || i18n.codeSent);
                return true;
            } catch (e) {
                this.otpError = e.message || i18n.couldNotSendCode;
                return false;
            } finally {
                this.otpBusy = false;
            }
        },
        async resendCheckoutOtp() {
            await this.sendCheckoutOtp();
        },
        async verifyCheckoutOtp() {
            if (!String(this.otpCode || '').trim()) {
                this.otpError = i18n.enterConfirmationCode;
                return;
            }
            this.otpBusy = true;
            this.otpError = '';
            try {
                const res = await fetch(otpVerifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        phone: this.fullPhone,
                        purpose: 'checkout',
                        otp: String(this.otpCode).trim(),
                    }),
                });
                const text = await res.text();
                let body = {};
                try { body = text ? JSON.parse(text) : {}; } catch (_) {
                    this.otpError = i18n.couldNotVerifyCode + ' (' + res.status + ')';
                    return;
                }
                if (!res.ok) {
                    this.otpError = body.errors?.otp?.[0] || body.message || i18n.invalidCode;
                    return;
                }
                this.otpToken = body.otp_token;
                this.otpPhoneLocal = String(this.phoneLocal || '').replace(/\D/g, '');
                this.chargePhoneLocal = this.otpPhoneLocal;
                if (this.isFree) {
                    this.$refs.checkoutForm?.requestSubmit();
                    return;
                }
                this.goStep(4);
            } catch (e) {
                this.otpError = e.message || i18n.couldNotVerifyCode;
            } finally {
                this.otpBusy = false;
            }
        },
        prepareSubmit(e) {
            if (this.ticketCount < 1 || !this.chargeFullPhone) {
                e.preventDefault();
                return;
            }
            if (this.isFree) {
                if (!this.signedIn && !this.otpToken) {
                    e.preventDefault();
                    this.otpError = i18n.confirmPhoneFirst;
                    this.step = 3;
                    return;
                }
                this.submitting = true;
                return;
            }
            if (!this.payment || this.ticketCount < 1 || !this.chargeFullPhone) {
                e.preventDefault();
                return;
            }
            if (!this.signedIn && !this.otpToken) {
                e.preventDefault();
                this.otpError = i18n.confirmPhoneFirst;
                this.step = 3;
                return;
            }
            if (this.sandbox && !this.pinReady) {
                e.preventDefault();
                this.openPinModal();
                return;
            }
            this.submitting = true;
        },
        openPinModal() {
            this.showPinModal = true;
            this.pinError = this.pinError || '';
            this.$nextTick(() => this.$refs.pinInput?.focus());
        },
        closePinModal() {
            this.showPinModal = false;
            this.submitting = false;
        },
        confirmPin() {
            const pin = String(this.walletPin || '').replace(/\D/g, '');
            if (pin.length !== 4) {
                this.pinError = i18n.walletPinRequired;
                return;
            }
            this.walletPin = pin;
            this.pinError = '';
            this.pinReady = true;
            this.showPinModal = false;
            this.submitting = true;
            this.$nextTick(() => this.$refs.checkoutForm?.submit());
        },
    }
}
</script>
@endif
@endsection
