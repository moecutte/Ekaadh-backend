@extends('layouts.app')

@section('title', __('ui.privacy_title'))

@push('head')
<meta name="description" content="{{ __('ui.privacy_meta') }}">
@endpush

@section('content')
<div class="bg-white border-b border-slate-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand mb-3">{{ __('ui.privacy_kicker') }}</p>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-ink tracking-tight mb-4">{{ __('ui.privacy_title') }}</h1>
        <p class="text-mute text-base sm:text-lg leading-relaxed mb-3">{{ __('ui.privacy_intro') }}</p>
        <p class="text-sm text-mute">{{ __('ui.privacy_updated') }}</p>
    </div>
</div>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-6">
    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_who_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.privacy_who_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_collect_title') }}</h2>
        <p class="text-sm text-mute mb-4">{{ __('ui.privacy_collect_lead') }}</p>
        <ul class="list-disc list-inside space-y-2 text-sm text-ink leading-relaxed">
            <li>{{ __('ui.privacy_collect_1') }}</li>
            <li>{{ __('ui.privacy_collect_2') }}</li>
            <li>{{ __('ui.privacy_collect_3') }}</li>
            <li>{{ __('ui.privacy_collect_4') }}</li>
            <li>{{ __('ui.privacy_collect_5') }}</li>
            <li>{{ __('ui.privacy_collect_6') }}</li>
        </ul>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_use_title') }}</h2>
        <p class="text-sm text-mute mb-4">{{ __('ui.privacy_use_lead') }}</p>
        <ul class="list-disc list-inside space-y-2 text-sm text-ink leading-relaxed">
            <li>{{ __('ui.privacy_use_1') }}</li>
            <li>{{ __('ui.privacy_use_2') }}</li>
            <li>{{ __('ui.privacy_use_3') }}</li>
            <li>{{ __('ui.privacy_use_4') }}</li>
            <li>{{ __('ui.privacy_use_5') }}</li>
        </ul>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_share_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.privacy_share_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_security_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.privacy_security_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_retain_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.privacy_retain_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_rights_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed mb-3">{{ __('ui.privacy_rights_body') }}</p>
        <a href="{{ route('account-deletion') }}" class="text-sm font-bold text-brand hover:underline">{{ __('ui.delete_account_footer') }}</a>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_children_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.privacy_children_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_changes_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.privacy_changes_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.privacy_contact_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed mb-4">{{ __('ui.privacy_contact_body') }}</p>
        <a href="mailto:{{ $supportEmail }}?subject={{ rawurlencode('Privacy question') }}"
           class="inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-bold text-sm px-5 py-2.5 rounded-xl transition-colors">
            {{ $supportEmail }}
        </a>
    </section>
</div>
@endsection
