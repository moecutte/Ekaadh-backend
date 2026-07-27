@extends('layouts.app')

@section('title', 'Create Private Event')

@section('content')
@php
    $defaultCategoryId = old('private_event_category_id', $categories[0]['id'] ?? null);
    $designsById = collect($allDesigns)->keyBy('id');
    $premiumIds = collect($allDesigns)->where('category', 'premium')->pluck('id')->values()->all();
    $oldFields = old('invitation_field_values', []);
@endphp
<link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Dancing+Script:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Great+Vibes&family=Italianno&family=Josefin+Sans:ital,wght@0,400;0,600;0,700;1,400&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Merriweather:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;500;600;700&family=Mr+De+Haviland&family=Parisienne&family=Pinyon+Script&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&family=Raleway:ital,wght@0,400;0,600;0,700;1,400&family=Rouge+Script&family=Sacramento&family=Satisfy&family=Source+Sans+3:wght@400;600;700&family=Tangerine:wght@400;700&display=swap" rel="stylesheet">
<style>[x-cloak]{display:none!important}</style>

<div class="relative overflow-hidden">
<div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
<div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-12"
     x-data="privateEventForm({
        unit: {{ (float) $unitPrice }},
        premiumExtra: {{ (float) $premiumSurcharge }},
        fee: {{ (float) $serviceFee }},
        maxTickets: {{ (int) $maxTickets }},
        premiumIds: @js($premiumIds),
        initialDesign: @js(old('ticket_design', '')),
        categories: @js($categories),
        initialCategoryId: @js($defaultCategoryId),
        designs: @js($designsById),
        initialFieldValues: @js($oldFields),
        initialStep: {{ $errors->any() ? 0 : 0 }},
     })">
    <a href="{{ route('private-events.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-mute hover:text-brand transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        My private events
    </a>
    <div class="mt-4 mb-1">
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-brand mb-2">New invitation</p>
        <div class="flex items-end justify-between gap-3">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-ink">Create private event</h1>
            <span class="text-xs font-bold text-mute shrink-0 px-2.5 py-1 rounded-full bg-white border border-slate-100" x-text="'Step ' + (step + 1) + ' of 4'"></span>
        </div>
        <p class="text-sm text-mute mt-2">Four short steps — event details, design, invitation text, then payment.</p>
    </div>

    {{-- Step indicator --}}
    <div class="mt-6 mb-8 grid grid-cols-4 gap-2 sm:gap-3">
        <template x-for="(label, i) in stepLabels" :key="label">
            <div class="relative">
                <div class="h-1.5 rounded-full overflow-hidden bg-slate-200/80">
                    <div class="h-full rounded-full bg-brand transition-all duration-500"
                         :style="{ width: i < step ? '100%' : (i === step ? '55%' : '0%') }"></div>
                </div>
                <p class="mt-2 text-[10px] sm:text-[11px] font-bold text-center truncate transition-colors"
                   :class="i === step ? 'text-brand' : (i < step ? 'text-ink' : 'text-mute')"
                   x-text="label"></p>
            </div>
        </template>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <p x-show="stepError" x-cloak class="mb-4 rounded-xl bg-amber-50 border border-amber-100 text-amber-900 text-sm p-3 font-semibold" x-text="stepError"></p>

    <form method="POST" action="{{ route('private-events.store') }}" enctype="multipart/form-data"
          @submit="onSubmit($event)" novalidate>
        @csrf
        <input type="hidden" name="ticket_design" :value="design">
        <input type="hidden" name="invitation_design_id" :value="invitationDesignId">

        {{-- Step 1: Event info --}}
        <div x-show="step === 0" x-cloak class="space-y-5">
            <p class="text-sm text-mute">Tell us about your event. Date and venue will be applied to your invitation design.</p>
            <div class="bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 p-6 sm:p-7 space-y-5">
                <div>
                    <label class="text-xs font-bold text-mute block mb-1.5">Category *</label>
                    <select name="private_event_category_id" x-model.number="categoryId"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition">
                        @foreach($categories as $cat)
                            <option value="{{ $cat['id'] }}" @selected((int) $defaultCategoryId === (int) $cat['id'])>{{ $cat['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-mute block mb-1.5">Description *</label>
                    <textarea name="description" x-model="description" rows="4"
                              class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 resize-none transition">{{ old('description') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-mute block mb-1.5">Date *</label>
                        <input type="date" x-model="eventDate" @change="applyBasicsToFieldValues()" @input="applyBasicsToFieldValues()"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-mute block mb-1.5">Time *</label>
                        <input type="time" x-model="eventTime" @change="applyBasicsToFieldValues()" @input="applyBasicsToFieldValues()"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition">
                    </div>
                </div>
                <input type="hidden" name="event_date" :value="eventDate">
                <input type="hidden" name="event_time" :value="eventTime">
                <div>
                    <label class="text-xs font-bold text-mute block mb-1.5">Venue *</label>
                    <input type="text" x-model="venue" placeholder="Venue name"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition">
                </div>
                <div>
                    <label class="text-xs font-bold text-mute block mb-1.5">Cover image <span class="font-medium text-mute/70">(optional)</span></label>
                    <label class="flex flex-col items-center justify-center gap-2 w-full rounded-xl border border-dashed border-slate-200 bg-slate-50/40 px-4 py-6 cursor-pointer hover:border-brand/40 hover:bg-brand-soft/30 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-mute" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-semibold text-mute">PNG, JPG or WebP · max 5MB</span>
                        <input type="file" name="cover_image" accept="image/png,image/jpeg,image/jpg,image/webp" class="sr-only">
                    </label>
                </div>
            </div>
        </div>

        {{-- Step 2: Design --}}
        <div x-show="step === 1" x-cloak class="space-y-5">
            <p class="text-sm text-mute">Choose an invitation design for your category. Premium adds ${{ number_format($premiumSurcharge, 2) }}/ticket.</p>

            <div x-show="!categoryDesigns.length" class="rounded-xl border border-amber-100 bg-amber-50 text-amber-900 text-sm p-4">
                No invitation designs for this category yet. Go back and pick another category, or ask an admin to upload designs.
            </div>

            <div class="bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 p-6 sm:p-7 space-y-6" x-show="categoryDesigns.length">
                <div x-show="standardForCategory.length">
                    <h4 class="text-[11px] font-bold uppercase tracking-[0.14em] text-mute mb-3">Standard</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="d in standardForCategory" :key="d.id">
                            <button type="button"
                                    class="relative block rounded-2xl border-2 overflow-hidden transition-all duration-200 bg-slate-50 text-left group/card"
                                    @click="selectDesign(d.id, d.invitation_design_id)"
                                    :class="design === d.id ? 'border-brand shadow-lg shadow-brand/15 ring-2 ring-brand/20 scale-[1.02]' : 'border-slate-100 hover:border-slate-200 hover:shadow-md'">
                                <img :src="d.thumbnail_url || d.graphic_url"
                                     x-show="d.thumbnail_url || d.graphic_url"
                                     class="w-full aspect-[3/4] object-cover transition-transform duration-300 group-hover/card:scale-105"
                                     alt="Invitation design"
                                     loading="lazy">
                                <div x-show="!(d.thumbnail_url || d.graphic_url)"
                                     class="w-full aspect-[3/4]"
                                     :style="{ background: d.card_bg || '#f1f5f9' }"></div>
                                <span x-show="design === d.id"
                                      class="absolute top-2 right-2 w-6 h-6 rounded-full bg-brand text-white flex items-center justify-center text-xs font-bold shadow-md">✓</span>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="premiumForCategory.length">
                    <h4 class="text-[11px] font-bold uppercase tracking-[0.14em] text-mute mb-3">Premium</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="d in premiumForCategory" :key="'p-'+d.id">
                            <button type="button"
                                    class="relative block rounded-2xl border-2 overflow-hidden transition-all duration-200 bg-slate-50 text-left group/card"
                                    @click="selectDesign(d.id, d.invitation_design_id)"
                                    :class="design === d.id ? 'border-amber-500 shadow-lg shadow-amber-200/60 ring-2 ring-amber-200 scale-[1.02]' : 'border-slate-100 hover:border-amber-200 hover:shadow-md'">
                                <img :src="d.thumbnail_url || d.graphic_url"
                                     x-show="d.thumbnail_url || d.graphic_url"
                                     class="w-full aspect-[3/4] object-cover transition-transform duration-300 group-hover/card:scale-105"
                                     alt="Invitation design"
                                     loading="lazy">
                                <div x-show="!(d.thumbnail_url || d.graphic_url)"
                                     class="w-full aspect-[3/4]"
                                     :style="{ background: d.card_bg || '#f1f5f9' }"></div>
                                <p class="px-2 py-1.5 text-[10px] font-bold text-center bg-amber-50 text-amber-800">+${{ number_format($premiumSurcharge, 2) }}</p>
                                <span x-show="design === d.id"
                                      class="absolute top-2 right-2 w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-bold shadow-md">✓</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Preview + invitation text --}}
        <div x-show="step === 2" x-cloak class="space-y-5">
            <p class="text-sm text-mute">Preview your invitation and edit the text shown on the design.</p>

            <div class="bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 p-5 sm:p-6" x-show="selectedDesign">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold">Live preview</h3>
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full" style="background: #eef0f8; color: #323891;" x-text="isPremium ? 'Premium' : 'Standard'"></span>
                </div>
                {{-- Same 420px design canvas as admin editor + ticket overlay so text positions match --}}
                <div class="mx-auto overflow-hidden shadow-2xl shadow-slate-300/40 rounded-xl border relative"
                     :style="{
                        maxWidth: '420px',
                        aspectRatio: '3/4.2',
                        background: selectedDesign?.card_bg,
                        borderColor: selectedDesign?.border,
                     }">
                    <img x-show="selectedDesign?.graphic_url" :src="selectedDesign?.graphic_url" class="absolute inset-0 w-full h-full object-cover" alt="">
                    <template x-for="field in overlayPreviewFields" :key="field.field_key">
                        <div class="absolute" :style="previewFieldStyle(field)">
                            <template x-if="field.field_type === 'qr'">
                                <div class="flex flex-col items-center text-center bg-white/90 px-1 py-1 w-full">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=EKAADH-PREVIEW"
                                         alt="Sample QR"
                                         class="w-full aspect-square object-contain bg-white">
                                    <p class="mt-0.5 text-[7px] leading-tight text-slate-600 w-full">
                                        Scan at entry · Status: <span class="font-semibold" style="color: #323891;">Valid</span>
                                    </p>
                                </div>
                            </template>
                            <span x-show="field.field_type !== 'qr'"
                                  class="block px-1 leading-tight"
                                  x-text="fieldPreviewText(field)"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 p-6 sm:p-7 space-y-4" x-show="buyerDesignFields.length">
                <div>
                    <h3 class="text-sm font-bold">Invitation text</h3>
                    <p class="text-[11px] text-mute mt-1">Change sample text for each field on the card. Date and time parts fill automatically from step 1.</p>
                </div>
                <template x-for="field in buyerDesignFields" :key="'edit-'+field.field_key">
                    <div>
                        <label class="text-xs font-bold text-mute block mb-1.5">
                            <span x-text="field.label"></span>
                            <span x-show="field.is_required"> *</span>
                        </label>
                        <input :type="fieldInputType(field)"
                               :name="'invitation_field_values[' + field.field_key + ']'"
                               x-model="fieldValues[field.field_key]"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition"
                               :placeholder="field.placeholder || field.default_text || ''">
                    </div>
                </template>
            </div>
            {{-- Auto date/time fields still submitted --}}
            <template x-for="field in autoDateFields" :key="'auto-'+field.field_key">
                <input type="hidden" :name="'invitation_field_values[' + field.field_key + ']'" :value="fieldValues[field.field_key] || ''">
            </template>
            <div x-show="selectedDesign && !buyerDesignFields.length" class="rounded-xl border border-slate-100 bg-white p-4 text-sm text-mute">
                Date and time on the card update from the event date/time you chose. Continue to pricing.
            </div>
        </div>

        {{-- Step 4: Quantity + payment --}}
        <div x-show="step === 3" x-cloak class="space-y-5">
            <p class="text-sm text-mute">Set how many tickets to buy and review pricing before payment.</p>
            <div class="bg-white/90 backdrop-blur rounded-[1.35rem] border border-slate-100 shadow-sm shadow-slate-200/50 p-6 sm:p-7 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-mute block mb-1.5">Quantity *</label>
                        <input type="number" name="quantity" x-model.number="qty" min="1" max="{{ $maxTickets }}"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-mute block mb-1.5">Ticket label</label>
                        <input name="ticket_label" value="{{ old('ticket_label', 'Invitation') }}"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition" placeholder="Invitation">
                    </div>
                </div>
                <div class="rounded-2xl bg-gradient-to-br from-brand-soft to-white border border-brand/10 px-5 py-4 text-sm space-y-2">
                    <div class="flex justify-between"><span class="text-mute">Price / ticket</span><span class="font-bold" x-text="'$' + unitNow.toFixed(2)"></span></div>
                    <div class="flex justify-between"><span class="text-mute">Subtotal</span><span class="font-bold" x-text="'$' + subtotal.toFixed(2)"></span></div>
                    <div class="flex justify-between"><span class="text-mute">Service fee</span><span class="font-bold">${{ number_format($serviceFee, 2) }}</span></div>
                    <div class="flex justify-between pt-2 mt-1 border-t border-brand/10"><span class="font-extrabold text-ink">Total due</span><span class="font-extrabold text-brand text-base" x-text="'$' + total.toFixed(2)"></span></div>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <div class="mt-6 flex items-center gap-3 sticky bottom-0 bg-page/90 backdrop-blur-md py-4 border-t border-slate-100/80 z-10">
            <button type="button" x-show="step > 0" x-cloak
                    @click="backStep()"
                    class="px-5 py-3.5 rounded-2xl border border-slate-200 bg-white text-sm font-extrabold text-ink hover:bg-slate-50 transition-colors">
                Back
            </button>
            <button type="button" x-show="step < 3" x-cloak
                    @click="nextStep()"
                    class="flex-1 py-3.5 rounded-2xl bg-brand text-white font-extrabold text-sm shadow-lg shadow-brand/20 hover:bg-brand-dark hover:-translate-y-0.5 transition-all">
                Continue
            </button>
            <button type="submit" x-show="step === 3" x-cloak
                    class="flex-1 py-3.5 rounded-2xl bg-brand text-white font-extrabold text-sm shadow-lg shadow-brand/20 hover:bg-brand-dark hover:-translate-y-0.5 transition-all"
                    x-text="'Continue to payment · $' + total.toFixed(2)">
            </button>
        </div>
    </form>
</div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function privateEventForm(cfg) {
    const designs = cfg.designs || {};
    return {
        step: 0,
        stepLabels: ['Event info', 'Design', 'Invitation text', 'Payment'],
        stepError: '',
        qty: {{ (int) old('quantity', 20) }},
        design: cfg.initialDesign || '',
        invitationDesignId: Number({{ (int) old('invitation_design_id', 0) }}) || null,
        premiumIds: cfg.premiumIds || [],
        categories: cfg.categories || [],
        categoryId: Number(cfg.initialCategoryId) || (cfg.categories?.[0]?.id ?? null),
        designs,
        fieldValues: Object.assign({}, cfg.initialFieldValues || {}),
        description: @js(old('description', '')),
        eventDate: '',
        eventTime: '18:00',
        venue: '',
        unit: cfg.unit,
        premiumExtra: cfg.premiumExtra,
        fee: cfg.fee,
        maxTickets: cfg.maxTickets || 500,
        init() {
            if (this.design && this.designs[this.design]) {
                this.invitationDesignId = this.designs[this.design].invitation_design_id || null;
                this.seedFieldDefaults();
                this.applyBasicsToFieldValues();
            }
            this.$watch('categoryId', () => {
                this.design = '';
                this.invitationDesignId = null;
            });
            this.$watch('eventDate', () => this.applyBasicsToFieldValues());
            this.$watch('eventTime', () => this.applyBasicsToFieldValues());
            this.$watch('venue', () => this.applyBasicsToFieldValues());
            // Ensure default time paints into the time input and card fields.
            this.$nextTick(() => this.applyBasicsToFieldValues());
        },
        selectDesign(slug, id) {
            this.design = slug;
            this.invitationDesignId = id || this.designs[slug]?.invitation_design_id || null;
            this.seedFieldDefaults();
            this.applyBasicsToFieldValues();
            this.stepError = '';
        },
        seedFieldDefaults() {
            const fields = this.selectedDesign?.fields || [];
            fields.forEach(f => {
                if (f.field_type === 'qr') return;
                const current = this.fieldValues[f.field_key];
                if (current === undefined || current === null || String(current).trim() === '') {
                    this.fieldValues[f.field_key] = f.default_text || '';
                }
            });
            this.fieldValues = { ...this.fieldValues };
        },
        formatTimeLabel(raw) {
            if (!raw) return '';
            const parts = String(raw).split(':');
            if (parts.length < 2) return '';
            let h = Number(parts[0]);
            if (Number.isNaN(h)) return '';
            const m = String(parts[1] || '00').padStart(2, '0').slice(0, 2);
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            if (h === 0) h = 12;
            return h + ':' + m + ' ' + ampm;
        },
        isTimeLikeField(f) {
            const type = String(f.field_type || '');
            if (type === 'date_time') return true;
            const key = String(f.field_key || '').toLowerCase();
            const label = String(f.label || '').toLowerCase();
            if (key === 'event_time' || key === 'date_time' || key.includes('time')) return true;
            if (label === 'time' || label.includes('time')) return true;
            const sample = String(f.default_text || '').trim();
            return /^\d{1,2}:\d{2}(\s*[ap]m)?$/i.test(sample);
        },
        applyBasicsToFieldValues() {
            const fields = this.selectedDesign?.fields || [];
            if (!fields.length) return;
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            let monthName = '', day = '', year = '';
            const timeLabel = this.formatTimeLabel(this.eventTime);
            if (this.eventDate) {
                const parts = String(this.eventDate).split('-');
                if (parts.length === 3) {
                    year = parts[0];
                    const mi = Number(parts[1]) - 1;
                    monthName = months[mi] || '';
                    day = String(Number(parts[2]));
                }
            }
            const next = { ...this.fieldValues };
            fields.forEach(f => {
                if (f.field_type === 'qr') return;
                const key = String(f.field_key || '').toLowerCase();
                const label = String(f.label || '').toLowerCase();
                if (f.field_type === 'date_month' && monthName) {
                    next[f.field_key] = monthName;
                } else if (f.field_type === 'date_day' && day) {
                    next[f.field_key] = day;
                } else if (f.field_type === 'date_year' && year) {
                    next[f.field_key] = year;
                } else if (this.isTimeLikeField(f) && timeLabel) {
                    next[f.field_key] = timeLabel;
                } else if ((key === 'event_date' || label === 'date') && this.eventDate) {
                    next[f.field_key] = this.eventDate;
                } else if ((key.includes('venue') || key.includes('location') || label.includes('venue')) && this.venue.trim()) {
                    next[f.field_key] = this.venue.trim();
                }
            });
            this.fieldValues = next;
        },
        fieldInputType(field) {
            if (field.field_type === 'textarea') return 'text';
            return 'text';
        },
        isAutoDateType(type) {
            return ['date_month', 'date_day', 'date_year', 'date_time'].includes(String(type || ''));
        },
        validateStep(step) {
            this.stepError = '';
            if (step === 0) {
                if (!this.categoryId) { this.stepError = 'Choose a category.'; return false; }
                if (!String(this.description || '').trim()) { this.stepError = 'Enter a description.'; return false; }
                if (!this.eventDate) { this.stepError = 'Choose an event date.'; return false; }
                if (!this.eventTime) { this.stepError = 'Choose an event time.'; return false; }
                if (!String(this.venue || '').trim()) { this.stepError = 'Enter a venue.'; return false; }
                return true;
            }
            if (step === 1) {
                if (!this.design || !this.selectedDesign || !this.invitationDesignId) {
                    this.stepError = 'Choose an invitation design.';
                    return false;
                }
                this.seedFieldDefaults();
                this.applyBasicsToFieldValues();
                return true;
            }
            if (step === 2) {
                if (!this.selectedDesign) {
                    this.stepError = 'Choose an invitation design.';
                    return false;
                }
                for (const f of this.buyerDesignFields) {
                    const value = String(this.fieldValues[f.field_key] || '').trim();
                    if (f.is_required && !value) {
                        this.stepError = (f.label || 'A field') + ' is required.';
                        return false;
                    }
                }
                return true;
            }
            if (step === 3) {
                const q = Number(this.qty) || 0;
                if (q < 1 || q > this.maxTickets) {
                    this.stepError = 'Choose a valid ticket quantity.';
                    return false;
                }
                return true;
            }
            return true;
        },
        nextStep() {
            if (!this.validateStep(this.step)) return;
            this.step = Math.min(3, this.step + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        backStep() {
            this.stepError = '';
            this.step = Math.max(0, this.step - 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onSubmit(e) {
            if (!this.validateStep(0) || !this.validateStep(1) || !this.validateStep(2) || !this.validateStep(3)) {
                e.preventDefault();
                if (!this.validateStep(0)) this.step = 0;
                else if (!this.validateStep(1)) this.step = 1;
                else if (!this.validateStep(2)) this.step = 2;
                else this.step = 3;
                return;
            }
        },
        get categoryDesigns() {
            return Object.values(this.designs).filter(d => Number(d.private_event_category_id) === Number(this.categoryId));
        },
        get standardForCategory() {
            return this.categoryDesigns.filter(d => d.category === 'standard');
        },
        get premiumForCategory() {
            return this.categoryDesigns.filter(d => d.category === 'premium');
        },
        get selectedDesign() {
            return this.designs[this.design] || null;
        },
        get buyerDesignFields() {
            return (this.selectedDesign?.fields || []).filter(f => f.field_type !== 'qr' && !this.isAutoDateType(f.field_type));
        },
        get autoDateFields() {
            return (this.selectedDesign?.fields || []).filter(f => this.isAutoDateType(f.field_type));
        },
        get overlayPreviewFields() {
            return (this.selectedDesign?.fields || []).filter(f => f.show_on_card !== false);
        },
        previewFieldStyle(field) {
            const isQr = field.field_type === 'qr';
            return {
                left: (field.pos_x ?? (isQr ? 35 : 20)) + '%',
                top: (field.pos_y ?? (isQr ? 75 : 30)) + '%',
                width: (field.box_width ?? (isQr ? 25 : 60)) + '%',
                zIndex: isQr ? 15 : 10,
                textAlign: field.text_align || 'center',
                fontSize: (field.font_size || 18) + 'px',
                fontFamily: field.font_family ? `'${field.font_family}', serif` : "'Montserrat', serif",
                fontWeight: field.font_weight || '400',
                fontStyle: field.font_style || 'normal',
                color: field.color || this.selectedDesign?.text || '#3d3348',
                lineHeight: '1.25',
            };
        },
        fieldPreviewText(field) {
            const value = this.fieldValues[field.field_key];
            if (value !== undefined && value !== null && String(value).trim() !== '') {
                return String(value).trim();
            }
            return (field.default_text || field.placeholder || '').trim();
        },
        get isPremium() { return this.premiumIds.includes(this.design); },
        get unitNow() {
            const d = this.selectedDesign;
            if (d && d.unit_price != null && d.unit_price !== '') {
                return Number(d.unit_price);
            }
            return this.unit + (this.isPremium ? this.premiumExtra : 0);
        },
        get subtotal() { return Math.max(0, Number(this.qty) || 0) * this.unitNow; },
        get total() { return this.subtotal + this.fee; },
    }
}
</script>
@endsection
