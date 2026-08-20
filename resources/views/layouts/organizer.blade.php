<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Organizer') — Ekaadh</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @include('partials.panel-theme')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="panel-app antialiased min-h-screen font-sans text-[15px]">
@php
    $authUser = auth()->user();
    $initials = $authUser->initials();
    $hasOrganizerProfile = (bool) $authUser?->organizerProfile;
    $nav = [
        ['organizer.dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', request()->routeIs('organizer.dashboard')],
    ];
    if ($hasOrganizerProfile) {
        $nav[] = ['organizer.events.create', 'Create Event', 'M12 4v16m8-8H4', request()->routeIs('organizer.events.create')];
        $nav[] = ['organizer.events.index', 'My Events', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', request()->routeIs('organizer.events.index') || request()->routeIs('organizer.events.edit') || request()->routeIs('organizer.events.invitations.*')];
        $nav[] = ['organizer.earnings', 'Earnings & Payouts', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', request()->routeIs('organizer.earnings')];
        $nav[] = ['organizer.profile.edit', 'Profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', request()->routeIs('organizer.profile.*')];
    }
@endphp
<div class="panel-shell">
    <aside class="panel-sidebar">
        <div class="panel-sidebar-head h-16 flex items-center px-5 border-b shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <img src="{{ asset('images/ekaadh-logo-white.png') }}" alt="ekaadh" class="h-7 w-auto" onerror="this.onerror=null;this.src='{{ asset('images/ekaadh-logo.png') }}';this.style.filter='brightness(0) invert(1)'">
                <span class="panel-chip">ORG</span>
            </div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            @foreach($nav as [$route, $label, $icon, $active])
                <a href="{{ route($route) }}" class="panel-nav-link {{ $active ? 'is-on' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    <span class="truncate leading-none">{{ $label }}</span>
                </a>
            @endforeach
            <a href="{{ route('home') }}" target="_blank" class="panel-nav-link panel-nav-ghost mt-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Public site</span>
            </a>
        </nav>
        <div class="panel-sidebar-foot border-t p-3 shrink-0 space-y-2">
            <a href="{{ route('organizer.profile.edit') }}" class="flex items-center gap-2.5 px-2 py-2 rounded-xl hover:bg-white/5 transition-colors {{ request()->routeIs('organizer.profile.*') ? 'bg-white/10' : '' }}">
                @include('partials.avatar', ['url' => $authUser->avatar, 'label' => $authUser->name, 'initials' => $initials, 'bg' => 'bg-white/15', 'fg' => 'text-white'])
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-white truncate">{{ $authUser->name }}</p>
                    <p class="text-[11px] text-gold/80 truncate">Event Organizer</p>
                </div>
            </a>
            <form method="POST" action="{{ route('organizer.logout') }}">@csrf
                <button type="submit" class="panel-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </aside>

    <div class="panel-main">
        <header class="panel-header">
            <div>
                <h1 class="font-extrabold text-lg text-ink">@yield('heading', 'Dashboard')</h1>
                <p class="text-xs text-mute hidden sm:block">{{ now()->format('l, M j, Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @hasSection('actions')@yield('actions')@endif
                @include('partials.notification-bell', [
                    'indexRoute' => route('organizer.notifications.index'),
                    'openRoute' => 'organizer.notifications.open',
                    'readAllRoute' => route('organizer.notifications.read-all'),
                ])
                <a href="{{ route('organizer.profile.edit') }}" class="shrink-0" title="Profile">
                    @include('partials.avatar', ['url' => $authUser->avatar, 'label' => $authUser->name, 'initials' => $initials])
                </a>
            </div>
        </header>
        <main class="panel-canvas">
            @if(session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-semibold px-4 py-3">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-semibold px-4 py-3">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
