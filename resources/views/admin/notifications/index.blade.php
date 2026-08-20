@extends('layouts.admin')
@section('title', 'Notifications')
@section('heading', 'Notifications')
@section('actions')
    @if(auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-mute hover:text-ink">Mark all read</button>
        </form>
    @endif
@endsection

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="divide-y divide-slate-50">
        @forelse($notifications as $note)
            @php $data = $note->data ?? []; @endphp
            <a href="{{ route('admin.notifications.open', $note->id) }}" class="block px-5 py-4 hover:bg-slate-50 {{ $note->read_at ? '' : 'bg-brand/5' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold text-ink">{{ $data['title'] ?? 'Notification' }}</p>
                        <p class="text-sm text-mute mt-0.5">{{ $data['body'] ?? '' }}</p>
                    </div>
                    <span class="text-[11px] text-mute shrink-0">{{ $note->created_at?->diffForHumans() }}</span>
                </div>
            </a>
        @empty
            <p class="px-5 py-12 text-center text-sm text-mute">No notifications yet. You’ll see organizer applications, events for review, and support messages here.</p>
        @endforelse
    </div>
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
