@php
    $desktopCover = $desktopCover ?? $event->cover_image;
    $mobileCover = $mobileCover ?? ($event->cover_image_mobile ?: $desktopCover);
    $alt = $alt ?? $event->title;
    $imgClass = $imgClass ?? 'w-full h-full object-cover';
    $pictureClass = $pictureClass ?? 'contents';
    $fetchpriority = $fetchpriority ?? null;
    $lazy = $lazy ?? true;
@endphp
@if($desktopCover || $mobileCover)
    <picture class="{{ $pictureClass }}">
        @if($mobileCover)
            <source media="(max-width: 639px)" srcset="{{ $mobileCover }}">
        @endif
        <img
            src="{{ $desktopCover ?: $mobileCover }}"
            alt="{{ $alt }}"
            class="{{ $imgClass }}"
            @if($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
            @if($lazy) loading="lazy" decoding="async" @else decoding="async" @endif
        >
    </picture>
@endif
