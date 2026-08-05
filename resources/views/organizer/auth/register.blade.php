@extends('layouts.organizer-auth')
@section('title', 'Register')
@section('subtitle', 'Apply to sell tickets — identity verification required')
@section('card_width', 'max-w-xl')

@section('content')
@php
    $startStep = 1;
    if ($errors->hasAny(['id_type', 'id_number', 'id_document_front', 'id_document_back', 'business_license', 'terms'])) {
        $startStep = 3;
    } elseif ($errors->hasAny(['business_name', 'business_phone', 'city', 'business_description'])) {
        $startStep = 2;
    }
@endphp

<form
    method="POST"
    action="{{ route('organizer.register') }}"
    enctype="multipart/form-data"
    class="space-y-5"
    x-data="{
        step: {{ $startStep }},
        idType: @js(old('id_type', 'national_id')),
        frontName: '',
        backName: '',
        licenseName: '',
        go(n) { this.step = n; window.scrollTo({ top: 0, behavior: 'smooth' }); }
    }"
>
    @csrf

    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide">
        <template x-for="n in [1,2,3]" :key="n">
            <div class="flex items-center gap-2 flex-1">
                <div
                    class="w-7 h-7 rounded-full flex items-center justify-center border text-xs"
                    :class="step >= n ? 'bg-brand text-white border-brand' : 'bg-slate-50 text-mute border-slate-200'"
                    x-text="n"
                ></div>
                <span class="hidden sm:inline text-mute" x-text="n === 1 ? 'Account' : n === 2 ? 'Business' : 'Identity'"></span>
                <div class="flex-1 h-px bg-slate-100" x-show="n < 3"></div>
            </div>
        </template>
    </div>

    <p class="text-xs text-mute leading-relaxed">
        Applications stay <strong class="text-ink">pending</strong> until an admin reviews your business details and ID documents.
    </p>

    {{-- Step 1: Account --}}
    <div x-show="step === 1" x-cloak class="space-y-3">
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Your full name</label>
            <input name="name" value="{{ old('name') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('name') border-red-300 @enderror">
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('email') border-red-300 @enderror">
            @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Phone</label>
            <input name="phone" value="{{ old('phone') }}" required placeholder="+252…" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('phone') border-red-300 @enderror">
            @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Password</label>
                <input type="password" name="password" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('password') border-red-300 @enderror">
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Confirm password</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
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
        <div class="flex gap-2">
            <button type="button" @click="go(1)" class="flex-1 rounded-xl border border-slate-200 bg-white font-bold py-3.5 text-sm text-mute hover:text-ink">Back</button>
            <button type="button" @click="go(3)" class="flex-[2] rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">Continue</button>
        </div>
    </div>

    {{-- Step 3: Identity --}}
    <div x-show="step === 3" x-cloak class="space-y-3">
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">ID type</label>
            <select name="id_type" x-model="idType" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('id_type') border-red-300 @enderror">
                @foreach($idTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('id_type', 'national_id') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('id_type')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">ID number</label>
            <input name="id_number" value="{{ old('id_number') }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand @error('id_number') border-red-300 @enderror">
            @error('id_number')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">ID document — front <span class="text-red-500">*</span></label>
            <label class="flex flex-col items-start gap-1 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 cursor-pointer hover:border-brand">
                <span class="text-sm font-semibold text-ink">Upload JPG, PNG, or PDF (max 5MB)</span>
                <span class="text-xs text-mute" x-text="frontName || 'No file selected'"></span>
                <input type="file" name="id_document_front" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" required class="sr-only" @change="frontName = $event.target.files[0]?.name || ''">
            </label>
            @error('id_document_front')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div x-show="idType === 'national_id'">
            <label class="text-xs font-bold text-mute block mb-1.5">ID document — back <span class="text-red-500">*</span></label>
            <label class="flex flex-col items-start gap-1 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 cursor-pointer hover:border-brand">
                <span class="text-sm font-semibold text-ink">Upload back of national ID</span>
                <span class="text-xs text-mute" x-text="backName || 'No file selected'"></span>
                <input type="file" name="id_document_back" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" class="sr-only" :required="idType === 'national_id'" @change="backName = $event.target.files[0]?.name || ''">
            </label>
            @error('id_document_back')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Business license <span class="font-normal">(optional)</span></label>
            <label class="flex flex-col items-start gap-1 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 cursor-pointer hover:border-brand">
                <span class="text-sm font-semibold text-ink">Upload license if you have one</span>
                <span class="text-xs text-mute" x-text="licenseName || 'No file selected'"></span>
                <input type="file" name="business_license" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" class="sr-only" @change="licenseName = $event.target.files[0]?.name || ''">
            </label>
            @error('business_license')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-start gap-2 text-xs text-mute leading-relaxed cursor-pointer">
            <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand" @checked(old('terms'))>
            <span>I confirm these documents are genuine and I accept that Ekaadh will review my application before I can sell tickets.</span>
        </label>
        @error('terms')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

        <div class="flex gap-2 pt-1">
            <button type="button" @click="go(2)" class="flex-1 rounded-xl border border-slate-200 bg-white font-bold py-3.5 text-sm text-mute hover:text-ink">Back</button>
            <button type="submit" class="flex-[2] rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">Submit application</button>
        </div>
    </div>
</form>

<p class="text-center text-sm text-mute mt-5">
    Already have an account?
    <a href="{{ route('organizer.login') }}" class="font-bold text-brand">Sign in</a>
</p>

<style>[x-cloak]{display:none!important}</style>
@endsection
