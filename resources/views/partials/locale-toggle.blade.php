@php
    $variant = $variant ?? 'nav';
    $locale = app()->getLocale();
    $isNav = $variant === 'nav';
    $isMobile = $variant === 'nav-mobile';
    $wrap = $isNav
        ? 'flex items-center rounded-lg bg-white/10 p-0.5 text-xs font-bold'
        : ($isMobile
            ? 'flex items-center rounded-lg bg-white/10 p-0.5 text-[11px] font-bold'
            : 'flex items-center rounded-lg bg-white border border-slate-200 p-0.5 text-xs font-bold shadow-sm');
    $on = $isNav || $isMobile
        ? 'bg-white text-ink'
        : 'bg-brand text-white';
    $off = $isNav || $isMobile
        ? 'text-slate-300 hover:text-white'
        : 'text-mute hover:text-ink';
    $pad = $isMobile ? 'px-2 py-1' : 'px-2.5 py-1';
@endphp
<div class="{{ $wrap }}" role="group" aria-label="{{ __('ui.language') }}" data-locale-toggle>
    <a href="{{ route('locale.switch', 'en') }}" data-locale-switch
       class="{{ $pad }} rounded-md transition-colors {{ $locale === 'en' ? $on : $off }}">{{ __('ui.english') }}</a>
    <a href="{{ route('locale.switch', 'so') }}" data-locale-switch
       class="{{ $pad }} rounded-md transition-colors {{ $locale === 'so' ? $on : $off }}">{{ __('ui.somali') }}</a>
</div>
