@extends('layouts.app')

@section('title', __('ui.send_invitations'))

@section('content')
@php
    $thumb = $event->invitationDesign?->thumbnail_url
        ?: $event->invitationDesign?->graphic_url
        ?: $event->cover_image;
    $remaining = $event->ticketTypes->sum(fn ($t) => $t->remaining());
    $card = 'rounded-[1.75rem] bg-white border border-slate-100 shadow-[0_18px_40px_-28px_rgba(15,26,46,0.35)] overflow-hidden';
    $bar = 'h-1 bg-gradient-to-r from-brand via-[#4a51b8] to-brand/40';
@endphp
<div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-7">
        <a href="{{ route('private-events.invitations.index', $event) }}" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-mute hover:text-brand transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('ui.back_to_invitations') }}
        </a>

        <header class="mt-4 mb-4 {{ $card }}">
            <div class="{{ $bar }}"></div>
            <div class="px-5 sm:px-6 py-4 sm:py-5">
                <div class="flex flex-wrap items-start gap-3.5">
                    @if($thumb)
                        <div class="w-14 h-[4.5rem] sm:w-16 sm:h-[5.25rem] rounded-xl overflow-hidden border border-slate-100 shrink-0 bg-slate-100">
                            <img src="{{ $thumb }}" alt="" class="w-full h-full object-cover">
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.guest_delivery') }}</p>
                        <h1 class="mt-1 text-xl sm:text-2xl font-extrabold tracking-tight text-ink leading-tight">{{ __('ui.send_invitations') }}</h1>
                        <p class="text-sm text-mute mt-1.5">{{ $event->title }} · {{ __('ui.seats_left_invite_note', ['remaining' => $remaining]) }}</p>
                    </div>
                </div>
            </div>
        </header>

        @if($errors->any())
            <div class="mb-5 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm font-semibold px-4 py-3.5">
                <ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid lg:grid-cols-5 gap-5" x-data="inviteForm()">
            <div class="lg:col-span-5 {{ $card }}">
                <div class="{{ $bar }}"></div>
                <div class="px-5 sm:px-7 py-5 sm:py-6">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand mb-1">{{ __('ui.invite_send_via') }}</p>
                    <p class="text-[13px] text-mute mb-4">{{ __('ui.invite_channel_hint') }}</p>
                    <div class="grid grid-cols-2 gap-3 max-w-lg">
                        <button type="button" @click="channel = 'whatsapp'"
                                :class="channel === 'whatsapp' ? 'border-brand bg-brand-soft text-brand' : 'border-slate-200 bg-white text-ink hover:border-brand/40'"
                                class="flex items-center justify-center gap-2 rounded-2xl border-2 px-4 py-3 text-sm font-extrabold transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
                            {{ __('ui.invite_channel_whatsapp') }}
                        </button>
                        <button type="button" @click="channel = 'sms'"
                                :class="channel === 'sms' ? 'border-brand bg-brand-soft text-brand' : 'border-slate-200 bg-white text-ink hover:border-brand/40'"
                                class="flex items-center justify-center gap-2 rounded-2xl border-2 px-4 py-3 text-sm font-extrabold transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.24-.93L3 20l1.04-3.12A7.24 7.24 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            {{ __('ui.invite_channel_sms') }}
                        </button>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('private-events.invitations.store', $event) }}"
                  class="lg:col-span-3 {{ $card }}">
                <div class="{{ $bar }}"></div>
                <div class="px-5 sm:px-7 py-6 sm:py-7 space-y-5">
                @csrf
                <input type="hidden" name="channel" value="whatsapp" :value="channel">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.add_guests') }}</p>
                        <p class="text-[13px] text-mute mt-1">{{ __('ui.add_guests_hint') }}</p>
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
                                    <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition" :name="'guests['+index+'][phone]'" x-model="row.phone" required placeholder="63xxxxxxx">
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
                </div>
            </form>

            <form method="POST" action="{{ route('private-events.invitations.store', $event) }}" enctype="multipart/form-data"
                  class="lg:col-span-2 {{ $card }} h-fit">
                <div class="{{ $bar }}"></div>
                <div class="px-5 sm:px-7 py-6 sm:py-7 space-y-5">
                @csrf
                <input type="hidden" name="channel" value="whatsapp" :value="channel">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.upload_csv') }}</p>
                    <p class="text-[13px] text-mute mt-1.5 leading-relaxed">{{ __('ui.upload_csv_hint') }}</p>
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
                </div>
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
        channel: @json(old('channel', 'whatsapp')),
        guestLabel: @js(__('ui.guest')),
        guestsLabel: @js(__('ui.guests')),
        guestNLabel: @js(__('ui.guest_n')),
        add() { this.rows.push({ name: '', phone: '', quantity: 1, ticket_type_id: defaultType }); },
        remove(i) { this.rows.splice(i, 1); },
    }
}
</script>
@endsection
