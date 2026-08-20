@extends('layouts.organizer')
@section('title', 'Send invitations')
@section('heading', 'Send complimentary invitations')

@section('content')
@php
    $remaining = $event->ticketTypes->sum(fn ($t) => $t->remaining());
@endphp
<a href="{{ route('organizer.events.invitations.index', $event) }}" class="text-sm font-bold text-mute hover:text-brand">&larr; Guest list</a>
<p class="text-sm text-mute mt-2 mb-5">{{ $event->title }} · {{ $remaining }} seats left · {{ $guestSlots }}/{{ $guestLimit }} complimentary guest slots left. Guests get a private link and do not pay.</p>

@if($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid lg:grid-cols-5 gap-5" x-data="inviteForm()">
    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <p class="text-xs font-bold uppercase text-mute mb-2">Send via</p>
        <div class="grid grid-cols-2 gap-3 max-w-lg">
            <button type="button" @click="channel = 'whatsapp'"
                    :class="channel === 'whatsapp' ? 'border-brand bg-brand/5 text-brand' : 'border-slate-200 bg-white'"
                    class="rounded-2xl border-2 px-4 py-3 text-sm font-extrabold">WhatsApp</button>
            <button type="button" @click="channel = 'sms'"
                    :class="channel === 'sms' ? 'border-brand bg-brand/5 text-brand' : 'border-slate-200 bg-white'"
                    class="rounded-2xl border-2 px-4 py-3 text-sm font-extrabold">SMS</button>
        </div>
    </div>

    <form method="POST" action="{{ route('organizer.events.invitations.store', $event) }}" class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        @csrf
        <input type="hidden" name="channel" :value="channel">
        <h3 class="text-sm font-bold">Add guests</h3>
        <p class="text-[11px] text-mute"><span x-text="rows.length"></span>/{{ $guestSlots }} in this batch · {{ $guestLimit }} total per event</p>
        <template x-for="(row, index) in rows" :key="index">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-2">
                <div class="flex justify-between">
                    <span class="text-[10px] font-bold uppercase text-mute" x-text="'Guest ' + (index + 1)"></span>
                    <button type="button" class="text-xs font-bold text-red-400" @click="remove(index)" x-show="rows.length > 1">Remove</button>
                </div>
                <div class="grid grid-cols-12 gap-2">
                    <input class="col-span-12 sm:col-span-5 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Name" :name="'guests['+index+'][name]'" x-model="row.name">
                    <input class="col-span-8 sm:col-span-4 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Phone *" :name="'guests['+index+'][phone]'" x-model="row.phone" required>
                    <input type="number" min="1" class="col-span-4 sm:col-span-3 rounded-lg border border-slate-200 px-3 py-2 text-sm" :name="'guests['+index+'][quantity]'" x-model="row.quantity" required>
                    <select class="col-span-12 rounded-lg border border-slate-200 px-3 py-2 text-sm" :name="'guests['+index+'][ticket_type_id]'" x-model="row.ticket_type_id" required>
                        @foreach($event->ticketTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->remaining() }} left)</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </template>
        <div class="flex items-center gap-3">
            <button type="button" @click="add()" class="text-xs font-bold text-brand" x-show="rows.length < maxGuests">+ Add another guest</button>
            <button class="ml-auto px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Issue & send</button>
        </div>
    </form>

    <form method="POST" action="{{ route('organizer.events.invitations.store', $event) }}" enctype="multipart/form-data" class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4 h-fit">
        @csrf
        <input type="hidden" name="channel" :value="channel">
        <h3 class="text-sm font-bold">Upload CSV</h3>
        <p class="text-[11px] text-mute">Columns: phone, name, quantity, ticket_type. Max {{ $guestSlots }} guests in this upload.</p>
        <select name="default_ticket_type_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach($event->ticketTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }} — {{ $type->remaining() }} left</option>
            @endforeach
        </select>
        <input type="file" name="csv" accept=".csv,text/csv,text/plain" required class="block w-full text-xs">
        <button class="w-full px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Upload & send</button>
    </form>
</div>
<script>
function inviteForm() {
    const defaultType = @json($event->ticketTypes->first()?->id);
    const maxGuests = @json((int) $guestSlots);
    return {
        rows: [{ name: '', phone: '', quantity: 1, ticket_type_id: defaultType }],
        channel: @json(old('channel', 'whatsapp')),
        maxGuests,
        add() {
            if (this.rows.length >= this.maxGuests) return;
            this.rows.push({ name: '', phone: '', quantity: 1, ticket_type_id: defaultType });
        },
        remove(i) { this.rows.splice(i, 1); },
    }
}
</script>
@endsection
