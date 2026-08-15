<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — Ekaadh</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{DEFAULT:'#323891',dark:'#262a6d'},ink:'#0f1a2e',mute:'#64748b'},fontFamily:{sans:['Plus Jakarta Sans','sans-serif']}}}}</script>
</head>
<body class="bg-[#f4f6f8] min-h-screen flex items-center justify-center p-6 font-sans text-ink">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <img src="{{ asset('images/ekaadh-logo.png') }}" alt="ekaadh" class="h-12 w-auto mx-auto mb-3">
        <h1 class="text-2xl font-black">Admin</h1>
        <p class="text-mute text-sm mt-1">Platform control panel</p>
    </div>
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
        @if($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 text-red-700 text-sm p-3">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Email or phone</label>
                <input name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Password</label>
                <input type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">
            </div>
            <button class="w-full rounded-xl bg-brand text-white font-extrabold py-3.5 text-sm hover:bg-brand-dark">Sign in as Admin</button>
        </form>
    </div>
</div>
</body>
</html>
