@extends('layouts.customer-auth')
@section('title', __('ui.create_account'))
@section('subtitle', __('ui.create_account_subtitle'))

@section('content')
<div x-data="registerOtp()">
    {{-- Step 1: account details --}}
    <form x-show="!otpSent" class="space-y-3" @submit.prevent="sendCode" novalidate>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.your_name') }}</label>
            <input name="name" value="{{ old('name') }}" required x-model="name"
                class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.phone') }}</label>
            <div class="flex">
                <span class="flex items-center px-3 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-sm text-mute shrink-0">+252</span>
                <input type="tel" x-model="phoneLocal" required placeholder="63 234 5678"
                    class="flex-1 rounded-r-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.email') }} <span class="font-medium text-slate-400">{{ __('ui.optional') }}</span></label>
            <input type="email" x-model="email" value="{{ old('email') }}"
                class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.password') }}</label>
            <input type="password" x-model="password"
                class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.confirm_password') }}</label>
            <input type="password" x-model="passwordConfirm"
                class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
        </div>

        <p x-show="error" x-cloak class="text-sm text-red-600 font-semibold" x-text="error"></p>
        @if($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-3">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <button
            type="submit"
            :disabled="busy"
            class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark mt-2 disabled:opacity-60"
        >
            <span x-text="busy ? i18n.sendingCode : i18n.sendCode">{{ __('ui.send_confirmation_code') }}</span>
        </button>
    </form>

    {{-- Step 2: confirmation code (own form) --}}
    <form
        x-show="otpSent"
        x-cloak
        method="POST"
        action="{{ route('customer.register') }}"
        class="space-y-4"
        @submit.prevent="onSubmit"
        novalidate
    >
        @csrf
        <input type="hidden" name="otp_token" x-model="otpToken">
        <input type="hidden" name="name" :value="name">
        <input type="hidden" name="phone" :value="fullPhone">
        <input type="hidden" name="email" :value="email">
        <input type="hidden" name="password" :value="password">
        <input type="hidden" name="password_confirmation" :value="passwordConfirm">

        <div>
            <h2 class="text-lg font-extrabold text-ink">{{ __('ui.confirm_phone') }}</h2>
            <p class="text-sm text-mute mt-1">
                {{ __('ui.enter_code_sent_to') }}
                <span class="font-bold text-ink" x-text="fullPhone"></span>.
            </p>
            <p class="text-xs text-brand font-semibold mt-2" x-show="otpHint" x-text="otpHint"></p>
        </div>

        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.confirmation_code') }}</label>
            <input
                type="text"
                inputmode="numeric"
                maxlength="6"
                x-model="otpCode"
                placeholder="123456"
                autocomplete="one-time-code"
                class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm tracking-[0.35em] text-center font-bold outline-none focus:border-brand"
            >
        </div>

        <p x-show="error" x-cloak class="text-sm text-red-600 font-semibold" x-text="error"></p>

        <button
            type="submit"
            :disabled="busy"
            class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark disabled:opacity-60"
        >
            <span x-text="busy ? i18n.sendingCode : i18n.verifyCreate">{{ __('ui.verify_create_account') }}</span>
        </button>
        <button type="button" @click="resend" :disabled="busy" class="w-full text-sm font-bold text-brand hover:underline py-1 disabled:opacity-50">
            {{ __('ui.resend_code') }}
        </button>
        <button type="button" @click="backToDetails" class="w-full border border-slate-200 text-ink font-bold py-3 rounded-xl hover:bg-page transition-colors text-sm">
            {{ __('ui.back') }}
        </button>
    </form>
</div>
<p class="text-center text-sm text-mute mt-5">
    {{ __('ui.already_have_account') }}
    <a href="{{ route('customer.login') }}" class="font-bold text-brand">{{ __('ui.sign_in') }}</a>
</p>

<style>[x-cloak]{display:none!important}</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function registerOtp() {
    const otpSendUrl = @json(route('otp.send'));
    const otpVerifyUrl = @json(route('otp.verify'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
    const oldPhone = @json(old('phone', ''));
    const localFromOld = String(oldPhone || '').replace(/^\+?252/, '').replace(/\D/g, '').replace(/^0+/, '');
    const i18n = {
        sendCode: @json(__('ui.send_confirmation_code')),
        verifyCreate: @json(__('ui.verify_create_account')),
        sendingCode: @json(__('ui.sending_code')),
        couldNotSendCode: @json(__('ui.could_not_send_code')),
        namePhoneRequired: @json(__('ui.name_phone_required')),
        enterConfirmationCode: @json(__('ui.enter_confirmation_code')),
        passwordRequired: @json(__('ui.password_required')),
        passwordMismatch: @json(__('ui.password_mismatch')),
        invalidCode: @json(__('ui.invalid_code')),
        couldNotVerifyCode: @json(__('ui.could_not_verify_code')),
    };

    return {
        name: @json(old('name', '')),
        phoneLocal: localFromOld,
        email: @json(old('email', '')),
        password: '',
        passwordConfirm: '',
        otpSent: false,
        otpCode: '',
        otpToken: @json(old('otp_token', '')),
        otpHint: '',
        error: '',
        busy: false,
        i18n,
        get fullPhone() {
            const local = String(this.phoneLocal || '').replace(/\D/g, '').replace(/^0+/, '');
            return local ? '+252' + local : '';
        },
        otpHeaders() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            };
        },
        backToDetails() {
            this.otpSent = false;
            this.otpCode = '';
            this.otpToken = '';
            this.otpHint = '';
            this.error = '';
        },
        async sendCode() {
            if (!String(this.name || '').trim() || !this.fullPhone) {
                this.error = i18n.namePhoneRequired;
                return;
            }
            if (!String(this.password || '')) {
                this.error = i18n.passwordRequired;
                return;
            }
            if (String(this.password) !== String(this.passwordConfirm || '')) {
                this.error = i18n.passwordMismatch;
                return;
            }
            if (String(this.password).length < 8) {
                this.error = i18n.passwordRequired;
                return;
            }
            await this.send();
        },
        async send() {
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(otpSendUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: this.otpHeaders(),
                    body: JSON.stringify({ phone: this.fullPhone, purpose: 'register' }),
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
                    ? ('Testing code: ' + body.debug_code)
                    : (body.message || 'Code sent.');
                return true;
            } catch (e) {
                this.error = e.message || i18n.couldNotSendCode;
                return false;
            } finally {
                this.busy = false;
            }
        },
        async resend() {
            await this.send();
        },
        async verify() {
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(otpVerifyUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: this.otpHeaders(),
                    body: JSON.stringify({
                        phone: this.fullPhone,
                        purpose: 'register',
                        otp: String(this.otpCode).trim(),
                    }),
                });
                const text = await res.text();
                let body = {};
                try { body = text ? JSON.parse(text) : {}; } catch (_) {
                    this.error = i18n.couldNotVerifyCode + ' (' + res.status + ').';
                    return false;
                }
                if (!res.ok) {
                    this.error = body.errors?.otp?.[0] || body.message || i18n.invalidCode;
                    return false;
                }
                this.otpToken = body.otp_token || '';
                return !!this.otpToken;
            } catch (e) {
                this.error = e.message || i18n.couldNotVerifyCode;
                return false;
            } finally {
                this.busy = false;
            }
        },
        async onSubmit() {
            if (!String(this.otpCode || '').trim()) {
                this.error = i18n.enterConfirmationCode;
                return;
            }

            const form = this.$el;
            if (!this.otpToken) {
                const ok = await this.verify();
                if (!ok) return;
            }

            const set = (name, value) => {
                const el = form.querySelector('input[name="' + name + '"]');
                if (el) el.value = value == null ? '' : String(value);
            };
            set('otp_token', this.otpToken);
            set('name', this.name);
            set('phone', this.fullPhone);
            set('email', this.email);
            set('password', this.password);
            set('password_confirmation', this.passwordConfirm);
            form.submit();
        },
    };
}
</script>
@endsection
