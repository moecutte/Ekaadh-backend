@extends('layouts.organizer-auth')
@section('title', 'Register')
@section('subtitle', 'Create your organizer account and start selling tickets')
@section('card_width', 'max-w-xl')

@section('content')
@php
    $startStep = 1;
    if ($errors->hasAny(['business_name', 'business_phone', 'city', 'business_description', 'terms'])) {
        $startStep = 2;
    }
@endphp

<form
    method="POST"
    action="{{ route('organizer.register') }}"
    class="space-y-5"
    x-data="{
        step: {{ $startStep }},
        go(n) { this.step = n; window.scrollTo({ top: 0, behavior: 'smooth' }); }
    }"
>
    @csrf

    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide">
        <template x-for="n in [1,2]" :key="n">
            <div class="flex items-center gap-2 flex-1">
                <div
                    class="w-7 h-7 rounded-full flex items-center justify-center border text-xs"
                    :class="step >= n ? 'bg-brand text-white border-brand' : 'bg-slate-50 text-mute border-slate-200'"
                    x-text="n"
                ></div>
                <span class="hidden sm:inline text-mute" x-text="n === 1 ? 'Account' : 'Business'"></span>
                <div class="flex-1 h-px bg-slate-100" x-show="n < 2"></div>
            </div>
        </template>
    </div>

    <p class="text-xs text-mute leading-relaxed">
        Your account is <strong class="text-ink">ready immediately</strong> — no ID upload and no admin review.
    </p>

    {{-- Step 1: Account --}}
    <div x-show="step === 1" x-cloak class="space-y-3">
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Your full name</label>
            <input name="name" value="{{ old('name') }}" :required="step === 1" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('name') border-red-300 @enderror">
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" :required="step === 1" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('email') border-red-300 @enderror">
            @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Phone</label>
            <input name="phone" value="{{ old('phone') }}" :required="step === 1" placeholder="+252…" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('phone') border-red-300 @enderror">
            @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Password</label>
                <input type="password" name="password" :required="step === 1" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('password') border-red-300 @enderror">
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Confirm password</label>
                <input type="password" name="password_confirmation" :required="step === 1" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
            </div>
        </div>
        <button type="button" @click="go(2)" class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark mt-2">Continue</button>
    </div>

    {{-- Step 2: Business --}}
    <div x-show="step === 2" x-cloak class="space-y-3">
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Business / brand name</label>
            <input name="business_name" value="{{ old('business_name') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('business_name') border-red-300 @enderror">
            @error('business_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Business phone <span class="font-normal">(optional)</span></label>
            <input name="business_phone" value="{{ old('business_phone') }}" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">City</label>
            <input name="city" value="{{ old('city') }}" required placeholder="Hargeisa, Mogadishu…" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('city') border-red-300 @enderror">
            @error('city')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">About your business</label>
            <textarea name="business_description" rows="4" required maxlength="500" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand resize-y @error('business_description') border-red-300 @enderror" placeholder="What events do you organize? Who is your audience?">{{ old('business_description') }}</textarea>
            @error('business_description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-start gap-2 text-xs text-mute leading-relaxed cursor-pointer">
            <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand" @checked(old('terms'))>
            <span>I agree to the <a href="{{ route('terms') }}" target="_blank" class="font-bold text-brand hover:underline">Ekaadh terms</a> and confirm that these details are accurate.</span>
        </label>
        @error('terms')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

        <div class="flex gap-2">
            <button type="button" @click="go(1)" class="flex-1 rounded-xl border border-slate-200 bg-white font-bold py-3.5 text-sm text-mute hover:text-ink">Back</button>
            <button type="submit" class="flex-[2] rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">Create account</button>
        </div>
    </div>
</form>

<p class="text-center text-sm text-mute mt-5">
    Already have an account?
    <a href="{{ route('organizer.login') }}" class="font-bold text-brand">Sign in</a>
</p>

<style>[x-cloak]{display:none!important}</style>
@endsection
