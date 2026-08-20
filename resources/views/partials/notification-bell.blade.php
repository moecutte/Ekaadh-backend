@php
    $noteUser = auth()->user();
    $unreadCount = $noteUser?->unreadNotifications()->count() ?? 0;
    $latestNotes = $noteUser?->notifications()->latest()->limit(8)->get() ?? collect();
    $indexRoute = $indexRoute ?? '#';
    $openRoute = $openRoute ?? null;
    $readAllRoute = $readAllRoute ?? null;
@endphp
<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" @click="open = !open" class="relative flex w-8 h-8 rounded-full bg-gold/15 items-center justify-center text-gold hover:bg-gold/25 transition-colors" title="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        @if($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-extrabold leading-4 text-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>
    <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-slate-100 shadow-lg z-50 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-50 flex items-center justify-between">
            <p class="text-sm font-extrabold text-ink">Notifications</p>
            @if($readAllRoute && $unreadCount > 0)
                <form method="POST" action="{{ $readAllRoute }}">
                    @csrf
                    <button class="text-[11px] font-bold text-brand">Mark all read</button>
                </form>
            @endif
        </div>
        <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
            @forelse($latestNotes as $note)
                @php $data = $note->data ?? []; @endphp
                <a href="{{ $openRoute ? route($openRoute, $note->id) : ($data['url'] ?? $indexRoute) }}" class="block px-4 py-3 hover:bg-slate-50 {{ $note->read_at ? '' : 'bg-brand/5' }}">
                    <p class="text-sm font-bold text-ink leading-snug">{{ $data['title'] ?? 'Notification' }}</p>
                    <p class="text-xs text-mute mt-0.5 leading-relaxed">{{ $data['body'] ?? '' }}</p>
                    <p class="text-[11px] text-mute mt-1">{{ $note->created_at?->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-mute">No notifications yet.</p>
            @endforelse
        </div>
        <a href="{{ $indexRoute }}" class="block px-4 py-2.5 text-center text-xs font-bold text-brand border-t border-slate-50 hover:bg-slate-50">View all</a>
    </div>
</div>
