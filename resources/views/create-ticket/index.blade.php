@extends('layouts.app')

@section('title', 'Create Ticket')

@section('content')
@php
    $user = auth()->user();
    $isCustomer = $user && $user->isCustomer();
    $ctaUrl = $isCustomer
        ? route('private-events.create')
        : route('customer.register');
    $ctaLabel = $isCustomer ? 'Create Your Ticket Package' : 'Sign Up & Create Tickets';
    $secondaryUrl = $isCustomer
        ? route('private-events.index')
        : route('customer.login');
    $secondaryLabel = $isCustomer ? 'My Tickets' : 'Already have an account? Sign in';

    $features = [
        [
            'title' => 'Buy Invitation Capacity',
            'desc' => 'Purchase the number of private invitation tickets you need for a wedding, dinner, or private gathering — you are buying seats for your guests, not selling to the public.',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        ],
        [
            'title' => 'Design Beautiful Invites',
            'desc' => 'Choose an admin-designed invitation template, fill in your details, and send guests a personal link with QR admission.',
            'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        [
            'title' => 'Invite by Phone',
            'desc' => 'Send invitations to guests by phone number. They get a link and QR code — no public event listing required.',
            'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
        ],
        [
            'title' => 'Pay with Zaad or eDahab',
            'desc' => 'Pay for your ticket package securely. Once paid, assign invitations until your capacity is used.',
            'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        ],
        [
            'title' => 'Track Who Is Coming',
            'desc' => 'See how many invitations you have left, resend links, update guest phones, or revoke unused invites.',
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        ],
        [
            'title' => 'Private by Default',
            'desc' => 'Your invitation is not listed on the public Browse Events page. Only people you invite can open the link.',
            'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        ],
    ];

    $steps = [
        ['01', 'Create a Customer Account', 'Sign up with your phone. This is for hosts buying private invitation tickets — not for selling public events.'],
        ['02', 'Choose Design & Capacity', 'Pick an invitation design, set how many guest tickets you need, and fill in your event details.'],
        ['03', 'Pay for Your Package', 'Complete payment with Zaad or eDahab. Your invitation capacity unlocks once payment succeeds.'],
        ['04', 'Invite Guests', 'Enter guest names and phones. Each guest receives a private invitation link and QR ticket.'],
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
                Private invitations for hosts
            </div>
            <h1 class="text-5xl sm:text-6xl font-extrabold text-white leading-[1.1] mb-5">
                Create Tickets for<br>
                <span class="text-brand">Your Private Event</span>
            </h1>
            <p class="text-slate-300 text-lg mb-8 leading-relaxed max-w-xl">
                Unlike organizers who sell tickets to the public, you buy a package of invitation tickets for your own celebration — then invite guests by phone.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $ctaUrl }}" class="bg-brand hover:bg-brand-dark text-white font-extrabold px-7 py-3.5 rounded-xl transition-colors text-sm">
                    {{ $ctaLabel }}
                </a>
                <a href="#how-it-works" class="border border-white/30 hover:border-white/60 text-white font-semibold px-7 py-3.5 rounded-xl transition-colors text-sm">
                    See How It Works
                </a>
            </div>

            <div class="flex flex-wrap gap-8 mt-12 pt-8 border-t border-white/10">
                @foreach([
                    ['Buy capacity', 'Not sell publicly'],
                    ['Invite by phone', 'Private guest links'],
                    ['QR admission', 'Each guest gets a code'],
                    ['Zaad · eDahab', 'Pay for your package'],
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
            <strong class="font-extrabold">Create Event vs Create Ticket:</strong>
            <span class="text-amber-900/90">
                <a href="{{ route('organizers') }}" class="underline font-semibold">Create Event</a>
                is for organizers selling public tickets.
                <strong>Create Ticket</strong> is for customers buying invitation numbers for a private wedding, dinner, or gathering.
            </span>
        </div>
    </section>

    <section class="pb-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-ink mb-3">
                Built for Hosts, Not Ticket Sellers
            </h2>
            <p class="text-mute max-w-xl mx-auto text-sm leading-relaxed">
                You pay for how many guests you want to invite. Guests do not buy from a public listing — they only receive your invitation.
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
                From Package to Guest Invite in 4 Steps
            </h2>
            <p class="text-mute text-sm">
                Buy capacity once, then invite guests at your own pace.
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
                Ready to Create Your Invitation Tickets?
            </h2>
            <p class="text-white/80 text-sm mb-7 max-w-md mx-auto">
                Start with a design and the number of guests you need. Invite people privately after you pay.
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
