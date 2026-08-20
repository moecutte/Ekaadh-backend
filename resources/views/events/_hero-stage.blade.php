@php
    $mockEvents = $featured->concat($upcoming)->unique('id')->values();
    $featEvent = $mockEvents->get(0);
    $listEvent = $mockEvents->get(1) ?? $featEvent;
    $ticketEvent = $mockEvents->get(2) ?? $listEvent ?? $featEvent;
    $homeCats = $categories->take(4);
    if ($homeCats->count() < 4) {
        $homeCats = collect(['Music', 'Sports', 'Comedy', 'Tech']);
    }

    $heroCities = ['Hargeisa', 'Mogadishu', 'Berbera', 'Jigjiga', 'Djibouti', 'Burco', 'Borama', 'Bosaso', 'Garowe', 'Lasanod', 'Erigavo'];
    $cityAliases = ['Burco' => 'Burao', 'Lasanod' => 'Las Anod'];
    $cityLookup = collect($cities)->mapWithKeys(fn ($c) => [mb_strtolower($c) => $c]);
    $catIcons = [
        'Music' => 'M9 18V5l12-2v13',
        'Concerts' => 'M9 18V5l12-2v13',
        'Sports' => 'M12 2a10 10 0 100 20 10 10 0 000-20z',
        'Comedy' => 'M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01',
        'Tech' => 'M4 7h16M4 17h16M9 7v10M15 7v10',
        'Food' => 'M4 11h16M12 4v14',
        'Culture' => 'M12 3v18M4 8h16',
        'Business' => 'M3 21h18M5 21V8h6v13M13 21V4h6v17',
    ];
    $mockPhotos = [
        asset('images/hero-mock-concert.png'),
        asset('images/hero-mock-conference.png'),
        asset('images/hero-mock-festival.png'),
    ];
    $mockTitles = ['Night Live Concert', 'Tech Summit 2026', 'Hargeisa Culture Fest'];
    $ticketTimeLabel = '4:00 PM';
    if ($ticketEvent?->event_time) {
        try {
            $ticketTimeLabel = \Carbon\Carbon::parse($ticketEvent->event_time)->format('g:i A');
        } catch (\Throwable) {
            $ticketTimeLabel = (string) $ticketEvent->event_time;
        }
    }
@endphp

<style>
    .hero-stage {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        background:
            radial-gradient(ellipse 55% 60% at 72% 48%, rgba(50, 56, 145, 0.5), transparent 64%),
            radial-gradient(ellipse 40% 30% at 28% 18%, rgba(90, 100, 200, 0.22), transparent 70%),
            linear-gradient(180deg, #070b1c 0%, #0c1230 42%, #10183a 100%);
        min-height: 640px;
        text-align: left;
        color: #fff;
    }
    .hero-stage::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: radial-gradient(1.4px 1.4px at 20% 30%, rgba(255,255,255,.55), transparent),
            radial-gradient(1.2px 1.2px at 70% 22%, rgba(255,255,255,.4), transparent),
            radial-gradient(1px 1px at 42% 58%, rgba(255,255,255,.35), transparent),
            radial-gradient(1.5px 1.5px at 82% 48%, rgba(255,255,255,.3), transparent),
            radial-gradient(1px 1px at 12% 72%, rgba(255,255,255,.25), transparent),
            radial-gradient(1.3px 1.3px at 58% 16%, rgba(255,255,255,.45), transparent);
        opacity: .7;
        pointer-events: none;
        z-index: 0;
    }
    .hero-curtain {
        position: absolute;
        top: -2%;
        bottom: -12%;
        width: clamp(150px, 26vw, 400px);
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
    }
    .hero-curtain img {
        width: 140%;
        height: 100%;
        object-fit: cover;
        object-position: left center;
        filter: saturate(1.05) contrast(1.08);
    }
    .hero-curtain--left {
        left: 0;
        -webkit-mask-image: linear-gradient(to right, #000 52%, rgba(0,0,0,.55) 78%, transparent 100%);
        mask-image: linear-gradient(to right, #000 52%, rgba(0,0,0,.55) 78%, transparent 100%);
    }
    .hero-curtain--right {
        right: 0;
        -webkit-mask-image: linear-gradient(to left, #000 52%, rgba(0,0,0,.55) 78%, transparent 100%);
        mask-image: linear-gradient(to left, #000 52%, rgba(0,0,0,.55) 78%, transparent 100%);
    }
    .hero-curtain--right img {
        transform: scaleX(-1);
        object-position: left center;
    }
    .hero-inner {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(280px, 520px);
        align-items: center;
        gap: 12px 24px;
        width: min(1200px, calc(100% - 32px));
        margin: 0 auto;
        padding: 64px 8px 56px;
    }
    .hero-copy {
        max-width: 640px;
        margin: 0;
        padding: 28px 0 0;
    }
    .hero-title-1 {
        font-size: clamp(36px, 6.2vw, 64px);
        font-weight: 800;
        letter-spacing: -0.045em;
        line-height: .95;
        color: #fff;
    }
    .hero-title-2 {
        display: block;
        margin-top: 4px;
        font-family: Fraunces, Georgia, serif;
        font-style: italic;
        font-weight: 500;
        font-size: clamp(30px, 5.2vw, 52px);
        color: #9aa4e8;
        letter-spacing: -0.03em;
        line-height: 1;
    }
    .hero-cta {
        box-shadow: 0 10px 36px rgba(50, 56, 145, .55);
    }
    .hero-search {
        box-shadow: 0 18px 50px rgba(0,0,0,.28);
        width: 100%;
    }
    .hero-visual {
        position: relative;
        height: 520px;
        min-width: 0;
    }
    .hero-orbit-ring {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 460px;
        height: 460px;
        margin: -230px 0 0 -230px;
        border: 1px dashed rgba(255, 255, 255, .18);
        border-radius: 50%;
        pointer-events: none;
    }
    .hero-orbit-ring--inner {
        width: 330px;
        height: 330px;
        margin: -165px 0 0 -165px;
        border-style: solid;
        border-color: rgba(255, 255, 255, .08);
    }
    .hero-orbit-item {
        position: absolute;
        left: 50%;
        top: 50%;
        --angle: calc((var(--i) * 360deg / var(--n)) - 90deg);
        transform:
            translate(-50%, -50%)
            rotate(var(--angle))
            translate(var(--r, 210px))
            rotate(calc(-1 * var(--angle)));
        white-space: nowrap;
        font-size: 11px;
        font-weight: 800;
        color: #fff;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 999px;
        padding: 6px 11px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .18);
        z-index: 2;
        transition: background .18s ease, color .18s ease;
    }
    .hero-orbit-item:hover {
        background: #323891;
        color: #fff;
        z-index: 6;
    }
    .hero-phones {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 3;
        pointer-events: none;
    }
    .hero-phones::before {
        content: "";
        position: absolute;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(154, 164, 232, .28), transparent 72%);
        pointer-events: none;
    }
    .hero-phone {
        position: relative;
        width: 178px;
        height: 368px;
        border-radius: 34px;
        background: #1a1733;
        padding: 7px;
        box-shadow:
            0 0 0 1px rgba(255,255,255,.08),
            0 24px 50px rgba(15, 26, 46, .32);
        flex-shrink: 0;
    }
    .hero-phone::after {
        content: "";
        position: absolute;
        top: 11px;
        left: 50%;
        width: 72px;
        height: 10px;
        margin-left: -36px;
        background: #1a1733;
        border-radius: 999px;
        z-index: 5;
    }
    .hero-phone--left {
        z-index: 1;
        transform: translateX(22px) translateY(16px) rotate(-10deg);
        animation: hero-bob-left 5.4s ease-in-out infinite;
    }
    .hero-phone--center {
        z-index: 3;
        transform: translateY(-6px);
        animation: hero-bob-center 6.2s ease-in-out infinite;
    }
    .hero-phone--right {
        z-index: 2;
        transform: translateX(-22px) translateY(16px) rotate(10deg);
        animation: hero-bob-right 5.8s ease-in-out infinite;
    }
    @keyframes hero-bob-left {
        0%, 100% { transform: translateX(22px) translateY(16px) rotate(-10deg); }
        50% { transform: translateX(18px) translateY(4px) rotate(-7.5deg); }
    }
    @keyframes hero-bob-center {
        0%, 100% { transform: translateY(-6px); }
        50% { transform: translateY(-18px); }
    }
    @keyframes hero-bob-right {
        0%, 100% { transform: translateX(-22px) translateY(16px) rotate(10deg); }
        50% { transform: translateX(-18px) translateY(5px) rotate(7.5deg); }
    }
    .hero-phone-screen {
        height: 100%;
        border-radius: 27px;
        overflow: hidden;
        background: #f2f4f8;
        display: flex;
        flex-direction: column;
        font-size: 9px;
        line-height: 1.25;
        color: #0f1a2e;
        text-align: left;
    }
    .hp-status {
        display: flex;
        justify-content: space-between;
        padding: 16px 12px 2px;
        font-size: 8px;
        font-weight: 800;
        color: #0f1a2e;
        background: #fff;
    }
    .hp-nav {
        margin-top: auto;
        background: #26215c;
        color: rgba(255,255,255,.55);
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        padding: 7px 4px 9px;
        text-align: center;
        font-size: 6.5px;
        font-weight: 700;
    }
    .hp-nav span.is-on { color: #fff; font-weight: 800; }
    .hp-nav svg { display: block; margin: 0 auto 3px; }
    @media (max-width: 1100px) {
        .hero-inner { grid-template-columns: minmax(0, 1fr) minmax(240px, 420px); }
        .hero-visual { height: 460px; }
        .hero-orbit-item { --r: 176px; font-size: 10px; padding: 5px 9px; }
        .hero-orbit-ring { width: 380px; height: 380px; margin: -190px 0 0 -190px; }
        .hero-phone { width: 150px; height: 310px; }
    }
    @media (max-width: 900px) {
        .hero-inner {
            grid-template-columns: 1fr;
            padding: 48px 4px 36px;
        }
        .hero-copy { max-width: none; }
        .hero-visual { height: 430px; margin: 0 auto; width: min(520px, 100%); }
        .hero-curtain { opacity: .92; width: clamp(92px, 22vw, 220px); }
        .hero-orbit-item { --r: 168px; }
        .hero-phone { width: 148px; height: 306px; }
    }
    @media (max-width: 640px) {
        .hero-phone--left, .hero-phone--right { display: none; }
        .hero-phone--center { animation: hero-bob-center 6.2s ease-in-out infinite; }
        .hero-orbit-ring { display: none; }
        .hero-visual { height: auto; display: flex; flex-direction: column; align-items: center; gap: 16px; }
        .hero-phones { position: relative; height: auto; padding: 8px 0 4px; }
        .hero-orbit-item {
            position: static;
            transform: none !important;
            margin: 3px;
        }
        .hero-cities-mobile {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 340px;
            order: 2;
        }
        .hero-phones { order: 1; }
    }
    @media (min-width: 641px) {
        .hero-cities-mobile { display: contents; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hero-phone { animation: none !important; }
        .hero-phone--left { transform: translateX(22px) translateY(16px) rotate(-10deg); }
        .hero-phone--center { transform: translateY(-6px); }
        .hero-phone--right { transform: translateX(-22px) translateY(16px) rotate(10deg); }
    }
</style>

<section class="hero-stage">
    <div class="hero-curtain hero-curtain--left" aria-hidden="true"><img src="{{ asset('images/hero-curtain.png') }}" alt=""></div>
    <div class="hero-curtain hero-curtain--right" aria-hidden="true"><img src="{{ asset('images/hero-curtain.png') }}" alt=""></div>

    <div class="hero-inner">
    <div class="hero-copy">
        <h1 class="hero-title-1 mb-4">
            {{ __('ui.hero_title_1') }}
            <span class="hero-title-2">{{ __('ui.hero_title_2') }}</span>
        </h1>
        <p class="text-slate-300 text-sm sm:text-base mb-8 leading-relaxed max-w-xl">
            {{ __('ui.hero_subtitle') }}
        </p>
        <a href="{{ route('events.index') }}" class="hero-cta inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-bold px-7 py-3 rounded-full text-sm transition-colors mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            {{ __('ui.explore_events') }}
        </a>

        <form action="{{ route('events.index') }}" method="GET" class="hero-search bg-white rounded-2xl flex items-stretch overflow-hidden text-left">
            <div class="flex items-center gap-2 flex-1 px-4 py-1 min-w-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" placeholder="{{ __('ui.search_events') }}" class="flex-1 outline-none text-sm text-ink placeholder-slate-400 bg-transparent min-w-0 py-3">
            </div>
            <div class="hidden sm:block w-px bg-slate-200 my-3 shrink-0"></div>
            <div class="hidden sm:flex items-center gap-1.5 px-3 min-w-[148px] shrink-0">
                <select name="category" class="flex-1 outline-none text-sm text-ink bg-transparent cursor-pointer py-3">
                    <option value="">{{ __('ui.all_categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="hidden sm:block w-px bg-slate-200 my-3 shrink-0"></div>
            <div class="hidden sm:flex items-center gap-1.5 px-3 min-w-[120px] shrink-0">
                <select name="city" class="flex-1 outline-none text-sm text-ink bg-transparent cursor-pointer py-3">
                    <option value="">{{ __('ui.all_cities') }}</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-brand hover:bg-brand-dark text-white font-bold px-6 text-sm shrink-0 transition-colors rounded-r-2xl">{{ __('ui.search') }}</button>
        </form>
    </div>

    <div class="hero-visual" aria-label="{{ __('ui.hero_cities_label') }}">
        <div class="hero-orbit-ring" aria-hidden="true"></div>
        <div class="hero-orbit-ring hero-orbit-ring--inner" aria-hidden="true"></div>

        <div class="hero-cities-mobile">
            @foreach($heroCities as $i => $name)
                @php
                    $alias = $cityAliases[$name] ?? $name;
                    $queryCity = $cityLookup[mb_strtolower($name)] ?? $cityLookup[mb_strtolower($alias)] ?? $name;
                @endphp
                <a href="{{ route('events.index', ['city' => $queryCity]) }}" class="hero-orbit-item" style="--i: {{ $i }}; --n: {{ count($heroCities) }};">{{ $name }}</a>
            @endforeach
        </div>

        <div class="hero-phones" aria-hidden="true">
            {{-- Search tab --}}
            <div class="hero-phone hero-phone--left">
                <div class="hero-phone-screen">
                    <div class="hp-status"><span>9:41</span><span>●●●</span></div>
                    <div class="px-3 pt-1 pb-2 bg-[#f2f4f8]">
                        <p class="text-[16px] font-black text-ink leading-none mb-2">{{ __('ui.search') }}</p>
                        <div class="flex gap-1.5">
                            <div class="flex-1 bg-white border border-[#e8ecf1] rounded-full flex items-center gap-1.5 px-2 py-1.5 text-[8px] text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                {{ __('ui.search_placeholder') }}
                            </div>
                            <div class="w-8 h-8 rounded-2xl bg-brand flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            </div>
                        </div>
                        <div class="flex gap-1 mt-2 overflow-hidden">
                            <span class="shrink-0 bg-brand text-white text-[7px] font-extrabold px-2 py-1 rounded-full">{{ __('ui.all') }}</span>
                            @foreach($homeCats->take(3) as $cat)
                                <span class="shrink-0 bg-white text-ink text-[7px] font-extrabold px-2 py-1 rounded-full border border-slate-200">{{ $cat }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="px-3 space-y-2 pb-2 flex-1 overflow-hidden">
                        @foreach([0, 1] as $i)
                            <div class="bg-white rounded-xl overflow-hidden">
                                <div class="h-16 bg-slate-200 relative">
                                    <img src="{{ $mockPhotos[$i] }}" alt="" class="w-full h-full object-cover">
                                    <span class="absolute top-1.5 right-1.5 bg-brand text-white text-[6px] font-extrabold px-1.5 py-0.5 rounded-full">{{ $homeCats[$i] ?? 'Music' }}</span>
                                </div>
                                <div class="px-2 py-1.5">
                                    <p class="font-extrabold text-[8px] truncate">{{ $mockTitles[$i] }}</p>
                                    <p class="text-[7px] text-slate-400 truncate">{{ $heroCities[$i] ?? 'Hargeisa' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @include('events._phone-nav', ['active' => 'home'])
                </div>
            </div>

            {{-- Home tab --}}
            <div class="hero-phone hero-phone--center">
                <div class="hero-phone-screen">
                    <div class="hp-status"><span>9:41</span><span>●●●</span></div>
                    <div class="bg-white px-3 pt-1 pb-2.5">
                        <div class="flex items-center gap-1 mb-2">
                            <img src="{{ asset('images/ekaadh-logo.png') }}" alt="" class="h-3.5 w-auto">
                            <div class="ml-auto flex items-center bg-[#f2f4f8] border border-[#e2e8e4] rounded-lg p-0.5">
                                <span class="bg-brand text-white text-[6px] font-extrabold px-1.5 py-0.5 rounded">{{ __('ui.english') }}</span>
                                <span class="text-mute text-[6px] font-extrabold px-1.5 py-0.5">{{ __('ui.somali') }}</span>
                            </div>
                            <span class="w-6 h-6 rounded-xl bg-[#f2f4f8] flex items-center justify-center ml-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-mute" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                            </span>
                        </div>
                        <div class="bg-[#f2f4f8] border border-[#e2e8e4] rounded-2xl flex items-center gap-1.5 px-2.5 py-2 text-[8px] text-slate-400 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            {{ __('ui.search_events') }}
                        </div>
                    </div>
                    <div class="px-3 pt-2 flex-1 overflow-hidden">
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="font-black text-[9px]">{{ __('ui.categories') }}</p>
                            <span class="bg-brand-soft text-brand text-[7px] font-extrabold px-1.5 py-0.5 rounded-full">{{ __('ui.all') }}</span>
                        </div>
                        <div class="grid grid-cols-4 gap-1 mb-2.5">
                            @foreach($homeCats as $cat)
                                <div class="bg-white rounded-xl px-0.5 pt-1.5 pb-1 text-center">
                                    <div class="w-7 h-7 mx-auto rounded-lg bg-brand-soft text-brand flex items-center justify-center mb-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $catIcons[$cat] ?? 'M4 6h16M4 12h16M4 18h16' }}"/></svg>
                                    </div>
                                    <p class="text-[6px] font-extrabold truncate leading-tight">{{ $cat }}</p>
                                </div>
                            @endforeach
                        </div>
                        <p class="font-black text-[9px] mb-1.5">{{ __('ui.featured') }}</p>
                        <div class="rounded-2xl overflow-hidden h-[88px] relative mb-2">
                            <img src="{{ $mockPhotos[1] }}" alt="" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                            <span class="absolute top-1.5 right-1.5 bg-brand text-white text-[6px] font-extrabold px-1.5 py-0.5 rounded-full">{{ $homeCats[1] ?? 'Tech' }}</span>
                            <div class="absolute bottom-1.5 left-2 right-2">
                                <p class="text-white/80 text-[6px] font-semibold">Aug 24, 2026</p>
                                <p class="text-white font-extrabold text-[8px] leading-tight truncate">{{ $mockTitles[1] }}</p>
                            </div>
                        </div>
                        <p class="font-black text-[9px] mb-1.5">{{ __('ui.upcoming_events') }}</p>
                        <div class="bg-white rounded-xl overflow-hidden flex">
                            <div class="w-12 h-12 bg-slate-200 shrink-0">
                                <img src="{{ $mockPhotos[2] }}" alt="" class="w-full h-full object-cover">
                            </div>
                            <div class="px-2 py-1.5 min-w-0">
                                <p class="font-extrabold text-[8px] truncate">{{ $mockTitles[2] }}</p>
                                <p class="text-[7px] text-slate-400 truncate">Hargeisa</p>
                            </div>
                        </div>
                    </div>
                    @include('events._phone-nav', ['active' => 'home'])
                </div>
            </div>

            {{-- Booked events --}}
            <div class="hero-phone hero-phone--right">
                <div class="hero-phone-screen bg-white">
                    <div class="hp-status bg-white"><span>9:41</span><span>●●●</span></div>
                    <div class="px-3 pt-1 pb-2">
                        <p class="text-[15px] font-black text-ink leading-tight">{{ __('ui.booked_events') }}</p>
                        <p class="text-[7px] text-mute mt-0.5">{{ __('ui.booked_events_sub') }}</p>
                        <div class="flex gap-1 mt-2">
                            <span class="bg-brand text-white text-[7px] font-extrabold px-2 py-0.5 rounded-full">{{ __('ui.all') }}</span>
                            <span class="bg-[#f2f4f8] text-mute text-[7px] font-extrabold px-2 py-0.5 rounded-full">{{ __('ui.valid') }}</span>
                            <span class="bg-[#f2f4f8] text-mute text-[7px] font-extrabold px-2 py-0.5 rounded-full">{{ __('ui.expired') }}</span>
                        </div>
                    </div>
                    <div class="px-3 flex-1 overflow-hidden">
                        <div class="bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-sm">
                            <div class="h-[92px] relative bg-slate-200">
                                <img src="{{ $mockPhotos[0] }}" alt="" class="w-full h-full object-cover">
                                <div class="absolute top-2 left-2 bg-white rounded-xl px-1.5 py-1 text-center min-w-[28px]">
                                    <p class="text-[6px] font-extrabold text-brand leading-none">AUG</p>
                                    <p class="text-[11px] font-black leading-none">24</p>
                                </div>
                            </div>
                            <div class="px-2.5 py-2">
                                <p class="font-extrabold text-[8px] truncate">{{ $mockTitles[0] }}</p>
                                <p class="text-[7px] text-slate-400 truncate">Hargeisa</p>
                                <div class="flex items-center justify-between mt-1.5">
                                    <span class="text-[7px] text-slate-400">{{ $ticketTimeLabel }}</span>
                                    <span class="text-brand font-extrabold text-[8px]">{{ __('ui.valid') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @include('events._phone-nav', ['active' => 'booked'])
                </div>
            </div>
        </div>
    </div>
    </div>
</section>
