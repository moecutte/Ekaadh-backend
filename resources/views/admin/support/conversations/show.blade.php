@extends('layouts.admin')
@section('title', 'Conversation #'.$conversation->id)
@section('heading', 'Support · #'.$conversation->id)

@section('content')
<div class="mb-4 flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.support.conversations.index') }}" class="text-sm font-bold text-brand">&larr; Inbox</a>
    <span class="text-sm text-mute">{{ $conversation->displayName() }} · {{ $conversation->displayContact() ?: 'No contact' }}</span>
    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $conversation->status === 'open' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-50 text-mute border-slate-100' }}">{{ $conversation->status }}</span>
    @if($conversation->status === 'open')
        <form method="POST" action="{{ route('admin.support.conversations.close', $conversation) }}">@csrf
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-mute">Close</button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.support.conversations.reopen', $conversation) }}">@csrf
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-brand">Reopen</button>
        </form>
    @endif
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-4 min-h-[420px] flex flex-col">
        <div class="flex-1 space-y-3 overflow-y-auto max-h-[520px] pr-1">
            @forelse($conversation->messages as $message)
                @php
                    $isAdmin = $message->sender_type === 'admin';
                    $isSystem = $message->sender_type === 'system';
                @endphp
                <div class="flex {{ $isAdmin || $isSystem ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm {{ $isSystem ? 'bg-slate-100 text-ink border border-slate-200' : ($isAdmin ? 'bg-brand text-white' : 'bg-slate-50 text-ink border border-slate-100') }}">
                        @if($isSystem)
                            <div class="text-[10px] font-bold uppercase tracking-wide opacity-60 mb-1">FAQ</div>
                        @elseif($isAdmin)
                            <div class="text-[10px] font-bold uppercase tracking-wide opacity-70 mb-1">Support</div>
                        @endif
                        <div class="whitespace-pre-line">{{ $message->body }}</div>
                        <div class="text-[10px] mt-2 opacity-60">{{ $message->created_at->format('M j, H:i') }}</div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-mute text-center py-10">No messages yet.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 h-fit">
        <h3 class="font-bold mb-3">Reply</h3>
        @if($conversation->status === 'open')
            <form method="POST" action="{{ route('admin.support.conversations.reply', $conversation) }}" class="space-y-3">
                @csrf
                <textarea name="body" rows="6" required maxlength="2000" placeholder="Type your reply…" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand"></textarea>
                <button class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Send reply</button>
            </form>
        @else
            <p class="text-sm text-mute">This conversation is closed. Reopen it to send a reply.</p>
        @endif
    </div>
</div>
@endsection
