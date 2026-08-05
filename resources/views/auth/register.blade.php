@extends('layouts.customer-auth')
@section('title', __('ui.create_account'))
@section('subtitle', __('ui.create_account_subtitle'))

@section('content')
<form method="POST" action="{{ route('customer.register') }}" class="space-y-3" x-data="registerOtp()" @submit="onSubmit">
    @csrf
    <input type="hidden" name="otp_token" x-model="otpToken">
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.your_name') }}</label>
        <input name="name" value="{{ old('name') }}" required x-model="name"
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.phone') }}</label>
        <div class="flex">
            <span class="flex items-center px-3 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-sm text-mute shrink-0">+252</span>
            <input type="tel" x-model="phoneLocal" required placeholder="61 234 5678"
                class="flex-1 rounded-r-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
        </div>
        <input type="hidden" name="phone" :value="fullPhone">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.email') }} <span class="font-medium text-slate-400">{{ __('ui.optional') }}</span></label>
        <input type="email" name="email" value="{{ old('email') }}"
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.password') }}</label>
        <input type="password" name="password" required
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.confirm_password') }}</label>
        <input type="password" name="password_confirmation" required
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>

    <div x-show="otpSent" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
        <p class="text-xs text-mute">
            {{ __('ui.confirmation_code') }}
            <span class="block text-brand font-semibold mt-1" x-show="otpHint" x-text="otpHint"></span>
        </p>
        <input
            type="text"
            inputmode="numeric"
            maxlength="6"
            x-model="otpCode"
            placeholder="123456"
            class="w-full rounded-xl bg-white border border-slate-200 px-4 py-3 text-sm tracking-[0.35em] text-center font-bold outline-none focus:border-brand"
        >
        <button type="button" @click="resend" :disabled="busy" class="text-xs font-bold text-brand hover:underline">{{ __('ui.resend_code') }}</button>
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
        <span x-text="busy ? i18n.sendingCode : (otpSent ? i18n.verifyCreate : i18n.sendCode)"></span>
    </button>
</form>
<p class="text-center text-sm text-mute mt-5">
    {{ __('ui.already_have_account') }}
    <a href="{{ route('customer.login') }}" class="font-bold text-brand">{{ __('ui.sign_in') }}</a>
</p>

<style>[x-cloak]{display:none!important}</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function registerOtp() {
    const csrf = document.querySelector('input[name="_token"]')?.value || '';
    const otpSendUrl = @json(url('/api/v1/otp/send'));
    const otpVerifyUrl = @json(url('/api/v1/otp/verify'));
    const oldPhone = @json(old('phone', ''));
    const localFromOld = String(oldPhone || '').replace(/^\+?252/, '').replace(/\D/g, '');
    const i18n = {
        sendCode: @json(__('ui.send_confirmation_code')),
        verifyCreate: @json(__('ui.verify_create_account')),
        sendingCode: @json(__('ui.sending_code')),
        couldNotSendCode: @json(__('ui.could_not_send_code')),
        namePhoneRequired: @json(__('ui.name_phone_required')),
    };

    return {
        name: @json(old('name', '')),
        phoneLocal: localFromOld,
        otpSent: false,
        otpCode: '',
        otpToken: @json(old('otp_token', '')),
        otpHint: '',
        error: '',
        busy: false,
        i18n,
        get fullPhone() {
            const local = String(this.phoneLocal || '').replace(/\D/g, '');
            return local ? '+252' + local : '';
        },
        async send() {
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(otpSendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
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
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        phone: this.fullPhone,
                        purpose: 'register',
                        otp: String(this.otpCode).trim(),
                    }),
                });
                const text = await res.text();
                let body = {};
                try { body = text ? JSON.parse(text) : {}; } catch (_) {
                    this.error = 'Could not verify code (' + res.status + ').';
                    return false;
                }
                if (!res.ok) {
                    this.error = body.errors?.otp?.[0] || body.message || 'Invalid code.';
                    return false;
                }
                this.otpToken = body.otp_token;
                return true;
            } catch (e) {
                this.error = e.message || 'Could not verify code.';
                return false;
            } finally {
                this.busy = false;
            }
        },
        async onSubmit(e) {
            if (!this.fullPhone) {
                e.preventDefault();
                this.error = i18n.namePhoneRequired;
                return;
            }
            if (!this.otpSent) {
                e.preventDefault();
                await this.send();
                return;
            }
            if (!this.otpToken) {
                e.preventDefault();
                const form = e.target;
                const ok = await this.verify();
                if (ok) {
                    const hidden = form.querySelector('input[name="otp_token"]');
                    if (hidden) hidden.value = this.otpToken;
                    form.submit();
                }
            }
        },
    };
}
</script>
@endsection
