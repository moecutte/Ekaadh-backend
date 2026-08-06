@extends('layouts.admin')
@section('title', 'Support inbox')
@section('heading', 'Support inbox')

@section('content')
<form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <div class="flex flex-wrap gap-3">
        <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
            <option value="">All statuses</option>
            <option value="open" @selected(request('status')==='open')>Open</option>
            <option value="closed" @selected(request('status')==='closed')>Closed</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm px-3 py-2.5 rounded-xl border border-slate-200">
            <input type="checkbox" name="unread" value="1" @checked(request()->boolean('unread'))>
            Unread only
        </label>
        <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
        <a href="{{ route('admin.support.conversations.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-mute">Clear</a>
        <a href="{{ route('admin.support.faqs.index') }}" class="ml-auto px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-brand">Manage FAQs</a>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
    @forelse($conversations as $conversation)
        <a href="{{ route('admin.support.conversations.show', $conversation) }}"
           class="block p-4 hover:bg-slate-50 transition {{ $conversation->hasUnreadForAdmin() ? 'bg-brand/5' : '' }}">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[180px]">
                    <div class="font-bold">{{ $conversation->displayName() }}</div>
                    <div class="text-xs text-mute mt-0.5">
                        {{ $conversation->displayContact() ?: 'No contact' }}
                        · {{ strtoupper($conversation->channel) }}
                        · #{{ $conversation->id }}
                    </div>
                </div>
                <div class="text-xs text-mute">{{ $conversation->last_message_at?->diffForHumans() ?: 'No messages' }}</div>
                @if($conversation->status === 'open')
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-100">open</span>
                @else
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-slate-50 text-mute border-slate-100">closed</span>
                @endif
                @if($conversation->hasUnreadForAdmin())
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-brand text-white">new</span>
                @endif
            </div>
        </a>
    @empty
        <div class="px-4 py-12 text-center text-mute text-sm">No support conversations yet.</div>
    @endforelse
</div>
<div class="mt-4">{{ $conversations->links() }}</div>
@endsection
