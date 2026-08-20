@extends('layouts.organizer')
@section('title', $event->exists ? 'Edit Event' : 'Create Event')
@section('heading', $event->exists ? 'Edit Event' : 'Create Event')

@section('content')
@if($errors->any())
    <div class="mb-3 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-3">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $event->exists ? route('organizer.events.update', $event) : route('organizer.events.store') }}" enctype="multipart/form-data" class="max-w-3xl mx-auto space-y-3" x-data="eventForm()" x-init="if (!inviteRows.length) addInvite()">
    @csrf
    @if($event->exists) @method('PUT') @endif
    <input type="hidden" name="pricing_type" :value="pricingType">
    <input type="hidden" name="package_id" :value="pricingType === 'free' ? packageId : ''">

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-3">
        <div class="flex items-baseline justify-between gap-3">
            <h3 class="text-sm font-bold">Event type</h3>
            <p class="text-[11px] text-mute hidden sm:block">Locked after payment or sales.</p>
        </div>
        <div class="grid sm:grid-cols-2 gap-2">
            <button
                type="button"
                @click="setPricing('free')"
                :disabled="pricingLocked"
                :class="pricingType === 'free' ? 'border-brand bg-brand/5 ring-1 ring-brand/30' : 'border-slate-200 bg-white hover:border-brand/40'"
                class="text-left rounded-xl border px-3 py-2.5 transition disabled:opacity-60"
            >
                <p class="text-sm font-extrabold text-ink">Free event</p>
                <p class="text-[11px] text-mute mt-0.5">Guests claim tickets. You pay a capacity package.</p>
            </button>
            <button
                type="button"
                @click="setPricing('paid')"
                :disabled="pricingLocked"
                :class="pricingType === 'paid' ? 'border-brand bg-brand/5 ring-1 ring-brand/30' : 'border-slate-200 bg-white hover:border-brand/40'"
                class="text-left rounded-xl border px-3 py-2.5 transition disabled:opacity-60"
            >
                <p class="text-sm font-extrabold text-ink">Priced event</p>
                <p class="text-[11px] text-mute mt-0.5">Sell tickets. Platform keeps {{ number_format((float) $commissionRate, 1) }}% commission.</p>
            </button>
        </div>
        <p x-show="pricingLocked" x-cloak class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-1.5">Event type is locked because this event is already paid or has ticket sales.</p>

        <div x-show="pricingType === 'free'" x-cloak class="pt-1 border-t border-slate-50 space-y-2">
            <p class="text-[11px] font-bold uppercase tracking-wide text-mute">Capacity package</p>
            @if($freePackages->isEmpty())
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">No free-event packages yet. Ask an admin to create them under Packages.</p>
            @else
                <div class="grid sm:grid-cols-3 gap-2">
                    <template x-for="pkg in packages" :key="pkg.id">
                        <button
                            type="button"
                            @click="selectPackage(pkg)"
                            :disabled="pricingLocked"
                            :class="String(packageId) === String(pkg.id) ? 'border-brand bg-brand/5 ring-1 ring-brand/30' : 'border-slate-200 hover:border-brand/40'"
                            class="text-left rounded-xl border px-3 py-2.5 transition disabled:opacity-60"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-extrabold text-ink truncate" x-text="pkg.name"></p>
                                <p class="text-sm font-black text-brand shrink-0" x-text="pkg.price_label"></p>
                            </div>
                            <p class="text-[11px] text-mute mt-0.5" x-text="pkg.range_label"></p>
                        </button>
                    </template>
                </div>
                <p class="text-[11px] text-mute" x-show="selectedPackage" x-cloak>
                    Ticket quantity must be <span class="font-bold text-ink" x-text="selectedPackage?.range_label"></span>.
                </p>
            @endif
            @if($event->exists && $event->needsPackagePayment())
                <a href="{{ route('organizer.events.pay', $event) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand text-white text-xs font-bold">Pay package now</a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-3">
        <h3 class="text-sm font-bold">Event details</h3>
        <div class="grid sm:grid-cols-3 gap-2.5">
            <div class="sm:col-span-2">
                <label class="text-[11px] font-bold text-mute block mb-1">Title *</label>
                <input name="title" value="{{ old('title', $event->title) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-[11px] font-bold text-mute block mb-1">Category *</label>
                <select name="category" class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm">
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(old('category', $event->category)===$cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid sm:grid-cols-5 gap-2.5">
            <div class="sm:col-span-3">
                <label class="text-[11px] font-bold text-mute block mb-1">Description *</label>
                <textarea name="description" rows="4" required class="w-full h-[148px] rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm outline-none focus:border-brand resize-none">{{ old('description', $event->description) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="text-[11px] font-bold text-mute block mb-1">Cover image</label>
                <input
                    type="file"
                    name="cover_image"
                    x-ref="coverInput"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    class="hidden"
                    @change="onFileSelect($event)"
                >
                <div
                    class="relative h-[148px] rounded-lg border-2 border-dashed border-slate-200 overflow-hidden bg-slate-50/50 hover:border-brand/40 transition-colors"
                    @dragover.prevent="dragOver = true"
                    @dragleave.prevent="dragOver = false"
                    @drop.prevent="onDrop($event)"
                    :class="dragOver && 'border-brand bg-brand/5'"
                >
                    <div x-show="previewUrl" x-cloak class="relative group h-full">
                        <img :src="previewUrl" alt="Cover preview" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <button type="button" @click="$refs.coverInput.click()" class="px-3 py-1.5 rounded-lg bg-white text-xs font-bold text-ink shadow">Change</button>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-show="!previewUrl"
                        @click="$refs.coverInput.click()"
                        class="flex flex-col items-center justify-center w-full h-full px-3 text-center"
                    >
                        <span class="text-sm font-semibold text-ink">Upload cover</span>
                        <span class="text-[11px] text-mute mt-0.5">PNG, JPG, WEBP · 5 MB</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
            <div>
                <label class="text-[11px] font-bold text-mute block mb-1">Date *</label>
                <input type="date" name="event_date" value="{{ old('event_date', optional($event->event_date)->format('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold text-mute block mb-1">Time *</label>
                <input type="time" name="event_time" value="{{ old('event_time', $event->event_time ? substr((string)$event->event_time,0,5) : '18:00') }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold text-mute block mb-1">Venue *</label>
                <input name="venue" value="{{ old('venue', $event->venue) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold text-mute block mb-1">City</label>
                <select name="city" class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm">
                    <option value="">Select city</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}" @selected(old('city', $event->city)===$city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2 lg:col-span-4">
                <label class="text-[11px] font-bold text-mute block mb-1">Address</label>
                <input name="address" value="{{ old('address', $event->address) }}" class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <button type="button" @click="showSpeakers = !showSpeakers; if (showSpeakers && !speakerRows.length) addSpeaker()" class="w-full flex items-center justify-between gap-3 text-left">
            <div>
                <h3 class="text-sm font-bold">Speakers & special guests <span class="font-semibold text-mute">(optional)</span></h3>
                <p class="text-[11px] text-mute">Shown on the public event page.</p>
            </div>
            <span class="text-xs font-bold text-brand shrink-0" x-text="showSpeakers ? 'Hide' : 'Add'"></span>
        </button>
        <div x-show="showSpeakers" x-cloak class="mt-3 space-y-2">
            <div class="flex justify-end">
                <button type="button" @click="addSpeaker()" class="text-xs font-bold text-brand" x-show="speakerRows.length < 20">+ Add person</button>
            </div>
            <template x-for="(speaker, index) in speakerRows" :key="index">
                <div class="rounded-xl border border-slate-100 p-2.5 space-y-1.5">
                    <input type="hidden" :name="'speakers['+index+'][id]'" x-model="speaker.id">
                    <div class="grid grid-cols-12 gap-1.5 items-center">
                        <label class="col-span-2 sm:col-span-1 relative w-12 h-12 rounded-full overflow-hidden bg-slate-100 border border-slate-200 cursor-pointer shrink-0">
                            <img x-show="speaker.photo_url" :src="speaker.photo_url" class="w-full h-full object-cover" alt="">
                            <span x-show="!speaker.photo_url" class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-mute">Photo</span>
                            <input type="file" class="hidden" accept="image/png,image/jpeg,image/jpg,image/webp" :name="'speakers['+index+'][photo]'" @change="onSpeakerPhoto($event, index)">
                        </label>
                        <input class="col-span-10 sm:col-span-5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="Name" :name="'speakers['+index+'][name]'" x-model="speaker.name">
                        <input class="col-span-9 sm:col-span-5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="Role, e.g. Keynote" :name="'speakers['+index+'][role]'" x-model="speaker.role">
                        <button type="button" class="col-span-3 sm:col-span-1 text-xs font-bold text-red-400" @click="removeSpeaker(index)">✕</button>
                    </div>
                    <input class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="Short bio (optional)" :name="'speakers['+index+'][bio]'" x-model="speaker.bio">
                </div>
            </template>
            <p class="text-[11px] text-mute" x-show="!speakerRows.length">Add hosts, speakers, or special guests.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <button type="button" @click="showProgramme = !showProgramme; if (showProgramme && !programmeRows.length) addProgramme()" class="w-full flex items-center justify-between gap-3 text-left">
            <div>
                <h3 class="text-sm font-bold">Programme <span class="font-semibold text-mute">(optional)</span></h3>
                <p class="text-[11px] text-mute">Time slots, e.g. 8:00–8:30 Opening remarks.</p>
            </div>
            <span class="text-xs font-bold text-brand shrink-0" x-text="showProgramme ? 'Hide' : 'Add'"></span>
        </button>
        <div x-show="showProgramme" x-cloak class="mt-3">
            <div class="flex items-center justify-between mb-2">
                <div class="hidden sm:grid grid-cols-12 gap-1.5 text-[11px] font-bold uppercase tracking-wide text-mute flex-1">
                    <span class="col-span-2">Start</span>
                    <span class="col-span-2">End</span>
                    <span class="col-span-7">What happens</span>
                    <span class="col-span-1"></span>
                </div>
                <button type="button" @click="addProgramme()" class="text-xs font-bold text-brand shrink-0 ml-2" x-show="programmeRows.length < 40">+ Add slot</button>
            </div>
            <template x-for="(slot, index) in programmeRows" :key="index">
                <div class="grid grid-cols-12 gap-1.5 mb-1.5 items-center">
                    <input type="hidden" :name="'programme['+index+'][id]'" x-model="slot.id">
                    <input type="time" class="col-span-6 sm:col-span-2 rounded-lg border border-slate-200 px-2 py-1.5 text-sm" :name="'programme['+index+'][starts_at]'" x-model="slot.starts_at">
                    <input type="time" class="col-span-6 sm:col-span-2 rounded-lg border border-slate-200 px-2 py-1.5 text-sm" :name="'programme['+index+'][ends_at]'" x-model="slot.ends_at">
                    <input class="col-span-10 sm:col-span-7 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="e.g. Opening remarks" :name="'programme['+index+'][title]'" x-model="slot.title">
                    <button type="button" class="col-span-2 sm:col-span-1 text-xs font-bold text-red-400" @click="removeProgramme(index)">✕</button>
                    <input type="hidden" :name="'programme['+index+'][description]'" x-model="slot.description">
                </div>
            </template>
            <p class="text-[11px] text-mute" x-show="!programmeRows.length">Add the run of show, one time slot at a time.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <button type="button" @click="showGallery = !showGallery" class="w-full flex items-center justify-between gap-3 text-left">
            <div>
                <h3 class="text-sm font-bold">Event gallery <span class="font-semibold text-mute">(optional, max 12)</span></h3>
                <p class="text-[11px] text-mute">Extra photos for the public event page.</p>
            </div>
            <span class="text-xs font-bold text-brand shrink-0" x-text="showGallery ? 'Hide' : 'Add'"></span>
        </button>
        <div x-show="showGallery" x-cloak class="mt-3 space-y-2.5">
            @if(($galleryImages ?? collect())->isNotEmpty())
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                    @foreach($galleryImages as $image)
                        <div class="relative aspect-square rounded-lg overflow-hidden border border-slate-100" x-show="!galleryRemove.includes({{ $image->id }})">
                            <img src="{{ $image->path }}" alt="" class="w-full h-full object-cover">
                            <button type="button" @click="galleryRemove.push({{ $image->id }})" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/70 text-white text-[10px] font-bold">✕</button>
                        </div>
                    @endforeach
                </div>
                <template x-for="id in galleryRemove" :key="id">
                    <input type="hidden" name="gallery_remove[]" :value="id">
                </template>
            @endif
            <input type="file" name="gallery_images[]" accept="image/png,image/jpeg,image/jpg,image/webp" multiple class="block w-full text-xs text-mute file:mr-3 file:rounded-lg file:border-0 file:bg-brand/10 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-brand">
            <p class="text-[11px] text-mute">PNG, JPG, WEBP · up to 5 MB each.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2.5">
            <div>
                <h3 class="text-sm font-bold">Ticket types</h3>
                <p class="text-[11px] text-mute" x-text="pricingType === 'free' ? 'Prices are $0. Quantity must match the package.' : 'Set the prices buyers will pay.'"></p>
            </div>
            <button type="button" @click="add()" class="text-xs font-bold text-brand">+ Add row</button>
        </div>
        <div class="grid grid-cols-12 gap-1.5 mb-1 text-[11px] font-bold uppercase tracking-wide text-mute">
            <span class="col-span-4">Name</span>
            <span class="col-span-2">Price</span>
            <span class="col-span-2">Quantity</span>
            <span class="col-span-2">Max / order</span>
            <span class="col-span-2"></span>
        </div>
        <template x-for="(row, index) in rows" :key="index">
            <div class="grid grid-cols-12 gap-1.5 mb-1.5 items-center">
                <input type="hidden" :name="'tickets['+index+'][id]'" x-model="row.id">
                <input class="col-span-4 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="General Admission" :name="'tickets['+index+'][name]'" x-model="row.name" required>
                <input class="col-span-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="0.00" type="number" step="0.01" min="0" :name="'tickets['+index+'][price]'" x-model="row.price" :readonly="pricingType === 'free'" :class="pricingType === 'free' && 'bg-slate-100 text-mute'" required aria-label="Price">
                <input class="col-span-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="100" type="number" :name="'tickets['+index+'][quantity_available]'" x-model="row.quantity_available" required aria-label="Quantity">
                <input class="col-span-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="5" type="number" :name="'tickets['+index+'][max_per_order]'" x-model="row.max_per_order" required aria-label="Max per order">
                <input type="hidden" :name="'tickets['+index+'][description]'" x-model="row.description">
                <button type="button" class="col-span-2 text-xs font-bold text-red-400" @click="remove(index)" x-show="rows.length > 1">Remove</button>
            </div>
        </template>
        <p class="text-[11px] text-mute mt-1">Total tickets: <span class="font-bold text-ink" x-text="ticketTotal"></span></p>
    </div>

    @if($event->exists && $event->status === 'published')
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold">Complimentary guests</h3>
                <p class="text-[11px] text-mute">Private comps use the same ticket capacity.</p>
            </div>
            <a href="{{ route('organizer.events.invitations.index', $event) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand text-white text-xs font-bold">Manage guests</a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <button type="button" @click="showInvites = !showInvites" class="w-full flex items-center justify-between gap-3 text-left">
                <div>
                    <h3 class="text-sm font-bold">Complimentary guests <span class="font-semibold text-mute">(optional, max 15)</span></h3>
                    <p class="text-[11px] text-mute">Private invites send after publish. Up to 15 guests per event.</p>
                </div>
                <span class="text-xs font-bold text-brand shrink-0" x-text="showInvites ? 'Hide' : 'Add guests'"></span>
            </button>
            <div x-show="showInvites" x-cloak class="mt-3 space-y-2.5">
                <input type="hidden" name="invite_channel" :value="inviteChannel">
                <div class="flex items-center gap-2">
                    <button type="button" @click="inviteChannel = 'whatsapp'"
                            :class="inviteChannel === 'whatsapp' ? 'border-brand bg-brand/5 text-brand' : 'border-slate-200 bg-white'"
                            class="rounded-lg border px-3 py-1.5 text-xs font-extrabold">WhatsApp</button>
                    <button type="button" @click="inviteChannel = 'sms'"
                            :class="inviteChannel === 'sms' ? 'border-brand bg-brand/5 text-brand' : 'border-slate-200 bg-white'"
                            class="rounded-lg border px-3 py-1.5 text-xs font-extrabold">SMS</button>
                    <button type="button" @click="addInvite()" class="ml-auto text-xs font-bold text-brand" x-show="inviteRows.length < 15">+ Add guest</button>
                </div>
                <template x-for="(invite, index) in inviteRows" :key="index">
                    <div class="grid grid-cols-12 gap-1.5 items-center">
                        <input class="col-span-12 sm:col-span-4 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="Name" :name="'invites['+index+'][name]'" x-model="invite.name">
                        <input class="col-span-7 sm:col-span-3 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" placeholder="Phone" :name="'invites['+index+'][phone]'" x-model="invite.phone">
                        <input type="number" min="1" class="col-span-5 sm:col-span-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" :name="'invites['+index+'][quantity]'" x-model="invite.quantity">
                        <select class="col-span-10 sm:col-span-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm" :name="'invites['+index+'][ticket_name]'" x-model="invite.ticket_name">
                            <template x-for="(ticket, tIndex) in rows" :key="tIndex">
                                <option :value="ticket.name" x-text="ticket.name || 'Ticket'"></option>
                            </template>
                        </select>
                        <button type="button" class="col-span-2 sm:col-span-1 text-xs font-bold text-red-400" @click="removeInvite(index)" x-show="inviteRows.length > 1">✕</button>
                    </div>
                </template>
                <p class="text-[11px] text-mute"><span x-text="inviteRows.length"></span>/15 guests. Leave phones blank to skip.</p>
            </div>
        </div>
    @endif

    <div class="flex justify-end gap-2 sticky bottom-3 bg-page/90 backdrop-blur-sm py-2">
        <button type="submit" name="action" value="draft" class="px-4 py-2 text-sm font-bold text-mute bg-white border border-slate-200 rounded-xl">Save draft</button>
        <button type="submit" name="action" value="publish" class="px-4 py-2 text-sm font-bold text-white bg-brand rounded-xl hover:bg-brand-dark">Submit for review</button>
    </div>
</form>

<script>
function eventForm() {
    return {
        rows: @json(old('tickets', $ticketTypes->values())),
        previewUrl: @json($event->cover_image),
        fileName: '',
        dragOver: false,
        pricingType: @json(old('pricing_type', $event->pricing_type ?: 'paid')),
        packageId: @json(old('package_id', $event->package_id)),
        packages: @json($freePackages),
        pricingLocked: @json((bool) $pricingLocked),
        inviteRows: @json($pendingInvites ?: []),
        inviteChannel: @json($inviteChannel ?? 'whatsapp'),
        showInvites: @json(collect(old('invites', $pendingInvites ?? []))->contains(fn ($row) => filled(data_get($row, 'phone')))),
        speakerRows: @json($speakerRows ?? []),
        programmeRows: @json($programmeRows ?? []),
        galleryRemove: [],
        showSpeakers: @json(collect($speakerRows ?? [])->isNotEmpty()),
        showProgramme: @json(collect($programmeRows ?? [])->isNotEmpty()),
        showGallery: @json(($galleryImages ?? collect())->isNotEmpty()),
        get selectedPackage() {
            return (this.packages || []).find((p) => String(p.id) === String(this.packageId)) || null;
        },
        get ticketTotal() {
            return (this.rows || []).reduce((sum, row) => sum + (Number(row.quantity_available) || 0), 0);
        },
        setPricing(type) {
            if (this.pricingLocked) return;
            this.pricingType = type;
            if (type === 'free') {
                this.rows = this.rows.map((row) => ({ ...row, price: 0 }));
            }
        },
        selectPackage(pkg) {
            if (this.pricingLocked) return;
            this.packageId = pkg.id;
        },
        add() { this.rows.push({ id: null, name: '', description: '', price: this.pricingType === 'free' ? 0 : '', quantity_available: 100, max_per_order: 5 }); },
        remove(i) { this.rows.splice(i, 1); },
        addInvite() {
            if (this.inviteRows.length >= 15) return;
            this.inviteRows.push({ name: '', phone: '', quantity: 1, ticket_name: (this.rows[0] && this.rows[0].name) || '' });
        },
        removeInvite(i) { this.inviteRows.splice(i, 1); },
        addSpeaker() {
            if (this.speakerRows.length >= 20) return;
            this.speakerRows.push({ id: null, name: '', role: '', bio: '', photo_url: '' });
        },
        removeSpeaker(i) { this.speakerRows.splice(i, 1); },
        onSpeakerPhoto(e, index) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const prev = this.speakerRows[index].photo_url;
            if (prev && String(prev).startsWith('blob:')) URL.revokeObjectURL(prev);
            this.speakerRows[index].photo_url = URL.createObjectURL(file);
        },
        addMinutes(time, minutes) {
            const parts = String(time || '08:00').split(':');
            let total = (Number(parts[0]) || 8) * 60 + (Number(parts[1]) || 0) + minutes;
            if (total >= 24 * 60) total = 23 * 60 + 30;
            if (total < 0) total = 0;
            const h = String(Math.floor(total / 60)).padStart(2, '0');
            const m = String(total % 60).padStart(2, '0');
            return h + ':' + m;
        },
        addProgramme() {
            if (this.programmeRows.length >= 40) return;
            const last = this.programmeRows[this.programmeRows.length - 1];
            const start = (last && (last.ends_at || last.starts_at)) || '08:00';
            this.programmeRows.push({
                id: null,
                starts_at: start,
                ends_at: this.addMinutes(start, 30),
                title: '',
                description: '',
            });
        },
        removeProgramme(i) { this.programmeRows.splice(i, 1); },
        onFileSelect(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            this.setPreview(file);
        },
        onDrop(e) {
            this.dragOver = false;
            const file = e.dataTransfer.files && e.dataTransfer.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            this.$refs.coverInput.files = dt.files;
            this.setPreview(file);
        },
        setPreview(file) {
            this.fileName = file.name;
            if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = URL.createObjectURL(file);
        },
    }
}
</script>
@endsection
