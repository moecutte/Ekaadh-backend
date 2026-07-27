@extends('layouts.customer-auth')
@section('title', 'Sign in')
@section('subtitle', 'Sign in to view your tickets')

@section('content')
<form method="POST" action="{{ route('customer.login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Phone or email</label>
        <input name="login" value="{{ old('login') }}" required autofocus placeholder="+252 61 234 5678"
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Password</label>
        <input type="password" name="password" required
            class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <label class="flex items-center gap-2 text-xs text-mute"><input type="checkbox" name="remember" value="1"> Remember me</label>
    <button class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">Sign in</button>
</form>
<p class="text-center text-sm text-mute mt-5">
    New here?
    <a href="{{ route('customer.register') }}" class="font-bold text-brand">Create an account</a>
</p>
<p class="text-center text-xs text-mute mt-3">
    Or
    <a href="{{ route('tickets.index') }}" class="font-semibold text-ink hover:text-brand">look up tickets as a guest</a>
</p>
@endsection
