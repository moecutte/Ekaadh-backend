@extends('layouts.organizer')
@section('title', 'Pay free event fee')
@section('heading', 'Pay free event fee')

@section('content')
@php
    $profilePhone = auth()->user()->phone ?: ($event->organizer?->business_phone ?? '');
    $oldBuyerPhone = old('buyer_phone', $profilePhone);
    $chargePhoneLocal = $oldBuyerPhone
        ? preg_replace('/^\+?252/', '', preg_replace('/\D+/', '', (string) $oldBuyerPhone))
        : '';
    $capacity = $event->freeEventCapacity();
    $feePerTicket = $event->freeEventFeePerTicket();
@endphp
<div class="max-w-md mx-auto" x-data="packagePayPin()">
    <a href="{{ route('organizer.events.index') }}" class="text-sm font-bold text-mute hover:text-brand">&larr; Back to events</a>
    <h2 class="text-xl font-extrabold mt-3 mb-1">{{ $event->title }}</h2>
    <p class="text-sm text-mute mb-6">Admin approved this free event. Pay the platform fee to make it live for guests.</p>

    @if(session('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-5 space-y-2 text-sm">
        <div class="flex justify-between gap-3">
            <span>Ticket capacity</span>
            <span class="font-bold">{{ number_format($capacity) }}</span>
        </div>
        <div class="flex justify-between gap-3">
            <span>Fee per ticket</span>
            <span class="font-bold">${{ number_format($feePerTicket, 2) }}</span>
        </div>
        <div class="flex justify-between font-extrabold text-base pt-2 border-t border-slate-50">
            <span>Total due</span>
            <span class="text-brand">${{ number_format((float) $order->total_amount, 2) }}</span>
        </div>
        <p class="text-[11px] text-mute pt-2">Order {{ $order->order_number }} · {{ number_format($capacity) }} × ${{ number_format($feePerTicket, 2) }}</p>
    </div>

    <form method="POST" action="{{ route('organizer.events.pay.store', $event) }}" x-ref="payForm" @submit="prepareSubmit" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        @csrf
        <input type="hidden" name="wallet_pin" :value="walletPin">
        <input type="hidden" name="buyer_phone" :value="chargeFullPhone">
        <input type="hidden" name="payment_method" :value="payment">
        @if(! empty($waafiSandbox) && ! empty($waafiTestWallets))
            <div x-show="payment === 'waafipay'" x-cloak class="rounded-2xl bg-amber-50 border border-amber-100 p-4 text-sm text-ink">
                <p class="font-extrabold mb-1">WaafiPay sandbox</p>
                <p class="text-xs text-mute mb-3">Charge a test wallet, then enter PIN <span class="font-bold text-ink">1212</span>.</p>
                <div class="flex flex-wrap gap-2 mb-3">
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
        @if(! empty($waafiCardCheckout) && ! empty($waafiSandbox) && ! empty($waafiTestCards))
            <div x-show="payment === 'waafipay_card'" x-cloak class="rounded-2xl bg-amber-50 border border-amber-100 p-4 text-sm text-ink">
                <p class="font-extrabold mb-1">WaafiPay sandbox cards</p>
                @if(empty($waafiHppEnabled))
                    <p class="text-xs font-semibold text-red-700 mb-2">{{ __('ui.payment_failed_card_unavailable') }}</p>
                @endif
                <ul class="space-y-1 text-xs font-semibold">
                    @foreach($waafiTestCards as $card)
                        <li>{{ $card['network'] }} · {{ $card['number'] }} · {{ $card['expiry'] }} · CVV {{ $card['cvv'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div>
            <label class="text-xs font-bold text-mute block mb-2">{{ __('ui.payment_method') }}</label>
            <div class="grid grid-cols-1 gap-3">
                <button
                    type="button"
                    @click="payment = 'waafipay'"
                    class="rounded-xl border-2 px-3 py-3 text-left"
                    :class="payment === 'waafipay' ? 'border-brand bg-brand-soft' : 'border-slate-200 hover:border-brand/40'"
                >
                    @include('partials.operator-logos', ['size' => 'compact'])
                    <p class="text-sm font-bold text-ink mt-2">WaafiPay</p>
                    <p class="text-xs text-mute">{{ __('ui.mobile_money_waafipay') }}</p>
                </button>
                @if(! empty($waafiCardCheckout))
                <button
                    type="button"
                    @click="payment = 'waafipay_card'"
                    class="rounded-xl border-2 px-3 py-3 text-left"
                    :class="payment === 'waafipay_card' ? 'border-brand bg-brand-soft' : 'border-slate-200 hover:border-brand/40'"
                >
                    @include('partials.card-network-logos', ['size' => 'compact'])
                    <p class="text-sm font-bold text-ink mt-2">{{ __('ui.card_visa_mastercard') }}</p>
                    <p class="text-xs text-mute">{{ __('ui.card_payment_hint') }}</p>
                </button>
                @endif
            </div>
        </div>
        <div x-show="payment === 'waafipay'" x-cloak>
            <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.phone_number') }}</label>
            <p class="text-xs text-mute mb-2">{{ __('ui.enter_number_to_charge', ['method' => 'WaafiPay']) }}</p>
            <div class="flex">
                <span class="flex items-center px-3 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-sm text-mute shrink-0">+252</span>
                <input
                    type="tel"
                    x-model="chargePhoneLocal"
                    placeholder="611111111"
                    class="flex-1 border border-slate-200 rounded-r-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand bg-page"
                >
            </div>
            <p x-show="phoneError" x-cloak class="text-sm text-red-600 font-semibold mt-2" x-text="phoneError"></p>
        </div>
        @if($allowForceFail)
            <label class="flex items-center gap-2 text-xs text-mute">
                <input type="checkbox" name="force_fail" value="1"> {{ __('ui.simulate_failed_payment') }}
            </label>
        @endif
        <button type="submit" class="w-full py-3.5 rounded-2xl bg-brand text-white font-extrabold text-sm hover:bg-brand-dark">Pay ${{ number_format((float) $order->total_amount, 2) }}</button>
    </form>

    @include('partials.wallet-pin-modal')
</div>
<style>[x-cloak] { display: none !important; }</style>
<script>
function packagePayPin() {
    return {
        payment: @json(old('payment_method', 'waafipay')),
        sandbox: {{ ! empty($waafiSandbox) ? 'true' : 'false' }},
        showPinModal: {{ $errors->has('wallet_pin') ? 'true' : 'false' }},
        walletPin: '',
        pinError: @json($errors->first('wallet_pin') ?: ''),
        pinReady: false,
        chargePhoneLocal: @json($chargePhoneLocal),
        phoneError: @json($errors->first('buyer_phone') ?: ''),
        get chargeFullPhone() {
            const local = String(this.chargePhoneLocal || '').replace(/\D/g, '');
            return local ? '+252' + local : '';
        },
        prepareSubmit(e) {
            this.phoneError = '';
            if (this.payment === 'waafipay' && !String(this.chargePhoneLocal || '').replace(/\D/g, '')) {
                e.preventDefault();
                this.phoneError = @json(__('ui.sandbox_charge_phone_required'));
                return;
            }
            if (this.sandbox && this.payment === 'waafipay' && !this.pinReady) {
                e.preventDefault();
                this.showPinModal = true;
                this.$nextTick(() => this.$refs.pinInput?.focus());
            }
        },
        closePinModal() {
            this.showPinModal = false;
        },
        confirmPin() {
            const pin = String(this.walletPin || '').replace(/\D/g, '');
            if (pin.length !== 4) {
                this.pinError = @json(__('ui.wallet_pin_required'));
                return;
            }
            this.walletPin = pin;
            this.pinError = '';
            this.pinReady = true;
            this.showPinModal = false;
            this.$nextTick(() => this.$refs.payForm?.submit());
        },
    };
}
</script>
@endsection
