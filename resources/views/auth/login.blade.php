@extends('layouts.customer-auth')
@section('title', __('ui.sign_in_title'))
@section('subtitle', __('ui.sign_in_subtitle'))

@section('content')
<form method="POST" action="{{ route('customer.login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.phone_or_email') }}</label>
        <input name="login" value="{{ old('login') }}" required autofocus placeholder="+252 61 234 5678"
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.password') }}</label>
        <input type="password" name="password" required
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <label class="flex items-center gap-2 text-xs text-mute"><input type="checkbox" name="remember" value="1"> {{ __('ui.remember_me') }}</label>
    <button class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">{{ __('ui.sign_in') }}</button>
</form>
<p class="text-center text-sm text-mute mt-5">
    {{ __('ui.new_here') }}
    <a href="{{ route('customer.register') }}" class="font-bold text-brand">{{ __('ui.create_an_account') }}</a>
</p>
<p class="text-center text-xs text-mute mt-3">
    {{ __('ui.or') }}
    <a href="{{ route('tickets.index') }}" class="font-semibold text-ink hover:text-brand">{{ __('ui.lookup_guest') }}</a>
</p>
@endsection
