@extends('layouts.app')

@section('title', __('ui.pay_for_tickets').' — '.$event->title)

@section('content')
@php
    $oldBuyerPhone = old('buyer_phone');
    $chargePhoneLocal = $oldBuyerPhone
        ? preg_replace('/^\+?252/', '', preg_replace('/\D+/', '', (string) $oldBuyerPhone))
        : '';
@endphp
<div
    class="max-w-md mx-auto px-4 sm:px-6 py-10"
    x-data="privatePayPin()"
>
    <a href="{{ route('private-events.index') }}" class="text-sm font-bold text-mute hover:text-brand">&larr; {{ __('ui.my_private_events') }}</a>
    <h1 class="text-2xl font-extrabold mt-3 mb-1">{{ __('ui.pay_for_tickets') }}</h1>
    <p class="text-sm text-mute mb-6">{{ $event->title }}</p>

    @if(session('success'))
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm p-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-5 space-y-2 text-sm">
        @foreach($order->items as $item)
            <div class="flex justify-between gap-3">
                <span>{{ $item->quantity }} × {{ $item->ticketType?->name ?? __('ui.ticket') }} @ ${{ number_format($item->unit_price, 2) }}</span>
                <span class="font-bold">${{ number_format($item->subtotal, 2) }}</span>
            </div>
        @endforeach
        <div class="flex justify-between text-mute pt-2 border-t border-slate-50">
            <span>{{ __('ui.service_fee') }}</span>
            <span>${{ number_format($order->service_fee, 2) }}</span>
        </div>
        <div class="flex justify-between font-extrabold text-base pt-1">
            <span>{{ __('ui.total') }}</span>
            <span class="text-brand">${{ number_format($order->total_amount, 2) }}</span>
        </div>
        <p class="text-[11px] text-mute pt-2">{{ __('ui.order_label', ['number' => $order->order_number]) }}</p>
    </div>

    <form method="POST" action="{{ route('private-events.pay.store', $event) }}" x-ref="payForm" @submit="prepareSubmit" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        @csrf
        <input type="hidden" name="wallet_pin" :value="walletPin">
        <input type="hidden" name="buyer_phone" :value="chargeFullPhone">
        @if(! empty($waafiSandbox) && ! empty($waafiTestWallets))
            <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4 text-sm text-ink">
                <p class="font-extrabold mb-1">WaafiPay sandbox</p>
                <p class="text-xs text-mute mb-3">Charge a test wallet, then enter PIN <span class="font-bold text-ink">1212</span>. Your account phone is not a sandbox wallet.</p>
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
                <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.phone_number') }}</label>
                <div class="flex">
                    <span class="flex items-center px-3 bg-white border border-r-0 border-amber-200 rounded-l-xl text-sm text-mute shrink-0">+252</span>
                    <input
                        type="tel"
                        x-model="chargePhoneLocal"
                        placeholder="611111111"
                        class="flex-1 border border-amber-200 rounded-r-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand bg-white"
                    >
                </div>
                <p x-show="phoneError" x-cloak class="text-sm text-red-600 font-semibold mt-2" x-text="phoneError"></p>
            </div>
        @endif
        <div>
            <label class="text-xs font-bold text-mute block mb-2">{{ __('ui.payment_method') }}</label>
            <input type="hidden" name="payment_method" value="waafipay">
            <div class="grid grid-cols-1 min-[480px]:grid-cols-2 gap-3">
                <div class="flex flex-col gap-2 rounded-xl border-2 border-brand bg-brand-soft px-3 py-3 min-w-0">
                    @include('partials.operator-logos', ['size' => 'compact'])
                    <div>
                        <p class="text-sm font-bold text-ink">WaafiPay</p>
                        <p class="text-xs text-mute">{{ __('ui.mobile_money_waafipay') }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    onclick="document.getElementById('edahab-notice').classList.remove('hidden')"
                    class="flex flex-col gap-2 rounded-xl border border-slate-200 px-3 py-3 text-left hover:border-brand/40 min-w-0"
                >
                    <img src="{{ asset('images/somtel-logo.png') }}" alt="Somtel eDahab" class="h-10 sm:h-12 w-full max-w-[200px] object-contain object-left">
                    <div>
                        <p class="text-sm font-bold text-ink">eDahab</p>
                        <p class="text-xs text-mute">{{ __('ui.mobile_money_somtel') }}</p>
                    </div>
                </button>
            </div>
            <p id="edahab-notice" class="hidden mt-3 text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2">{{ __('ui.edahab_unavailable') }}</p>
        </div>
        @if($allowForceFail)
            <label class="flex items-center gap-2 text-xs text-mute">
                <input type="checkbox" name="force_fail" value="1"> {{ __('ui.simulate_failed_payment') }}
            </label>
        @endif
        <button type="submit" class="w-full py-3.5 rounded-2xl bg-brand text-white font-extrabold text-sm hover:bg-brand-dark">{{ __('ui.pay_amount', ['amount' => number_format($order->total_amount, 2)]) }}</button>
    </form>

    @include('partials.wallet-pin-modal')
</div>
<style>[x-cloak] { display: none !important; }</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function privatePayPin() {
    return {
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
            if (this.sandbox && !String(this.chargePhoneLocal || '').replace(/\D/g, '')) {
                e.preventDefault();
                this.phoneError = @json(__('ui.sandbox_charge_phone_required'));
                return;
            }
            if (this.sandbox && !this.pinReady) {
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
