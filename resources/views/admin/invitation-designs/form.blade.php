@extends('layouts.admin')
@section('title', $design->exists ? 'Edit design' : 'New design')
@section('heading', $design->exists ? 'Edit design' : 'New invitation design')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Cormorant+Garamond:ital,wght@0,500;0,700;1,400&family=Great+Vibes&family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Tangerine:wght@400;700&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>

<a href="{{ route('admin.invitation-designs.index') }}" class="text-sm font-bold text-mute hover:text-brand">&larr; Designs</a>

@if(session('success'))
    <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm p-3">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mt-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-3">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mt-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-3">
        <p class="font-bold mb-1">Could not save</p>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mt-5"
     x-data="{
        previewSrc: @js($design->thumbnail_url ?: $design->graphic_url),
        graphicError: '',
        envelopeDemo: true,
        envelopeOpen: false,
        envelopeDone: false,
        envelopeTimer: null,
        startEnvelopeDemo() {
            clearTimeout(this.envelopeTimer);
            this.envelopeDemo = true;
            this.envelopeOpen = false;
            this.envelopeDone = false;
        },
        openEnvelopeDemo() {
            if (this.envelopeOpen) return;
            this.envelopeOpen = true;
            this.envelopeTimer = setTimeout(() => {
                this.envelopeDone = true;
                this.envelopeDemo = false;
                this.envelopeOpen = false;
            }, 1400);
        },
        onGraphicPicked(e) {
            this.graphicError = '';
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                this.graphicError = 'Please choose a PNG, JPG, or WebP file.';
                return;
            }
            this.previewSrc = URL.createObjectURL(file);
        }
     }">

<div class="grid lg:grid-cols-12 gap-5">
<form method="POST"
      action="{{ $design->exists ? route('admin.invitation-designs.update', $design) : route('admin.invitation-designs.store') }}"
      enctype="multipart/form-data"
      class="lg:col-span-5 space-y-5">
    @csrf
    @if($design->exists) @method('PUT') @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
            <h3 class="text-sm font-bold">Design details</h3>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Event category *</label>
                <select name="private_event_category_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="">Select category…</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) old('private_event_category_id', $design->private_event_category_id) === (int) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-mute mt-1">Customers only see this design when they pick this category.</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">Tier *</label>
                    <select name="tier" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        <option value="standard" @selected(old('tier', $design->tier)==='standard')>Standard</option>
                        <option value="premium" @selected(old('tier', $design->tier)==='premium')>Premium</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">Web theme *</label>
                    @php $bladeTemplates = $bladeTemplates ?? \App\Support\TicketDesigns::bladeTemplateOptions(); @endphp
                    <select name="blade_key" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        @foreach($bladeTemplates as $key => $label)
                            <option value="{{ $key }}" @selected(old('blade_key', $design->blade_key) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="render_mode" value="blade">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">Ticket price ($)</label>
                    <input type="number" step="0.01" min="0" name="ticket_price"
                           value="{{ old('ticket_price', $design->ticket_price) }}"
                           placeholder="{{ number_format(\App\Services\PrivateEventService::unitPrice(), 2) }}"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    <p class="text-[10px] text-mute mt-0.5">Leave blank to use global default.</p>
                </div>
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">Premium surcharge ($)</label>
                    <input type="number" step="0.01" min="0" name="premium_surcharge"
                           value="{{ old('premium_surcharge', $design->premium_surcharge) }}"
                           placeholder="{{ number_format(\App\Services\PrivateEventService::premiumDesignSurcharge(), 2) }}"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    <p class="text-[10px] text-mute mt-0.5">Added when tier is Premium.</p>
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Theme colors</label>
                <p class="text-[11px] text-mute mb-2">These tint the HTML theme. Save to refresh the live preview.</p>
                <div class="grid grid-cols-4 gap-2">
                    @foreach([
                        'accent' => '#705898',
                        'accent_soft' => '#f3eef8',
                        'card_bg' => '#faf7fc',
                        'text_color' => '#3d3348',
                        'muted_color' => '#6b6280',
                        'border_color' => '#c5a059',
                        'header_from' => '#4b3664',
                        'header_to' => '#9b84b6',
                    ] as $key => $fallback)
                        @php $val = old($key, $design->{$key}) ?: $fallback; @endphp
                        <label class="block">
                            <span class="text-[10px] font-bold text-mute block mb-0.5">{{ str_replace('_', ' ', $key) }}</span>
                            <input type="color" name="{{ $key }}" value="{{ $val }}" class="w-full h-9 rounded-lg border border-slate-200 p-0.5">
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">Sort order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $design->sort_order) }}" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <label class="flex items-end gap-2 text-sm pb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-brand" @checked(old('is_active', $design->is_active ?? true))>
                    <span class="font-semibold">Active</span>
                </label>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
            <h3 class="text-sm font-bold">Picker thumbnail</h3>
            <p class="text-xs text-mute">Optional. Shown in the customer design grid. The invitation itself is the HTML theme.</p>
            <div class="flex gap-3 items-start">
                <div class="w-20 h-28 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shrink-0 flex items-center justify-center"
                     x-show="previewSrc" x-cloak>
                    <img :src="previewSrc" alt="Thumbnail" class="w-full h-full object-cover">
                </div>
                <div class="flex-1 min-w-0 space-y-2">
                    <input type="file" name="thumbnail" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="w-full text-sm"
                           @change="onGraphicPicked($event)">
                    <p class="text-[11px] text-red-600" x-show="graphicError" x-text="graphicError" x-cloak></p>
                </div>
            </div>
        </div>

        <button class="w-full py-3 rounded-2xl bg-brand text-white font-extrabold text-sm">{{ $design->exists ? 'Save design' : 'Create design' }}</button>
</form>

    @include('admin.invitation-designs.theme-preview')
</div>

@if($design->exists)
    <form method="POST" action="{{ route('admin.invitation-designs.destroy', $design) }}" class="mt-5"
          onsubmit="return confirm('Delete this design? Events that used it will keep their invitation with a fallback theme.')">
        @csrf
        @method('DELETE')
        <button class="px-4 py-2.5 rounded-xl bg-red-50 text-red-600 text-sm font-bold border border-red-100">Delete design</button>
    </form>
@endif
</div>
@endsection


