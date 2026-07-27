<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Organizer') — Ekaadh</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#323891', soft: '#eef0f8', dark: '#262a6d' },
                        ink: '#111827',
                        mute: '#6b7280',
                        page: '#f4f6f8',
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-page text-ink antialiased min-h-screen font-sans text-[15px]">
@php
    $nav = [
        ['organizer.dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', request()->routeIs('organizer.dashboard')],
        ['organizer.events.create', 'Create Event', 'M12 4v16m8-8H4', request()->routeIs('organizer.events.create')],
        ['organizer.events.index', 'My Events', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', request()->routeIs('organizer.events.index') || request()->routeIs('organizer.events.edit')],
        ['organizer.earnings', 'Earnings & Payouts', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', request()->routeIs('organizer.earnings')],
    ];
    $initials = collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp
<div class="min-h-screen flex">
    <aside class="w-[232px] shrink-0 bg-white border-r border-gray-100 flex flex-col">
        <div class="h-16 flex items-center px-5 border-b border-gray-100 shrink-0">
            <div class="flex items-center">
                <img src="{{ asset('images/ekaadh-logo.png') }}" alt="ekaadh" class="h-7 w-auto">
            </div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            @foreach($nav as [$route, $label, $icon, $active])
                <a href="{{ route($route) }}" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $active ? 'bg-brand text-white shadow-sm shadow-brand/20' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    <span class="truncate leading-none">{{ $label }}</span>
                </a>
            @endforeach
            <a href="{{ route('home') }}" target="_blank" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-400 hover:bg-gray-50 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Public site</span>
            </a>
        </nav>
        <div class="border-t border-gray-100 p-3 shrink-0 space-y-2">
            <div class="flex items-center gap-2.5 px-2 py-2 rounded-xl">
                <div class="w-8 h-8 bg-brand/12 rounded-full flex items-center justify-center text-brand text-xs font-black shrink-0">{{ $initials }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-gray-400 truncate">Event Organizer</p>
                </div>
            </div>
            <form method="POST" action="{{ route('organizer.logout') }}">@csrf
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
