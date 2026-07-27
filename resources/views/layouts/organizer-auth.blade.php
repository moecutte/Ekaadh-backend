<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Ekaadh Organizer</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{DEFAULT:'#323891',soft:'#eef0f8',dark:'#262a6d'},ink:'#0f1a2e',mute:'#64748b'},fontFamily:{sans:['Plus Jakarta Sans','sans-serif']}}}}</script>
</head>
<body class="bg-[#f4f6f8] text-ink antialiased min-h-screen font-sans flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="{{ asset('images/ekaadh-logo.png') }}" alt="ekaadh" class="h-12 w-auto mx-auto mb-3">
            <h1 class="text-2xl font-black">Organizer</h1>
            <p class="text-mute text-sm mt-1">@yield('subtitle')</p>
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
