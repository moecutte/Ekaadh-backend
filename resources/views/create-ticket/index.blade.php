@extends('layouts.app')

@section('title', __('ui.create_ticket'))

@section('content')
@php
    $user = auth()->user();
    $isCustomer = $user && $user->isCustomer();
    $ctaUrl = $isCustomer
        ? route('private-events.create')
        : route('customer.register');
    $ctaLabel = $isCustomer ? __('ui.create_your_ticket') : __('ui.sign_up_create_tickets');
    $secondaryUrl = $isCustomer
        ? route('private-events.index')
        : route('customer.login');
    $secondaryLabel = $isCustomer ? __('ui.my_tickets') : __('ui.already_have_account_sign_in');

    $features = [
        ['title' => __('ui.ct_feat1_title'), 'desc' => __('ui.ct_feat1_desc'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
        ['title' => __('ui.ct_feat2_title'), 'desc' => __('ui.ct_feat2_desc'), 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['title' => __('ui.ct_feat3_title'), 'desc' => __('ui.ct_feat3_desc'), 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ['title' => __('ui.ct_feat4_title'), 'desc' => __('ui.ct_feat4_desc'), 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        ['title' => __('ui.ct_feat5_title'), 'desc' => __('ui.ct_feat5_desc'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['title' => __('ui.ct_feat6_title'), 'desc' => __('ui.ct_feat6_desc'), 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
    ];

    $steps = [
        ['01', __('ui.ct_step1_title'), __('ui.ct_step1_desc')],
        ['02', __('ui.ct_step2_title'), __('ui.ct_step2_desc')],
        ['03', __('ui.ct_step3_title'), __('ui.ct_step3_desc')],
        ['04', __('ui.ct_step4_title'), __('ui.ct_step4_desc')],
    ];
@endphp

{{-- Hero --}}
<section class="relative bg-ink overflow-hidden">
    <div class="absolute inset-0">
        <img
            src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=1600&h=700&fit=crop&auto=format"
            alt="Private celebration invitation"
            class="w-full h-full object-cover opacity-20"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-transparent"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 bg-brand/20 border border-brand/30 text-brand text-xs font-bold px-3 py-1.5 rounded-full mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                {{ __('ui.private_invites_hosts_badge') }}
            </div>
            <h1 class="text-5xl sm:text-6xl font-extrabold text-white leading-[1.1] mb-5">
                {{ __('ui.create_ticket_hero_1') }}<br>
                <span class="text-brand">{{ __('ui.create_ticket_hero_2') }}</span>
            </h1>
            <p class="text-slate-300 text-lg mb-8 leading-relaxed max-w-xl">
                {{ __('ui.create_ticket_hero_sub') }}
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $ctaUrl }}" class="bg-brand hover:bg-brand-dark text-white font-extrabold px-7 py-3.5 rounded-xl transition-colors text-sm">
                    {{ $ctaLabel }}
                </a>
                <a href="#how-it-works" class="border border-white/30 hover:border-white/60 text-white font-semibold px-7 py-3.5 rounded-xl transition-colors text-sm">
                    {{ __('ui.see_how_it_works') }}
                </a>
            </div>

            <div class="flex flex-wrap gap-8 mt-12 pt-8 border-t border-white/10">
                @foreach([
                    [__('ui.ct_stat_capacity'), __('ui.ct_stat_capacity_sub')],
                    [__('ui.ct_stat_invite'), __('ui.ct_stat_invite_sub')],
                    [__('ui.ct_stat_qr'), __('ui.ct_stat_qr_sub')],
                    [__('ui.ct_stat_pay'), __('ui.ct_stat_pay_sub')],
                ] as [$val, $label])
                    <div>
                        <p class="text-lg font-extrabold text-brand">{{ $val }}</p>
                        <p class="text-sm text-slate-400 mt-0.5">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <section class="py-10">
        <div class="rounded-2xl border border-amber-100 bg-amber-50 px-5 py-4 text-sm text-amber-950 leading-relaxed">
            <strong class="font-extrabold">{{ __('ui.ct_vs_title') }}</strong>
            <span class="text-amber-900/90">
                <a href="{{ route('organizers') }}" class="underline font-semibold">{{ __('ui.create_event') }}</a>
                {{ __('ui.ct_vs_body') }}
            </span>
        </div>
    </section>

    <section class="pb-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-ink mb-3">
                {{ __('ui.ct_features_title') }}
            </h2>
            <p class="text-mute max-w-xl mx-auto text-sm leading-relaxed">
                {{ __('ui.ct_features_sub') }}
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($features as $feature)
                <div class="bg-white rounded-2xl border border-slate-100 p-6 hover:border-brand/30 hover:shadow-sm transition-all">
                    <div class="w-11 h-11 bg-brand/10 rounded-xl flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}"/></svg>
                    </div>
                    <h3 class="font-extrabold text-ink text-base mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-mute text-sm leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="how-it-works" class="py-8 mb-8 scroll-mt-24">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-ink mb-3">
                {{ __('ui.ct_steps_title') }}
            </h2>
            <p class="text-mute text-sm">
                {{ __('ui.ct_steps_sub') }}
            </p>
        </div>
        <div class="relative">
            <div class="hidden md:block absolute top-8 left-[calc(12.5%+1rem)] right-[calc(12.5%+1rem)] h-0.5 bg-brand/20"></div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach($steps as [$num, $title, $desc])
                    <div class="text-center relative">
                        <div class="w-16 h-16 bg-brand text-white font-extrabold text-xl rounded-2xl flex items-center justify-center mx-auto mb-4 relative z-10">
                            {{ $num }}
                        </div>
                        <h3 class="font-extrabold text-ink text-base mb-2">{{ $title }}</h3>
                        <p class="text-mute text-sm leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mb-16">
        <div class="bg-brand rounded-2xl p-8 sm:p-12 text-center">
            <h2 class="text-3xl font-extrabold text-white mb-3">
                {{ __('ui.ct_cta_title') }}
            </h2>
            <p class="text-white/80 text-sm mb-7 max-w-md mx-auto">
                {{ __('ui.ct_cta_sub') }}
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ $ctaUrl }}" class="bg-white text-brand font-extrabold px-8 py-3.5 rounded-xl text-sm hover:bg-slate-50 transition-colors">
                    {{ $ctaLabel }}
                </a>
                <a href="{{ $secondaryUrl }}" class="border border-white/40 text-white font-semibold px-8 py-3.5 rounded-xl text-sm hover:bg-white/10 transition-colors">
                    {{ $secondaryLabel }}
                </a>
            </div>
        </div>
    </section>

</div>
@endsection
