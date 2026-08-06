@extends('layouts.admin')
@section('title', 'Support FAQs')
@section('heading', 'Support FAQs')

@section('content')
<div class="grid lg:grid-cols-3 gap-5 mb-5">
    <div class="lg:col-span-2 space-y-5">
        <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex flex-wrap gap-3">
                <input name="q" value="{{ request('q') }}" placeholder="Search FAQs…" class="flex-1 min-w-[180px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand">
                <select name="locale" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <option value="">All languages</option>
                    <option value="en" @selected(request('locale')==='en')>English</option>
                    <option value="so" @selected(request('locale')==='so')>Somali</option>
                </select>
                <button class="px-4 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Filter</button>
            </div>
        </form>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
            @forelse($faqs as $faq)
                <div class="p-4" x-data="{ editing: false }">
                    <div x-show="!editing">
                        <div class="flex flex-wrap items-start gap-3">
                            <div class="flex-1 min-w-[180px]">
                                <div class="text-[11px] font-bold uppercase text-mute mb-1">{{ strtoupper($faq->locale) }} · order {{ $faq->sort_order }}</div>
                                <div class="font-bold">{{ $faq->question }}</div>
                                <p class="text-sm text-mute mt-2 whitespace-pre-line">{{ $faq->answer }}</p>
                            </div>
                            @if($faq->is_active)
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-100">active</span>
                            @else
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border bg-slate-50 text-mute border-slate-100">inactive</span>
                            @endif
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="editing = true" class="px-2.5 py-1 rounded-lg bg-slate-100 text-ink text-xs font-bold">Edit</button>
                                <form method="POST" action="{{ route('admin.support.faqs.toggle', $faq) }}">@csrf
                                    <button class="px-2.5 py-1 rounded-lg bg-slate-50 text-mute text-xs font-bold border border-slate-200">{{ $faq->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.support.faqs.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ?')">
                                    @csrf @method('DELETE')
                                    <button class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-bold">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <form x-cloak x-show="editing" method="POST" action="{{ route('admin.support.faqs.update', $faq) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Language</label>
                                <select name="locale" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                    <option value="en" @selected($faq->locale==='en')>English</option>
                                    <option value="so" @selected($faq->locale==='so')>Somali</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Sort order</label>
                                <input type="number" name="sort_order" value="{{ $faq->sort_order }}" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Question</label>
                            <input name="question" value="{{ $faq->question }}" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-mute block mb-1">Answer</label>
                            <textarea name="answer" rows="4" required maxlength="5000" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ $faq->answer }}</textarea>
                        </div>
                        <select name="is_active" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="1" @selected($faq->is_active)>Active</option>
                            <option value="0" @selected(! $faq->is_active)>Inactive</option>
                        </select>
                        <div class="flex gap-2">
                            <button class="px-3 py-2 rounded-xl bg-brand text-white text-xs font-bold">Save</button>
                            <button type="button" @click="editing = false" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-mute">Cancel</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-mute text-sm">No FAQs yet. Add your first question on the right.</div>
            @endforelse
        </div>
        <div>{{ $faqs->links() }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 h-fit">
        <h3 class="font-bold mb-1">Add FAQ</h3>
        <p class="text-xs text-mute mb-4">Shown in the support widget before customers message an agent.</p>
        <form method="POST" action="{{ route('admin.support.faqs.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Language</label>
                <select name="locale" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="en">English</option>
                    <option value="so">Somali</option>
                </select>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Question</label>
                <input name="question" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Answer</label>
                <textarea name="answer" rows="5" required maxlength="5000" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">Sort order</label>
                <input type="number" name="sort_order" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Auto">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-brand">
                Active
            </label>
            <button class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Create FAQ</button>
        </form>
    </div>
</div>
@endsection
