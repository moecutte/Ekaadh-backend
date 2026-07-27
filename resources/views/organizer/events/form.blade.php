@extends('layouts.organizer')
@section('title', $event->exists ? 'Edit Event' : 'Create Event')
@section('heading', $event->exists ? 'Edit Event' : 'Create Event')

@section('content')
@if($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $event->exists ? route('organizer.events.update', $event) : route('organizer.events.store') }}" enctype="multipart/form-data" class="max-w-2xl mx-auto space-y-5" x-data="eventForm()">
    @csrf
    @if($event->exists) @method('PUT') @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-bold mb-1">Event details</h3>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Event title *</label>
            <input name="title" value="{{ old('title', $event->title) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm outline-none focus:border-brand">
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Category *</label>
            <select name="category" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm">
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(old('category', $event->category)===$cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Description *</label>
            <textarea name="description" rows="4" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm outline-none focus:border-brand resize-none">{{ old('description', $event->description) }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-sm font-bold mb-4">Cover image</h3>
        <input
            type="file"
            name="cover_image"
            x-ref="coverInput"
            accept="image/png,image/jpeg,image/jpg,image/webp"
            class="hidden"
            @change="onFileSelect($event)"
        >
        <div
            class="relative rounded-xl border-2 border-dashed border-slate-200 overflow-hidden bg-slate-50/50 hover:border-brand/40 transition-colors"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop($event)"
            :class="dragOver && 'border-brand bg-brand/5'"
        >
            <div x-show="previewUrl" x-cloak class="relative group">
                <img :src="previewUrl" alt="Cover preview" class="w-full h-48 object-cover">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <button type="button" @click="$refs.coverInput.click()" class="px-4 py-2 rounded-lg bg-white text-sm font-bold text-ink shadow">
                        Change image
                    </button>
                </div>
            </div>
            <button
                type="button"
                x-show="!previewUrl"
                @click="$refs.coverInput.click()"
                class="flex flex-col items-center justify-center w-full px-6 py-10 text-center"
            >
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mb-3 text-mute text-lg">↑</span>
                <span class="text-sm font-semibold text-ink">Upload cover image</span>
                <span class="text-xs text-mute mt-1">PNG, JPG or WEBP · max 5 MB</span>
                <span class="mt-3 text-xs font-bold text-brand">Browse files</span>
            </button>
        </div>
        <p class="text-xs text-mute mt-2" x-show="previewUrl && !fileName" x-cloak>Current cover image. Upload a new file to replace it.</p>
        <p class="text-xs text-mute mt-2" x-show="fileName" x-text="'Selected: ' + fileName" x-cloak></p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-sm font-bold mb-4">Date, time & venue</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Date *</label>
                <input type="date" name="event_date" value="{{ old('event_date', optional($event->event_date)->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Time *</label>
                <input type="time" name="event_time" value="{{ old('event_time', $event->event_time ? substr((string)$event->event_time,0,5) : '18:00') }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">Venue *</label>
                <input name="venue" value="{{ old('venue', $event->venue) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1.5">City</label>
                <select name="city" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm">
                    <option value="">Select city</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}" @selected(old('city', $event->city)===$city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <label class="text-xs font-bold text-mute block mb-1.5">Address</label>
                <input name="address" value="{{ old('address', $event->address) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold">Ticket types</h3>
            <button type="button" @click="add()" class="text-xs font-bold text-brand">+ Add row</button>
        </div>
        <template x-for="(row, index) in rows" :key="index">
            <div class="grid grid-cols-12 gap-2 mb-2 items-center bg-slate-50 rounded-xl p-3 border border-slate-100">
                <input type="hidden" :name="'tickets['+index+'][id]'" x-model="row.id">
                <input class="col-span-4 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Name" :name="'tickets['+index+'][name]'" x-model="row.name" required>
                <input class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Price" type="number" step="0.01" :name="'tickets['+index+'][price]'" x-model="row.price" required>
                <input class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Qty" type="number" :name="'tickets['+index+'][quantity_available]'" x-model="row.quantity_available" required>
                <input class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Max" type="number" :name="'tickets['+index+'][max_per_order]'" x-model="row.max_per_order" required>
                <input type="hidden" :name="'tickets['+index+'][description]'" x-model="row.description">
                <button type="button" class="col-span-2 text-xs font-bold text-red-400" @click="remove(index)" x-show="rows.length > 1">Remove</button>
            </div>
        </template>
    </div>

    <div class="flex justify-end gap-3">
        <button type="submit" name="action" value="draft" class="px-5 py-2.5 text-sm font-bold text-mute bg-white border border-slate-200 rounded-xl">Save draft</button>
        <button type="submit" name="action" value="publish" class="px-5 py-2.5 text-sm font-bold text-white bg-brand rounded-xl hover:bg-brand-dark">Submit for review</button>
    </div>
</form>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function eventForm() {
    return {
        rows: @json(old('tickets', $ticketTypes->values())),
        previewUrl: @json($event->cover_image),
        fileName: '',
        dragOver: false,
        add() { this.rows.push({ id: null, name: '', description: '', price: '', quantity_available: 100, max_per_order: 5 }); },
        remove(i) { this.rows.splice(i, 1); },
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
