<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Ekaadh</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{DEFAULT:'#323891',soft:'#eef0f8',dark:'#262a6d'},ink:'#111827',mute:'#6b7280',page:'#f4f6f8'},fontFamily:{sans:['Plus Jakarta Sans','sans-serif']}}}}</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-page text-ink antialiased min-h-screen font-sans text-[15px]">
@php
    $peopleOpen = request()->routeIs('admin.organizers.*') || request()->routeIs('admin.customers.*');
    $catalogOpen = request()->routeIs('admin.events.*')
        || request()->routeIs('admin.categories.*')
        || request()->routeIs('admin.cities.*')
        || request()->routeIs('admin.invitation-designs.*')
        || request()->routeIs('admin.private-event-categories.*');
    $businessOpen = request()->routeIs('admin.packages.*')
        || request()->routeIs('admin.orders.*')
        || request()->routeIs('admin.revenue.*')
        || request()->routeIs('admin.commission.*')
        || request()->routeIs('admin.payouts.*');

    $link = function (string $route, string $label, string $icon, bool $active) {
        return compact('route', 'label', 'icon', 'active');
    };

    $people = [
        $link('admin.organizers.index', 'Organizers', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', request()->routeIs('admin.organizers.*')),
        $link('admin.customers.index', 'Customers', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', request()->routeIs('admin.customers.*')),
    ];
    $catalog = [
        $link('admin.events.index', 'Events', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', request()->routeIs('admin.events.*')),
        $link('admin.categories.index', 'Categories', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', request()->routeIs('admin.categories.*') || request()->routeIs('admin.private-event-categories.*')),
        $link('admin.cities.index', 'Cities', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', request()->routeIs('admin.cities.*')),
        $link('admin.invitation-designs.index', 'Invite designs', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', request()->routeIs('admin.invitation-designs.*')),
    ];
    $business = [
        $link('admin.packages.index', 'Packages', 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', request()->routeIs('admin.packages.*')),
        $link('admin.orders.index', 'Orders & Payments', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', request()->routeIs('admin.orders.*')),
        $link('admin.revenue.index', 'Revenue Report', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', request()->routeIs('admin.revenue.*')),
        $link('admin.commission.edit', 'Commission', 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', request()->routeIs('admin.commission.*')),
        $link('admin.payouts.index', 'Payouts', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', request()->routeIs('admin.payouts.*')),
    ];
    $initials = collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp
<div class="min-h-screen flex">
    <aside class="w-[232px] shrink-0 bg-white border-r border-gray-100 flex flex-col">
        <div class="h-16 flex items-center px-5 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <img src="{{ asset('images/ekaadh-logo.png') }}" alt="ekaadh" class="h-7 w-auto">
                <span class="text-[9px] font-black bg-gray-800 text-white px-1.5 py-0.5 rounded tracking-widest shrink-0">ADMIN</span>
            </div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" x-data="{
            people: {{ $peopleOpen ? 'true' : 'false' }},
            catalog: {{ $catalogOpen ? 'true' : 'false' }},
            business: {{ $businessOpen ? 'true' : 'false' }},
        }">
            <a href="{{ route('admin.dashboard') }}"
               class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-brand text-white shadow-sm shadow-brand/20' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="truncate leading-none">Dashboard</span>
            </a>

            <div class="pt-2">
                <button type="button" @click="people = !people"
                        class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-gray-600">
                    <span>People</span>
                    <span x-text="people ? '▾' : '▸'"></span>
                </button>
                <div x-show="people" x-cloak class="space-y-0.5 mt-0.5">
                    @foreach($people as $item)
                        <a href="{{ route($item['route']) }}" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $item['active'] ? 'bg-brand text-white shadow-sm shadow-brand/20' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            <span class="truncate leading-none">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="pt-1">
                <button type="button" @click="catalog = !catalog"
                        class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-gray-600">
                    <span>Catalog</span>
                    <span x-text="catalog ? '▾' : '▸'"></span>
                </button>
                <div x-show="catalog" x-cloak class="space-y-0.5 mt-0.5">
                    @foreach($catalog as $item)
                        <a href="{{ route($item['route']) }}" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $item['active'] ? 'bg-brand text-white shadow-sm shadow-brand/20' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            <span class="truncate leading-none">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="pt-1">
                <button type="button" @click="business = !business"
                        class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-gray-600">
                    <span>Business</span>
                    <span x-text="business ? '▾' : '▸'"></span>
                </button>
                <div x-show="business" x-cloak class="space-y-0.5 mt-0.5">
                    @foreach($business as $item)
                        <a href="{{ route($item['route']) }}" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $item['active'] ? 'bg-brand text-white shadow-sm shadow-brand/20' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            <span class="truncate leading-none">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('home') }}" target="_blank" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-400 hover:bg-gray-50 hover:text-gray-700 mt-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Public site</span>
            </a>
        </nav>
        <div class="border-t border-gray-100 p-3 shrink-0 space-y-2">
            <div class="flex items-center gap-2.5 px-2 py-2 rounded-xl">
                <div class="w-8 h-8 bg-brand/12 rounded-full flex items-center justify-center text-brand text-xs font-black shrink-0">{{ $initials }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-gray-400 truncate">Platform Admin</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </aside>
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-6 shrink-0 gap-4">
            <div>
                <h1 class="font-extrabold text-lg text-gray-900">@yield('heading', 'Dashboard')</h1>
                <p class="text-xs text-gray-400 hidden sm:block">{{ now()->format('l, M j, Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @hasSection('actions')@yield('actions')@endif
                <div class="hidden sm:flex w-8 h-8 rounded-full bg-gray-100 items-center justify-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div class="w-8 h-8 bg-brand/12 rounded-full flex items-center justify-center text-brand text-xs font-black">{{ $initials }}</div>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-6">
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
