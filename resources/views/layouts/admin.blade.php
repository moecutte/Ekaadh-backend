<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Ekaadh</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @include('partials.panel-theme')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="panel-app antialiased min-h-screen font-sans text-[15px]">
@php
    $peopleOpen = request()->routeIs('admin.organizers.*') || request()->routeIs('admin.customers.*') || request()->routeIs('admin.invitees.*');
    $catalogOpen = request()->routeIs('admin.events.*')
        || request()->routeIs('admin.categories.*')
        || request()->routeIs('admin.cities.*')
        || request()->routeIs('admin.invitation-designs.*')
        || request()->routeIs('admin.private-event-categories.*');
    $businessOpen = request()->routeIs('admin.packages.*')
        || request()->routeIs('admin.orders.*')
        || request()->routeIs('admin.payments.*')
        || request()->routeIs('admin.revenue.*')
        || request()->routeIs('admin.commission.*')
        || request()->routeIs('admin.payouts.*')
        || request()->routeIs('admin.settings.*');
    $supportOpen = request()->routeIs('admin.support.*');

    $link = function (string $route, string $label, string $icon, bool $active) {
        return compact('route', 'label', 'icon', 'active');
    };

    $people = [
        $link('admin.organizers.index', 'Organizers', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', request()->routeIs('admin.organizers.*')),
        $link('admin.customers.index', 'Customers', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', request()->routeIs('admin.customers.*')),
        $link('admin.invitees.index', 'Invitees', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', request()->routeIs('admin.invitees.*')),
    ];
    $catalog = [
        $link('admin.events.index', 'Events', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', request()->routeIs('admin.events.*')),
        $link('admin.categories.index', 'Categories', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', request()->routeIs('admin.categories.*') || request()->routeIs('admin.private-event-categories.*')),
        $link('admin.cities.index', 'Cities', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', request()->routeIs('admin.cities.*')),
        $link('admin.invitation-designs.index', 'Invite designs', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', request()->routeIs('admin.invitation-designs.*')),
    ];
    $business = [
        $link('admin.packages.index', 'Packages', 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', request()->routeIs('admin.packages.*')),
        $link('admin.orders.index', 'Orders', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', request()->routeIs('admin.orders.*')),
        $link('admin.payments.index', 'Payments', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', request()->routeIs('admin.payments.*')),
        $link('admin.revenue.index', 'Revenue Report', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', request()->routeIs('admin.revenue.*')),
        $link('admin.commission.edit', 'Commission', 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', request()->routeIs('admin.commission.*')),
        $link('admin.payouts.index', 'Payouts', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', request()->routeIs('admin.payouts.*')),
        $link('admin.settings.edit', 'System settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', request()->routeIs('admin.settings.*')),
    ];
    $initials = auth()->user()->initials();
@endphp
<div class="panel-shell">
    <aside class="panel-sidebar">
        <div class="panel-sidebar-head h-16 flex items-center px-5 border-b shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <img src="{{ asset('images/ekaadh-logo-white.png') }}" alt="ekaadh" class="h-7 w-auto" onerror="this.onerror=null;this.src='{{ asset('images/ekaadh-logo.png') }}';this.style.filter='brightness(0) invert(1)'">
                <span class="panel-chip">ADMIN</span>
            </div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" x-data="{
            people: {{ $peopleOpen ? 'true' : 'false' }},
            catalog: {{ $catalogOpen ? 'true' : 'false' }},
            business: {{ $businessOpen ? 'true' : 'false' }},
            support: {{ $supportOpen ? 'true' : 'false' }},
        }">
            <a href="{{ route('admin.dashboard') }}"
               class="panel-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-on' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="truncate leading-none">Dashboard</span>
            </a>

            <div class="pt-2">
                <button type="button" @click="people = !people"
                        class="panel-nav-group">
                    <span>People</span>
                    <span x-text="people ? '▾' : '▸'"></span>
                </button>
                <div x-show="people" x-cloak class="space-y-0.5 mt-0.5">
                    @foreach($people as $item)
                        <a href="{{ route($item['route']) }}" class="panel-nav-link {{ $item['active'] ? 'is-on' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            <span class="truncate leading-none">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="pt-1">
                <button type="button" @click="catalog = !catalog"
                        class="panel-nav-group">
                    <span>Catalog</span>
                    <span x-text="catalog ? '▾' : '▸'"></span>
                </button>
                <div x-show="catalog" x-cloak class="space-y-0.5 mt-0.5">
                    @foreach($catalog as $item)
                        <a href="{{ route($item['route']) }}" class="panel-nav-link {{ $item['active'] ? 'is-on' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            <span class="truncate leading-none">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="pt-1">
                <button type="button" @click="business = !business"
                        class="panel-nav-group">
                    <span>Business</span>
                    <span x-text="business ? '▾' : '▸'"></span>
                </button>
                <div x-show="business" x-cloak class="space-y-0.5 mt-0.5">
                    @foreach($business as $item)
                        <a href="{{ route($item['route']) }}" class="panel-nav-link {{ $item['active'] ? 'is-on' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            <span class="truncate leading-none">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="pt-1">
                <button type="button" @click="support = !support"
                        class="panel-nav-group">
                    <span>Support</span>
                    <span x-text="support ? '▾' : '▸'"></span>
                </button>
                <div x-show="support" x-cloak class="space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.support.conversations.index') }}" class="panel-nav-link {{ request()->routeIs('admin.support.conversations.*') ? 'is-on' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span class="truncate leading-none">Inbox</span>
                    </a>
                    <a href="{{ route('admin.support.faqs.index') }}" class="panel-nav-link {{ request()->routeIs('admin.support.faqs.*') ? 'is-on' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="truncate leading-none">FAQs</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.profile.edit') }}" class="panel-nav-link {{ request()->routeIs('admin.profile.*') ? 'is-on' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="truncate leading-none">Profile</span>
            </a>

            <a href="{{ route('home') }}" target="_blank" class="panel-nav-link panel-nav-ghost mt-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Public site</span>
            </a>
        </nav>
        <div class="panel-sidebar-foot border-t p-3 shrink-0 space-y-2">
            <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2.5 px-2 py-2 rounded-xl hover:bg-white/5 transition-colors {{ request()->routeIs('admin.profile.*') ? 'bg-white/10' : '' }}">
                @include('partials.avatar', ['url' => auth()->user()->avatar, 'label' => auth()->user()->name, 'initials' => $initials, 'bg' => 'bg-white/15', 'fg' => 'text-white'])
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-gold/80 truncate">Platform Admin</p>
                </div>
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf
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
                    'indexRoute' => route('admin.notifications.index'),
                    'openRoute' => 'admin.notifications.open',
                    'readAllRoute' => route('admin.notifications.read-all'),
                ])
                <a href="{{ route('admin.profile.edit') }}" class="shrink-0" title="Profile">
                    @include('partials.avatar', ['url' => auth()->user()->avatar, 'label' => auth()->user()->name, 'initials' => $initials])
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
