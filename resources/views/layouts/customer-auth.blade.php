<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Ekaadh</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @include('partials.vite-panel')
    <script src="{{ asset('js/locale-switch.js') }}"></script>
</head>
<body class="bg-[#f4f6f8] text-ink antialiased min-h-screen font-sans flex items-center justify-center p-4 sm:p-6 overflow-x-hidden">
    <div class="w-full max-w-md">
        <div class="flex justify-end mb-4">
            @include('partials.locale-toggle', ['variant' => 'light'])
        </div>
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex flex-col items-center">
                <img src="{{ asset('images/ekaadh-logo.png') }}" alt="ekaadh" class="h-12 w-auto">
            </a>
            <p class="text-mute text-sm mt-3">@yield('subtitle')</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
            @if($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 text-red-700 text-sm p-3">
                    {{ $errors->first() }}
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</body>
</html>
