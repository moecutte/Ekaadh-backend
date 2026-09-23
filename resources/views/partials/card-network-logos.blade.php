{{-- Standard Visa + Mastercard wordmarks (inline SVG for crisp checkout logos). --}}
@php
    $size = $size ?? 'card';
    $h = match ($size) {
        'compact' => 'h-6 sm:h-7',
        default => 'h-7 sm:h-9',
    };
@endphp
<div class="flex flex-wrap items-center justify-start gap-x-2.5 gap-y-1.5 min-w-0 w-full max-w-full {{ $class ?? '' }}" aria-label="Visa and Mastercard">
    {{-- Visa wordmark (brand blue) --}}
    <svg class="{{ $h }} w-auto shrink-0" viewBox="0 0 750 243" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Visa">
        <title>Visa</title>
        <path fill="#1A1F71" d="M278.2 209.1h-53.8L258 34.5h53.8l-33.6 174.6zm244.8-171.1c-10.6-4-27.3-8.3-48.1-8.3-53.1 0-90.5 28.3-90.8 68.8-.3 30 26.7 46.7 47.1 56.7 20.9 10.2 28 16.8 27.9 25.9-.2 14-16.7 20.4-32.2 20.4-21.5 0-33-3.1-50.6-10.4l-7-3.2-7.6 47.1c12.6 5.8 35.9 10.8 60.1 11.1 56.6 0 93.4-27.9 93.8-71.1.2-23.7-14.1-41.7-45.1-56.5-18.8-9.6-30.3-16-30.2-25.7.1-8.6 9.7-17.8 30.6-17.8 17.5-.3 30.1 3.7 39.9 7.9l4.8 2.3 7.3-45.2zM615.1 34.5h-41.6c-12.9 0-22.5 3.7-28.2 17.2l-80 157.4h56.5s9.2-25.6 11.3-31.2l68.9.1c1.6 7.3 6.6 31.1 6.6 31.1h49.9L615.1 34.5zm-66.2 112.4c4.6-12.4 22.1-60.1 22.1-60.1-.3.6 4.6-12.5 7.4-20.6l3.8 18.7s10.7 51.7 13 62h-46.3zM221.1 34.5l-52.7 141.9-5.6-28.9c-9.8-33.2-40.3-69.1-74.4-87.1l48.1 174.7h56.8l84.5-200.6h-56.7z"/>
        <path fill="#F7B600" d="M109.5 34.5H23.1L22 38.6c66.7 17.1 110.9 58.3 129.2 107.9l-18.6-94.8c-3.2-12.9-12.5-16.7-23.1-17.2z"/>
    </svg>
    <span class="hidden min-[380px]:block w-px self-stretch bg-slate-200" aria-hidden="true"></span>
    {{-- Mastercard interlocking circles --}}
    <svg class="{{ $h }} w-auto shrink-0" viewBox="0 0 152 96" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Mastercard">
        <title>Mastercard</title>
        <circle cx="58" cy="48" r="40" fill="#EB001B"/>
        <circle cx="94" cy="48" r="40" fill="#F79E1B"/>
        <path fill="#FF5F00" d="M76 18.2c-7.9 6.2-13 15.9-13 26.8s5.1 20.6 13 26.8c7.9-6.2 13-15.9 13-26.8s-5.1-20.6-13-26.8z"/>
    </svg>
</div>
