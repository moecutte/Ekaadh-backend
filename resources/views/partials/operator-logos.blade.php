@php
    $size = $size ?? 'card';
    $onDark = $onDark ?? in_array($size, ['footer', 'hero'], true);
    $includeSomtel = $includeSomtel ?? in_array($size, ['footer', 'hero'], true);
    $count = $includeSomtel ? 4 : 3;
    $maxW = $count === 4 ? 'max-w-[22%]' : 'max-w-[30%]';
    $imgClass = match ($size) {
        'compact' => 'h-5 sm:h-6 max-w-[31%] sm:max-w-[30%]',
        'footer' => 'h-5 sm:h-6 '.$maxW,
        'hero' => 'h-7 sm:h-9 '.$maxW,
        default => 'h-6 sm:h-8 '.$maxW,
    };
    if ($onDark) {
        $imgClass .= ' brightness-0 invert';
    }
    $dividerClass = $onDark
        ? 'hidden min-[380px]:block w-px self-stretch bg-white/20'
        : 'hidden min-[380px]:block w-px self-stretch bg-slate-200';
@endphp
<div class="flex flex-wrap items-center justify-start gap-x-1.5 gap-y-1 sm:gap-x-2 min-w-0 w-full max-w-full {{ $class ?? '' }}">
    <img src="{{ asset('images/telesom-logo.png') }}" alt="Telesom Zaad" class="{{ $imgClass }} w-auto object-contain">
    <span class="{{ $dividerClass }}" aria-hidden="true"></span>
    <img src="{{ asset('images/golis-logo.png') }}" alt="Golis Sahal" class="{{ $imgClass }} w-auto object-contain">
    <span class="{{ $dividerClass }}" aria-hidden="true"></span>
    <img src="{{ asset('images/hormuud-logo.png') }}" alt="Hormuud EVC Plus" class="{{ $imgClass }} w-auto object-contain">
    @if($includeSomtel)
        <span class="{{ $dividerClass }}" aria-hidden="true"></span>
        <img src="{{ asset('images/somtel-logo.png') }}" alt="Somtel eDahab" class="{{ $imgClass }} w-auto object-contain">
    @endif
</div>
