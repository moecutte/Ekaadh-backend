@extends('layouts.admin')
@section('title', 'Profile')
@section('heading', 'Profile')

@section('content')
@if($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-5" x-data="profilePhoto()">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-sm font-bold mb-1">Photo</h3>
        <p class="text-xs text-mute mb-4">Shown in the admin sidebar and header.</p>
        @include('partials.profile-photo-field', ['currentUrl' => $user->avatar])
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold">Account</h3>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Name</label>
            <input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Phone</label>
                <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold">Password</h3>
        <p class="text-xs text-mute">Leave blank to keep your current password.</p>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Current password</label>
            <input type="password" name="current_password" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand" autocomplete="current-password">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">New password</label>
                <input type="password" name="password" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand" autocomplete="new-password">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Confirm new password</label>
                <input type="password" name="password_confirmation" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm outline-none focus:border-brand" autocomplete="new-password">
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button class="px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">Save profile</button>
    </div>
</form>

@include('partials.profile-photo-script', ['currentUrl' => $user->avatar])
@endsection
