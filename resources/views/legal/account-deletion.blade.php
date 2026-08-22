@extends('layouts.app')

@section('title', __('ui.delete_account_title'))

@push('head')
<meta name="description" content="{{ __('ui.delete_account_meta') }}">
@endpush

@section('content')
<div class="bg-white border-b border-slate-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand mb-3">{{ __('ui.delete_account_kicker') }}</p>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-ink tracking-tight mb-4">{{ __('ui.delete_account_title') }}</h1>
        <p class="text-mute text-base sm:text-lg leading-relaxed">{{ __('ui.delete_account_intro') }}</p>
    </div>
</div>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-10">
    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.delete_account_app_title') }}</h2>
        <p class="text-sm text-mute mb-4">{{ __('ui.delete_account_app_lead') }}</p>
        <ol class="list-decimal list-inside space-y-2 text-sm text-ink leading-relaxed">
            <li>{{ __('ui.delete_account_app_1') }}</li>
            <li>{{ __('ui.delete_account_app_2') }}</li>
            <li>{{ __('ui.delete_account_app_3') }}</li>
            <li>{{ __('ui.delete_account_app_4') }}</li>
        </ol>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.delete_account_email_title') }}</h2>
        <p class="text-sm text-mute mb-4">{{ __('ui.delete_account_email_lead') }}</p>
        <p class="text-sm text-ink mb-3">{{ __('ui.delete_account_email_to') }}</p>
        <p class="mb-4">
            <a href="mailto:{{ $supportEmail }}?subject={{ rawurlencode('Delete my Ekaadh account') }}"
               class="inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-bold text-sm px-5 py-2.5 rounded-xl transition-colors">
                {{ $supportEmail }}
            </a>
        </p>
        <p class="text-sm text-mute mb-2">{{ __('ui.delete_account_email_include') }}</p>
        <ul class="list-disc list-inside space-y-1 text-sm text-ink">
            <li>{{ __('ui.delete_account_email_item_name') }}</li>
            <li>{{ __('ui.delete_account_email_item_phone') }}</li>
            <li>{{ __('ui.delete_account_email_item_email') }}</li>
        </ul>
        <p class="text-sm text-mute mt-4">{{ __('ui.delete_account_email_sla') }}</p>
    </section>

    <section class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-ink mb-3">{{ __('ui.delete_account_what_title') }}</h2>
        <p class="text-sm text-ink leading-relaxed mb-3">{{ __('ui.delete_account_what_deleted') }}</p>
        <p class="text-sm text-mute leading-relaxed">{{ __('ui.delete_account_what_kept') }}</p>
    </section>
</div>
@endsection
