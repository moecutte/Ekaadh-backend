<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Ekaadh') — Find & Book Events</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#323891', soft: '#eef0f8', dark: '#262a6d' },
                        ink: '#0f1a2e',
                        mute: '#64748b',
                        page: '#f2f4f8',
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    @livewireStyles
</head>
<body class="bg-page text-ink antialiased min-h-screen flex flex-col">
    <nav class="sticky top-0 z-50 bg-ink shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center shrink-0">
                    <img src="{{ asset('images/ekaadh-logo-white.png') }}" alt="ekaadh" class="h-8 w-auto">
                </a>

                @php
                    $onOrganizers = request()->routeIs('organizers');
                    $onCreateTicket = request()->routeIs('create-ticket') || request()->routeIs('private-events.*');
                    $authUser = auth()->user();
                    $isCustomer = $authUser && $authUser->isCustomer();
                    $isPortalUser = $authUser && ($authUser->isAdmin() || $authUser->isOrganizer());
                @endphp

                <div class="hidden md:flex items-center gap-7">
                    <a href="{{ route('events.index') }}" class="text-slate-300 hover:text-white text-sm font-medium transition-colors {{ request()->routeIs('events.index') ? 'text-white' : '' }}">Browse Events</a>
                    @unless($isCustomer)
                        <a href="{{ route('organizers') }}" class="text-slate-300 hover:text-white text-sm font-medium transition-colors {{ $onOrganizers ? 'text-white' : '' }}">Create Event</a>
                        <a href="{{ route('create-ticket') }}" class="text-slate-300 hover:text-white text-sm font-medium transition-colors {{ $onCreateTicket ? 'text-white' : '' }}">Create Ticket</a>
                    @endunless
                </div>

                <div class="hidden md:flex items-center gap-3">
                    @if(! $onOrganizers)
                        <a href="{{ route('tickets.index') }}" class="flex items-center gap-1.5 text-slate-300 hover:text-white text-sm font-medium transition-colors px-1 {{ request()->routeIs('tickets.*') ? 'text-white' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Booked Events
                        </a>
                        @if($isCustomer)
                            @php $firstName = explode(' ', trim($authUser->name))[0]; @endphp
                            <div class="relative" id="profile-menu">
                                <button type="button" id="profile-menu-toggle" class="flex items-center gap-2 bg-white/10 hover:bg-white/15 text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors" aria-expanded="false" aria-haspopup="true">
                                    <span class="w-6 h-6 bg-brand rounded-full flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </span>
                                    <span>{{ $firstName }}</span>
                                    <svg id="profile-menu-chevron" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div id="profile-menu-dropdown" class="hidden absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden z-50">
                                    <div class="px-4 py-3 border-b border-slate-100">
                                        <p class="font-bold text-ink text-sm">{{ $authUser->name }}</p>
                                        <p class="text-xs text-mute mt-0.5">{{ $authUser->phone }}</p>
                                    </div>
                                    <a href="{{ route('private-events.index') }}" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-ink hover:bg-page transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-mute" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                        <span>
                                            <span class="block font-semibold">Tickets</span>
                                            <span class="block text-[11px] text-mute">Private invitations you created</span>
                                        </span>
                                    </a>
                                    <a href="{{ route('tickets.index') }}" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-ink hover:bg-page transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-mute" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>
                                            <span class="block font-semibold">Events</span>
                                            <span class="block text-[11px] text-mute">Events you booked</span>
                                        </span>
                                    </a>
                                    <form method="POST" action="{{ route('customer.logout') }}">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Sign Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @elseif($isPortalUser)
                            <a href="{{ $authUser->isAdmin() ? route('admin.dashboard') : route('organizer.dashboard') }}" class="bg-white/10 hover:bg-white/15 text-white text-sm font-semibold px-3.5 py-1.5 rounded-lg transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('customer.login') }}" class="bg-brand hover:bg-brand-dark text-white text-sm font-bold px-5 py-2 rounded-lg transition-colors">Sign In</a>
                        @endif
                    @elseif($isPortalUser)
                        <a href="{{ $authUser->isAdmin() ? route('admin.dashboard') : route('organizer.dashboard') }}" class="bg-white/10 hover:bg-white/15 text-white text-sm font-semibold px-3.5 py-1.5 rounded-lg transition-colors">Dashboard</a>
                    @endif
                </div>

                <div class="flex md:hidden items-center gap-2">
                    @if(! $onOrganizers)
                        <a href="{{ route('tickets.index') }}" class="text-slate-300 p-2" aria-label="Booked Events">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </a>
                    @endif
                    <button type="button" id="mobile-nav-toggle" class="text-white p-2" aria-label="Open menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <div id="mobile-nav" class="hidden md:hidden bg-[#0a1220] border-t border-white/10 px-4 pb-4 pt-2 space-y-1">
            <a href="{{ route('events.index') }}" class="block text-slate-300 hover:text-white py-2.5 text-sm font-medium">Browse Events</a>
            @unless($isCustomer)
                <a href="{{ route('organizers') }}" class="block text-slate-300 hover:text-white py-2.5 text-sm font-medium">Create Event</a>
                <a href="{{ route('create-ticket') }}" class="block text-slate-300 hover:text-white py-2.5 text-sm font-medium">Create Ticket</a>
            @endunless
            @if(! $onOrganizers)
                <a href="{{ route('tickets.index') }}" class="block text-slate-300 hover:text-white py-2.5 text-sm font-medium">Booked Events</a>
                @if($isCustomer)
                    <div class="pt-2 border-t border-white/10 mt-1">
                        <a href="{{ route('private-events.index') }}" class="block text-slate-300 hover:text-white py-2.5 text-sm font-medium">Tickets</a>
                        <a href="{{ route('tickets.index') }}" class="block text-slate-300 hover:text-white py-2.5 text-sm font-medium">Events</a>
                        <p class="text-slate-400 text-xs px-0.5 mb-2">Signed in as {{ $authUser->name }}</p>
                        <form method="POST" action="{{ route('customer.logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 border border-white/20 text-white font-semibold py-2.5 rounded-xl text-sm hover:bg-white/10 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Sign Out
                            </button>
                        </form>
                    </div>
                @elseif($isPortalUser)
                    <a href="{{ $authUser->isAdmin() ? route('admin.dashboard') : route('organizer.dashboard') }}" class="block text-brand hover:text-white py-2.5 text-sm font-bold">Dashboard</a>
                @else
                    <a href="{{ route('customer.login') }}" class="w-full mt-2 block text-center bg-brand text-white font-bold py-3 rounded-xl text-sm">Sign In</a>
                @endif
            @elseif($isPortalUser)
                <a href="{{ $authUser->isAdmin() ? route('admin.dashboard') : route('organizer.dashboard') }}" class="block text-brand hover:text-white py-2.5 text-sm font-bold">Dashboard</a>
            @endif
        </div>
    </nav>
    <script>
        document.getElementById('mobile-nav-toggle')?.addEventListener('click', function () {
            document.getElementById('mobile-nav')?.classList.toggle('hidden');
        });

        (function () {
            const root = document.getElementById('profile-menu');
            const toggle = document.getElementById('profile-menu-toggle');
            const dropdown = document.getElementById('profile-menu-dropdown');
            const chevron = document.getElementById('profile-menu-chevron');
            if (!root || !toggle || !dropdown) return;

            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                const open = dropdown.classList.toggle('hidden') === false;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                chevron?.classList.toggle('rotate-180', open);
            });

            document.addEventListener('click', function (e) {
                if (!root.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                    chevron?.classList.remove('rotate-180');
                }
            });
        })();
    </script>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-ink text-slate-400 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                <div class="sm:col-span-2 lg:col-span-1">
                    <div class="mb-3">
                        <img src="{{ asset('images/ekaadh-logo-white.png') }}" alt="ekaadh" class="h-8 w-auto">
                    </div>
                    <p class="text-sm leading-relaxed">Discover events across Somalia &amp; Somaliland. Buy tickets with Zaad or eDahab.</p>
                </div>
                <div>
                    <p class="font-bold mb-3 text-xs uppercase tracking-wider text-slate-500">Explore</p>
                    <div class="flex flex-col gap-2 text-sm">
                        <a href="{{ route('events.index') }}" class="hover:text-white transition">All events</a>
                        <a href="{{ route('events.index', ['category' => 'Music']) }}" class="hover:text-white transition">Music</a>
                        <a href="{{ route('events.index', ['category' => 'Sports']) }}" class="hover:text-white transition">Sports</a>
                    </div>
                </div>
                <div>
                    <p class="font-bold mb-3 text-xs uppercase tracking-wider text-slate-500">Host &amp; organize</p>
                    <div class="flex flex-col gap-2 text-sm">
                        @unless($isCustomer)
                            <a href="{{ route('organizers') }}" class="hover:text-white transition">Create Event</a>
                            <a href="{{ route('create-ticket') }}" class="hover:text-white transition">Create Ticket</a>
                        @endunless
                        <a href="{{ route('tickets.index') }}" class="hover:text-white transition">Booked Events</a>
                        @guest
                            <a href="{{ route('organizer.register') }}" class="hover:text-white transition">Organizer account</a>
                            <a href="{{ route('organizer.login') }}" class="hover:text-white transition">Organizer login</a>
                        @else
                            @if(auth()->user()->isAdmin() || auth()->user()->isOrganizer())
                                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('organizer.dashboard') }}" class="hover:text-white transition">Go to dashboard</a>
                            @endif
                        @endguest
                    </div>
                </div>
                <div>
                    <p class="font-bold mb-3 text-xs uppercase tracking-wider text-slate-500">Pay with</p>
                    <p class="text-sm">Zaad · eDahab</p>
                    <p class="text-xs mt-2 text-slate-500">Your Event · Your Ticket</p>
                </div>
            </div>
            <div class="border-t border-white/10 pt-6 text-center text-xs text-slate-500">&copy; {{ date('Y') }} Ekaadh. All rights reserved.</div>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
