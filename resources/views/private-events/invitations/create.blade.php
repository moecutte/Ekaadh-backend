@extends('layouts.app')

@section('title', __('ui.send_invitations'))

@section('content')
@php
    $thumb = $event->invitationDesign?->thumbnail_url
        ?: $event->invitationDesign?->graphic_url
        ?: $event->cover_image;
    $remaining = $event->ticketTypes->sum(fn ($t) => $t->remaining());
@endphp
<div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12">
        <a href="{{ route('private-events.invitations.index', $event) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-mute hover:text-brand transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('ui.back_to_invitations') }}
        </a>

        <div class="mt-5 mb-8 flex flex-wrap items-start gap-5">
            @if($thumb)
                <div class="w-20 h-[6.5rem] rounded-xl overflow-hidden border border-slate-100 shadow-md shrink-0 bg-slate-100">
                    <img src="{{ $thumb }}" alt="" class="w-full h-full object-cover">
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-brand mb-2">{{ __('ui.guest_delivery') }}</p>
                <h1 class="text-3xl font-extrabold tracking-tight text-ink">{{ __('ui.send_invitations') }}</h1>
                <p class="text-sm text-mute mt-2">{{ $event->title }} · {{ __('ui.seats_left_invite_note', ['remaining' => $remaining]) }}</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
                <ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid lg:grid-cols-5 gap-6" x-data="inviteForm()">
            <form method="POST" action="{{ route('private-events.invitations.store', $event) }}"
                  class="lg:col-span-3 bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 p-6 sm:p-7 space-y-5">
                @csrf
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-ink">{{ __('ui.add_guests') }}</h3>
                        <p class="text-[11px] text-mute mt-0.5">{{ __('ui.add_guests_hint') }}</p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full bg-brand-soft text-brand" x-text="rows.length + ' ' + (rows.length === 1 ? guestLabel : guestsLabel)"></span>
                </div>

                <div class="space-y-3">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="relative rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50/80 to-white p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-mute" x-text="guestNLabel.replace(':n', index + 1)"></span>
                                <button type="button" class="text-[11px] font-bold text-red-400 hover:text-red-600 transition-colors" @click="remove(index)" x-show="rows.length > 1">{{ __('ui.remove') }}</button>
                            </div>
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="text-[11px] font-bold text-mute block mb-1">{{ __('ui.name') }}</label>
                                    <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition" :name="'guests['+index+'][name]'" x-model="row.name" placeholder="{{ __('ui.guest_name') }}">
                                </div>
                                <div class="col-span-12 sm:col-span-4">
                                    <label class="text-[11px] font-bold text-mute block mb-1">{{ __('ui.phone') }} *</label>
                                    <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition" :name="'guests['+index+'][phone]'" x-model="row.phone" required placeholder="61xxxxxxx">
                                </div>
                                <div class="col-span-4 sm:col-span-3">
                                    <label class="text-[11px] font-bold text-mute block mb-1">{{ __('ui.qty') }}</label>
                                    <input type="number" min="1" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition" :name="'guests['+index+'][quantity]'" x-model="row.quantity" required>
                                </div>
                                <div class="col-span-8 sm:col-span-12">
                                    <label class="text-[11px] font-bold text-mute block mb-1">{{ __('ui.ticket_type') }}</label>
                                    <select class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition" :name="'guests['+index+'][ticket_type_id]'" x-model="row.ticket_type_id" required>
                                        @foreach($event->ticketTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->remaining() }} {{ __('ui.left_label') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <button type="button" @click="add()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-brand hover:text-brand-dark transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('ui.add_another_guest') }}
                    </button>
                    <button type="submit"
                            class="ml-auto inline-flex items-center gap-2 px-5 py-3 text-sm font-bold text-white bg-brand rounded-2xl shadow-lg shadow-brand/20 hover:bg-brand-dark hover:-translate-y-0.5 transition-all">
                        {{ __('ui.issue_send') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('private-events.invitations.store', $event) }}" enctype="multipart/form-data"
                  class="lg:col-span-2 bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 p-6 sm:p-7 space-y-5 h-fit">
                @csrf
                <div>
                    <div class="w-11 h-11 rounded-xl bg-brand-soft flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <h3 class="text-sm font-extrabold text-ink">{{ __('ui.upload_csv') }}</h3>
                    <p class="text-[11px] text-mute mt-1 leading-relaxed">{{ __('ui.upload_csv_hint') }}</p>
                </div>
                <div>
                    <label class="text-xs font-bold text-mute block mb-1.5">{{ __('ui.default_ticket_type') }}</label>
                    <select name="default_ticket_type_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition">
                        @foreach($event->ticketTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} — {{ $type->remaining() }} {{ __('ui.left_label') }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex flex-col items-center justify-center gap-2 w-full rounded-xl border border-dashed border-slate-200 bg-slate-50/40 px-4 py-8 cursor-pointer hover:border-brand/40 hover:bg-brand-soft/30 transition">
                    <span class="text-xs font-semibold text-mute">{{ __('ui.drop_csv') }}</span>
                    <input type="file" name="csv" accept=".csv,text/csv,text/plain" required class="sr-only">
                </label>
                <button type="submit" class="w-full px-5 py-3 text-sm font-bold text-white bg-brand rounded-2xl shadow-lg shadow-brand/20 hover:bg-brand-dark transition-colors">
                    {{ __('ui.upload_send') }}
                </button>
            </form>
        </div>
    </div>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function inviteForm() {
    const defaultType = @json($event->ticketTypes->first()?->id);
    return {
        rows: [{ name: '', phone: '', quantity: 1, ticket_type_id: defaultType }],
        guestLabel: @js(__('ui.guest')),
        guestsLabel: @js(__('ui.guests')),
        guestNLabel: @js(__('ui.guest_n')),
        add() { this.rows.push({ name: '', phone: '', quantity: 1, ticket_type_id: defaultType }); },
        remove(i) { this.rows.splice(i, 1); },
    }
}
</script>
@endsection
