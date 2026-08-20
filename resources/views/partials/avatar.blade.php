@php
    $url = $url ?? null;
    $label = $label ?? '';
    $initials = $initials ?? mb_strtoupper(mb_substr((string) $label, 0, 1));
    $class = $class ?? 'w-8 h-8';
    $rounded = $rounded ?? 'rounded-full';
    $text = $text ?? 'text-xs';
    $bg = $bg ?? 'bg-brand/12';
    $fg = $fg ?? 'text-brand';
@endphp
@if($url)
    <img src="{{ $url }}" alt="{{ $label }}" class="{{ $class }} {{ $rounded }} object-cover shrink-0 {{ $bg }}">
@else
    <div class="{{ $class }} {{ $rounded }} {{ $bg }} flex items-center justify-center {{ $fg }} {{ $text }} font-black shrink-0">{{ $initials }}</div>
@endif
