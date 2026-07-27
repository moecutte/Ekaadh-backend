@extends('layouts.organizer-auth')
@section('title', 'Register')
@section('subtitle', 'Apply to sell tickets on Ekaadh')

@section('content')
<form method="POST" action="{{ route('organizer.register') }}" class="space-y-3">
    @csrf
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Your name</label>
        <input name="name" value="{{ old('name') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Business name</label>
        <input name="business_name" value="{{ old('business_name') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Phone</label>
        <input name="phone" value="{{ old('phone') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Business phone</label>
        <input name="business_phone" value="{{ old('business_phone') }}" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Password</label>
        <input type="password" name="password" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <div>
        <label class="text-xs font-bold text-mute block mb-1.5">Confirm password</label>
        <input type="password" name="password_confirmation" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
    </div>
    <button class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark mt-2">Submit application</button>
</form>
<p class="text-center text-sm text-mute mt-5">
    Already approved?
    <a href="{{ route('organizer.login') }}" class="font-bold text-brand">Sign in</a>
</p>
@endsection
