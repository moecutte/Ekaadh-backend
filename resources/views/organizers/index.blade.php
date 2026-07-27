@extends('layouts.app')

@section('title', 'Create Event')

@section('content')
@php
    $registerUrl = auth()->check()
        ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('organizer.dashboard'))
        : route('organizer.register');
    $registerLabel = auth()->check() ? 'Go to Dashboard' : 'Create Your First Event';

    $features = [
        [
            'title' => 'Go Live in Minutes',
            'desc' => 'Create your event, set ticket tiers, and start selling in under 10 minutes. No technical knowledge needed.',
            'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
        ],
        [
            'title' => 'Zaad & eDahab Payouts',
            'desc' => 'Receive your ticket revenue directly to your Zaad or eDahab account within 24 hours of your event.',
            'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
        ],
        [
            'title' => 'Real-Time Attendee Data',
            'desc' => 'Track ticket sales, revenue, and attendee check-ins from your live dashboard as they happen.',
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        ],
        [
            'title' => 'Secure QR Scanning',
            'desc' => 'Every ticket has a unique QR code. Use our free scanner app at the door to validate tickets instantly.',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        ],
        [
            'title' => 'Built-In Promotion',
            'desc' => 'Your event is automatically listed on the Ekaadh homepage and browse page, reaching thousands of buyers.',
            'icon' => 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z',
        ],
        [
            'title' => 'Automated Attendee Comms',
            'desc' => 'We handle confirmation SMS, WhatsApp delivery, and reminder messages to all your ticket buyers automatically.',
            'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        ],
    ];

    $steps = [
        ['01', 'Create Your Account', 'Sign up with your phone number. No paperwork, no waiting — your organiser account is active immediately.'],
        ['02', 'Set Up Your Event', 'Add your event details, upload a cover photo, create ticket tiers with prices, and set your capacity.'],
        ['03', 'Publish & Share', 'Go live with one click. Share your event link on WhatsApp, social media, or let Ekaadh\'s audience find you.'],
        ['04', 'Get Paid', 'Funds land in your Zaad or eDahab account within 24 hours after your event. No hidden fees, no delays.'],
    ];

    $plans = $packages->map(fn ($package) => [
        'name' => $package->name,
        'price' => $package->displayPrice(),
        'period' => $package->displayPeriod(),
        'desc' => $package->description,
        'features' => $package->features ?? [],
        'cta' => $package->cta_label ?: 'Get Started',
        'highlight' => (bool) $package->is_highlighted,
        'billing_type' => $package->billing_type,
    ]);
@endphp

{{-- Hero --}}
<section class="relative bg-ink overflow-hidden">
    <div class="absolute inset-0">
        <img
            src="https://images.unsplash.com/photo-1550305080-4e029753abcf?w=1600&h=700&fit=crop&auto=format"
            alt="Event organiser at conference"
            class="w-full h-full object-cover opacity-20"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-transparent"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 bg-brand/20 border border-brand/30 text-brand text-xs font-bold px-3 py-1.5 rounded-full mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                For Event Organisers
            </div>
            <h1 class="text-5xl sm:text-6xl font-extrabold text-white leading-[1.1] mb-5">
                Sell Tickets to<br>
                <span class="text-brand">Any Event</span>
            </h1>
            <p class="text-slate-300 text-lg mb-8 leading-relaxed max-w-xl">
                Ekaadh gives you everything you need to create, promote, and manage
                events across Somaliland. Collect payments instantly via Zaad and eDahab.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $registerUrl }}" class="bg-brand hover:bg-brand-dark text-white font-extrabold px-7 py-3.5 rounded-xl transition-colors text-sm">
                    {{ $registerLabel }}
                </a>
                <a href="#how-it-works" class="border border-white/30 hover:border-white/60 text-white font-semibold px-7 py-3.5 rounded-xl transition-colors text-sm">
                    See How It Works
                </a>
            </div>

            <div class="flex flex-wrap gap-8 mt-12 pt-8 border-t border-white/10">
                @foreach([
                    ['2,400+', 'Tickets Sold'],
                    ['120+', 'Events Hosted'],
                    ['24h', 'Payout Time'],
                    ['0%', 'Setup Fee'],
                ] as [$val, $label])
                    <div>
                        <p class="text-2xl font-extrabold text-brand">{{ $val }}</p>
                        <p class="text-sm text-slate-400 mt-0.5">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Features --}}
    <section class="py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-ink mb-3">
                Everything You Need to Run a Successful Event
            </h2>
            <p class="text-mute max-w-xl mx-auto text-sm leading-relaxed">
                From listing to payout — Ekaadh handles the entire ticketing workflow so you can focus on putting on a great event.
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

    {{-- How it works --}}
    <section id="how-it-works" class="py-8 mb-8 scroll-mt-24">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-ink mb-3">
                From Idea to Sold-Out in 4 Steps
            </h2>
            <p class="text-mute text-sm">
                No waiting, no paperwork. Start selling today.
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

    {{-- Pricing --}}
    <section class="py-12 mb-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-ink mb-3">
                Simple, Transparent Pricing
            </h2>
            <p class="text-mute text-sm">
                Only pay when you sell tickets. No monthly subscriptions for small events.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            @forelse($plans as $plan)
                <div class="rounded-2xl border p-6 relative {{ $plan['highlight'] ? 'border-brand bg-ink shadow-2xl scale-[1.02]' : 'border-slate-100 bg-white' }}">
                    @if($plan['highlight'])
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand text-white text-xs font-extrabold px-4 py-1 rounded-full">
                            MOST POPULAR
                        </div>
                    @endif
                    <h3 class="font-extrabold text-xl mb-1 {{ $plan['highlight'] ? 'text-white' : 'text-ink' }}">{{ $plan['name'] }}</h3>
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-4xl font-extrabold {{ $plan['highlight'] ? 'text-brand' : 'text-ink' }}">{{ $plan['price'] }}</span>
                        <span class="text-sm {{ $plan['highlight'] ? 'text-slate-400' : 'text-mute' }}">/ {{ $plan['period'] }}</span>
                    </div>
                    <p class="text-sm mb-6 {{ $plan['highlight'] ? 'text-slate-400' : 'text-mute' }}">{{ $plan['desc'] }}</p>
                    <ul class="space-y-2.5 mb-7">
                        @foreach($plan['features'] as $f)
                            <li class="flex items-start gap-2.5 text-sm">
                                <span class="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 {{ $plan['highlight'] ? 'bg-brand/20' : 'bg-brand/10' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="{{ $plan['highlight'] ? 'text-slate-300' : 'text-ink' }}">{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a
                        href="{{ ($plan['billing_type'] ?? '') === 'custom' ? 'mailto:sales@ekaadh.com' : $registerUrl }}"
                        class="block w-full text-center font-bold py-3 rounded-xl text-sm transition-colors {{ $plan['highlight'] ? 'bg-brand hover:bg-brand-dark text-white' : 'border border-slate-200 text-ink hover:bg-slate-50' }}"
                    >
                        {{ auth()->check() && ($plan['billing_type'] ?? '') !== 'custom' ? 'Go to Dashboard' : $plan['cta'] }}
                    </a>
                </div>
            @empty
                <div class="md:col-span-3 text-center text-mute text-sm py-8">Pricing packages coming soon.</div>
            @endforelse
        </div>
    </section>

    {{-- Social proof --}}
    <section class="mb-16">
        <div class="bg-white rounded-2xl border border-slate-100 p-8 sm:p-10 flex flex-col sm:flex-row gap-6 items-start">
            <div class="w-14 h-14 bg-brand rounded-2xl flex items-center justify-center flex-shrink-0 text-white font-extrabold text-xl">
                AH
            </div>
            <div>
                <p class="text-ink text-lg leading-relaxed font-medium mb-3">
                    "We sold 800 tickets to our business summit in two days. Ekaadh handled everything — payments, QR codes, check-in. I couldn't believe how smooth it was."
                </p>
                <p class="text-sm font-bold text-ink">Abdirahman Hassan</p>
                <p class="text-xs text-mute">Organiser · Hargeisa Business Summit 2026</p>
            </div>
        </div>
    </section>

    {{-- Bottom CTA --}}
    <section class="mb-16">
        <div class="bg-brand rounded-2xl p-8 sm:p-12 text-center">
            <h2 class="text-3xl font-extrabold text-white mb-3">
                Ready to Sell Your First Ticket?
            </h2>
            <p class="text-white/80 text-sm mb-7 max-w-md mx-auto">
                Join over 120 organisers who trust Ekaadh to power their events across Somaliland.
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ $registerUrl }}" class="bg-white text-brand font-extrabold px-8 py-3.5 rounded-xl text-sm hover:bg-slate-50 transition-colors">
                    {{ auth()->check() ? 'Go to Dashboard' : 'Create Your Event — It\'s Free' }}
                </a>
                <a href="{{ route('events.index') }}" class="border border-white/40 text-white font-semibold px-8 py-3.5 rounded-xl text-sm hover:bg-white/10 transition-colors">
                    Browse Existing Events
                </a>
            </div>
        </div>
    </section>

</div>
@endsection
