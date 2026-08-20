@if(! empty($waafiSandbox))
<div
    x-show="showPinModal"
    x-cloak
    class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center px-4 py-6"
    @keydown.escape.window="closePinModal()"
>
    <div class="absolute inset-0 bg-ink/50" @click="closePinModal()"></div>
    <div class="relative w-full max-w-sm rounded-3xl bg-white shadow-2xl p-6 text-center">
        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-brand/10 text-brand flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h3 class="text-lg font-extrabold text-ink mb-1">{{ __('ui.wallet_pin_title') }}</h3>
        <p class="text-sm text-mute mb-1">{{ __('ui.wallet_pin_hint') }}</p>
        <p class="text-xs font-semibold text-brand mb-4">{{ __('ui.wallet_pin_test_hint') }}</p>
        <input
            type="password"
            inputmode="numeric"
            maxlength="4"
            autocomplete="one-time-code"
            x-ref="pinInput"
            x-model="walletPin"
            @keydown.enter.prevent="confirmPin()"
            placeholder="••••"
            class="w-full text-center text-2xl tracking-[0.6em] font-black border border-slate-200 rounded-2xl px-4 py-3 outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand bg-page"
        >
        <p x-show="pinError" x-cloak class="text-sm text-red-600 font-semibold mt-3" x-text="pinError"></p>
        <button
            type="button"
            @click="confirmPin()"
            class="mt-4 w-full bg-brand hover:bg-brand-dark text-white font-extrabold py-3.5 rounded-2xl"
        >{{ __('ui.wallet_pin_continue') }}</button>
        <button type="button" @click="closePinModal()" class="mt-2 w-full text-sm font-bold text-mute py-2">{{ __('ui.cancel') }}</button>
    </div>
</div>
@endif
