@extends('layouts.app')

@section('title', __('ui.terms_title'))

@push('head')
<meta name="description" content="{{ __('ui.terms_meta') }}">
@endpush

@section('content')
<div class="bg-white border-b border-slate-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand mb-3">{{ __('ui.terms_kicker') }}</p>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-ink tracking-tight mb-4">{{ __('ui.terms_title') }}</h1>
        <p class="text-mute text-base sm:text-lg leading-relaxed mb-3">{{ __('ui.terms_intro') }}</p>
        <p class="text-sm text-mute">{{ __('ui.terms_updated') }}</p>
    </div>
</div>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-6">
    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.terms_service_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.terms_service_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.terms_accounts_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.terms_accounts_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.terms_tickets_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.terms_tickets_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.terms_events_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.terms_events_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.terms_conduct_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.terms_conduct_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.terms_liability_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.terms_liability_body') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.terms_law_title') }}</h2>
        <p class="text-sm text-mute leading-relaxed mb-4">{{ __('ui.terms_law_body') }}</p>
        <a href="mailto:{{ $supportEmail }}?subject={{ rawurlencode('Terms question') }}"
           class="inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-bold text-sm px-5 py-2.5 rounded-xl transition-colors">
            {{ $supportEmail }}
        </a>
    </section>
</div>
@endsection
