@extends('layouts.organizer')
@section('title', 'Update application')
@section('heading', 'Update application')

@section('content')
@if($profile->approval_status === 'rejected')
    <div class="mb-5 rounded-xl bg-red-50 border border-red-100 text-red-700 p-4 text-sm">
        <strong>Previously rejected.</strong> {{ $profile->rejection_reason ?: 'Update your details and documents, then resubmit.' }}
    </div>
@else
    <div class="mb-5 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 p-4 text-sm">
        Your application is pending. You can update business details or replace ID documents below.
    </div>
@endif

<form method="POST" action="{{ route('organizer.application.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-5" x-data="{ idType: @js(old('id_type', $profile->documents['id_type'] ?? 'national_id')) }">
    @csrf

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
        <h3 class="text-sm font-bold">Business</h3>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Business name</label>
            <input name="business_name" value="{{ old('business_name', $profile->business_name) }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
            @error('business_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Business phone</label>
            <input name="business_phone" value="{{ old('business_phone', $profile->business_phone) }}" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">City</label>
            <input name="city" value="{{ old('city', $profile->city) }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
            @error('city')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">About your business</label>
            <textarea name="business_description" rows="4" required maxlength="500" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand resize-y">{{ old('business_description', $profile->business_description) }}</textarea>
            @error('business_description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
        <h3 class="text-sm font-bold">Identity documents</h3>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">ID type</label>
            <select name="id_type" x-model="idType" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm">
                @foreach($idTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('id_type', $profile->documents['id_type'] ?? 'national_id') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">ID number</label>
            <input name="id_number" value="{{ old('id_number', $profile->id_number) }}" required class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm">
            @error('id_number')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        @if($profile->documentUrl('id_front'))
            <p class="text-xs text-mute">Current ID front: <a href="{{ $profile->documentUrl('id_front') }}" target="_blank" class="text-brand font-semibold">View</a> — upload a new file only to replace it.</p>
        @endif
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">ID front {{ $profile->hasIdentityDocuments() ? '(optional replace)' : '' }}</label>
            <input type="file" name="id_document_front" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" class="w-full text-sm">
            @error('id_document_front')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div x-show="idType === 'national_id'">
            @if($profile->documentUrl('id_back'))
                <p class="text-xs text-mute mb-1">Current ID back: <a href="{{ $profile->documentUrl('id_back') }}" target="_blank" class="text-brand font-semibold">View</a></p>
            @endif
            <label class="text-xs font-bold text-mute block mb-1.5">ID back</label>
            <input type="file" name="id_document_back" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" class="w-full text-sm">
            @error('id_document_back')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            @if($profile->documentUrl('business_license'))
                <p class="text-xs text-mute mb-1">Current license: <a href="{{ $profile->documentUrl('business_license') }}" target="_blank" class="text-brand font-semibold">View</a></p>
            @endif
            <label class="text-xs font-bold text-mute block mb-1.5">Business license (optional)</label>
            <input type="file" name="business_license" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" class="w-full text-sm">
        </div>

        <label class="flex items-start gap-2 text-xs text-mute leading-relaxed cursor-pointer">
            <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
            <span>I confirm these documents are genuine and ready for admin review.</span>
        </label>
        @error('terms')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-2">
        <a href="{{ route('organizer.dashboard') }}" class="flex-1 text-center rounded-xl border border-slate-200 bg-white font-bold py-3.5 text-sm text-mute hover:text-ink">Cancel</a>
        <button type="submit" class="flex-[2] rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">Resubmit application</button>
    </div>
</form>
@endsection
