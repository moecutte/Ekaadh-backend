@extends('layouts.organizer-auth')
@section('title', 'Sign in')
@section('subtitle', 'Sign in to manage your events')

@section('content')
<form method="POST" action="{{ route('organizer.login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Email or phone</label>
        <input name="login" value="{{ old('login') }}" required autocomplete="username" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Password</label>
        <input type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <label class="flex items-center gap-2 text-xs text-mute"><input type="checkbox" name="remember" value="1"> Remember me</label>
    <button class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">Sign in</button>
</form>
<p class="text-center text-sm text-mute mt-5">
    New organizer?
    <a href="{{ route('organizer.register') }}" class="font-bold text-brand">Apply here</a>
</p>
@endsection
