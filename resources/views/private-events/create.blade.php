@extends('layouts.app')

@section('title', __('ui.create_private_event'))

@section('content')
@php
    $defaultCategoryId = old('private_event_category_id', $categories[0]['id'] ?? null);
    $designsById = collect($allDesigns)->keyBy('id');
    $premiumIds = collect($allDesigns)->where('category', 'premium')->pluck('id')->values()->all();
    $oldFields = old('invitation_field_values', []);
    $wizardI18n = [
        'stepOf' => __('ui.step_of', ['current' => '{current}', 'total' => '{total}']),
        'stepLabels' => [__('ui.event_info'), __('ui.design'), __('ui.invitation_text'), __('ui.payment')],
        'standard' => __('ui.standard'),
        'premium' => __('ui.premium'),
        'continuePayment' => __('ui.continue_to_payment'),
        'valid' => __('ui.valid'),
        'chooseCategory' => __('ui.choose_category'),
        'enterDescription' => __('ui.enter_description'),
        'chooseEventDate' => __('ui.choose_event_date'),
        'chooseEventTime' => __('ui.choose_event_time'),
        'enterVenue' => __('ui.enter_venue'),
        'chooseInvitationDesign' => __('ui.choose_invitation_design'),
        'fieldRequired' => __('ui.field_required', ['field' => '{field}']),
        'chooseValidQuantity' => __('ui.choose_valid_quantity'),
    ];
    $wizardCard = 'rounded-[1.75rem] bg-white border border-slate-100 shadow-[0_18px_40px_-28px_rgba(15,26,46,0.35)] overflow-hidden';
    $wizardBar = 'h-1 bg-gradient-to-r from-brand via-[#4a51b8] to-brand/40';
    $fieldClass = 'w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition';
    $labelClass = 'text-[11px] font-bold uppercase tracking-[0.14em] text-mute block mb-1.5';
@endphp
@include('invitations.partials.invitation-fonts')
<style>
[x-cloak]{display:none!important}
.invite-picker-tile {
    position: relative;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    background: #fff;
}
.invite-picker-tile-scale {
    width: 420px;
    transform-origin: top left;
    pointer-events: none;
}
</style>

<div class="relative overflow-hidden">
<div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
<div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-7"
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
        previewUrl: @js(route('private-events.invitation-preview')),
        i18n: @js($wizardI18n),
     })">
    <a href="{{ route('private-events.index') }}" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-mute hover:text-brand transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ __('ui.my_private_events') }}
    </a>

    <header class="mt-4 mb-4 {{ $wizardCard }}">
        <div class="{{ $wizardBar }}"></div>
        <div class="px-5 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.new_invitation') }}</p>
                    <h1 class="mt-1 text-xl sm:text-2xl font-extrabold tracking-tight text-ink leading-tight">
                        {{ __('ui.create_private_event') }}
                    </h1>
                </div>
                <div class="relative sm:w-[23rem] sm:shrink-0">
                    <div class="absolute top-3.5 left-[12%] right-[12%] h-px bg-slate-200"></div>
                    <div class="absolute top-3.5 left-[12%] h-px bg-brand transition-all duration-500 ease-out"
                         :style="{ width: ((step / 3) * 76) + '%' }"></div>
                    <ol class="relative grid grid-cols-4">
                        <template x-for="(label, i) in stepLabels" :key="i">
                            <li class="flex flex-col items-center text-center">
                                <button
                                    type="button"
                                    @click="goToStep(i)"
                                    :disabled="i > step"
                                    class="relative z-[1] w-7 h-7 rounded-full text-[11px] font-extrabold border transition-all duration-300 flex items-center justify-center disabled:opacity-100"
                                    :class="i < step
                                        ? 'bg-brand border-brand text-white cursor-pointer'
                                        : (i === step
                                            ? 'bg-brand border-brand text-white shadow-[0_0_0_3px_rgba(50,56,145,0.14)]'
                                            : 'bg-white border-slate-200 text-mute cursor-default')"
                                >
                                    <svg x-show="i < step" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span x-show="i >= step" x-text="i + 1"></span>
                                </button>
                                <p class="mt-1.5 text-[10px] sm:text-[11px] font-bold leading-tight px-0.5 truncate w-full transition-colors"
                                   :class="i === step ? 'text-ink' : (i < step ? 'text-brand' : 'text-mute')"
                                   x-text="label"></p>
                            </li>
                        </template>
                    </ol>
                </div>
            </div>
        </div>
    </header>

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
        <div x-show="step === 0" x-cloak>
            <div class="{{ $wizardCard }}">
                <div class="{{ $wizardBar }}"></div>
                <div class="px-5 sm:px-7 pt-6 pb-7 space-y-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.event_info') }}</p>
                        <p class="mt-1.5 text-sm text-mute leading-relaxed">{{ __('ui.create_step1_hint') }}</p>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('ui.category') }} *</label>
                        <select name="private_event_category_id" x-model.number="categoryId" class="{{ $fieldClass }}">
                            @foreach($categories as $cat)
                                <option value="{{ $cat['id'] }}" @selected((int) $defaultCategoryId === (int) $cat['id'])>{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('ui.description') }} *</label>
                        <textarea name="description" x-model="description" rows="4" class="{{ $fieldClass }} resize-none">{{ old('description') }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}">{{ __('ui.date') }} *</label>
                            <input type="date" x-model="eventDate" @change="applyBasicsToFieldValues()" @input="applyBasicsToFieldValues()" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ __('ui.time') }} *</label>
                            <input type="time" x-model="eventTime" @change="applyBasicsToFieldValues()" @input="applyBasicsToFieldValues()" class="{{ $fieldClass }}">
                        </div>
                    </div>
                    <input type="hidden" name="event_date" :value="eventDate">
                    <input type="hidden" name="event_time" :value="eventTime">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('ui.venue') }} *</label>
                        <input type="text" x-model="venue" placeholder="{{ __('ui.venue_name_placeholder') }}" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('ui.cover_image') }} <span class="font-medium normal-case tracking-normal text-mute/70">{{ __('ui.optional') }}</span></label>
                        <label class="flex flex-col items-center justify-center gap-2 w-full rounded-xl border border-dashed border-slate-200 bg-slate-50/50 px-4 py-7 cursor-pointer hover:border-brand/40 hover:bg-brand/5 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-mute" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-xs font-semibold text-mute">{{ __('ui.cover_image_hint') }}</span>
                            <input type="file" name="cover_image" accept="image/png,image/jpeg,image/jpg,image/webp" class="sr-only">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Design --}}
        <div x-show="step === 1" x-cloak>
            <div class="{{ $wizardCard }}">
                <div class="{{ $wizardBar }}"></div>
                <div class="px-5 sm:px-7 pt-6 pb-7 space-y-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.design') }}</p>
                        <p class="mt-1.5 text-sm text-mute leading-relaxed">{{ __('ui.create_step2_hint', ['amount' => number_format($premiumSurcharge, 2)]) }}</p>
                    </div>

                    <div x-show="!categoryDesigns.length" class="rounded-xl border border-amber-100 bg-amber-50 text-amber-900 text-sm p-4">
                        {{ __('ui.no_designs_for_category') }}
                    </div>

                    <div class="space-y-6" x-show="categoryDesigns.length">
                        <div x-show="standardForCategory.length">
                            <h4 class="text-[11px] font-bold uppercase tracking-[0.14em] text-mute mb-3">{{ __('ui.standard') }}</h4>
                            @include('private-events.partials.design-picker-grid', ['tier' => 'standard'])
                        </div>

                        <div x-show="premiumForCategory.length">
                            <h4 class="text-[11px] font-bold uppercase tracking-[0.14em] text-mute mb-3">{{ __('ui.premium') }}</h4>
                            @include('private-events.partials.design-picker-grid', ['tier' => 'premium'])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Preview + invitation text --}}
        <div x-show="step === 2" x-cloak>
            <div class="{{ $wizardCard }}">
                <div class="{{ $wizardBar }}"></div>
                <div class="px-5 sm:px-7 pt-6 pb-7 space-y-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.invitation_text') }}</p>
                        <p class="mt-1.5 text-sm text-mute leading-relaxed">{{ __('ui.create_step3_hint') }}</p>
                    </div>

                    <div x-show="selectedDesign">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-ink">{{ __('ui.live_preview') }}</h3>
                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-brand/10 text-brand" x-text="isPremium ? i18n.premium : i18n.standard"></span>
                        </div>
                        <div class="mx-auto overflow-hidden rounded-xl border bg-slate-50/60"
                             :style="{ maxWidth: '428px', borderColor: selectedDesign?.border || '#e2e8f0' }">
                            <iframe x-ref="themePreview"
                                    title="Invitation theme preview"
                                    class="w-full border-0 block bg-transparent"
                                    :style="{ height: previewHeight + 'px' }"></iframe>
                        </div>
                    </div>

                    <div class="space-y-4" x-show="buyerDesignFields.length">
                        <p class="text-[11px] text-mute">{{ __('ui.invitation_text_hint') }}</p>
                        <template x-for="field in buyerDesignFields" :key="'edit-'+field.field_key">
                            <div>
                                <label class="{{ $labelClass }}">
                                    <span x-text="field.label"></span>
                                    <span x-show="field.is_required"> *</span>
                                </label>
                                <input :type="fieldInputType(field)"
                                       :name="'invitation_field_values[' + field.field_key + ']'"
                                       x-model="fieldValues[field.field_key]"
                                       class="{{ $fieldClass }}"
                                       :placeholder="field.placeholder || field.default_text || ''">
                            </div>
                        </template>
                    </div>
                    <div x-show="selectedDesign && !buyerDesignFields.length" class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 text-sm text-mute">
                        {{ __('ui.auto_datetime_note') }}
                    </div>
                </div>
            </div>
            {{-- Auto date/time fields still submitted --}}
            <template x-for="field in autoDateFields" :key="'auto-'+field.field_key">
                <input type="hidden" :name="'invitation_field_values[' + field.field_key + ']'" :value="fieldValues[field.field_key] || ''">
            </template>
        </div>

        {{-- Step 4: Quantity + payment --}}
        <div x-show="step === 3" x-cloak>
            <div class="{{ $wizardCard }}">
                <div class="{{ $wizardBar }}"></div>
                <div class="px-5 sm:px-7 pt-6 pb-7 space-y-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.payment') }}</p>
                        <p class="mt-1.5 text-sm text-mute leading-relaxed">{{ __('ui.create_step4_hint') }}</p>
                    </div>
                    <div x-show="selectedDesign" class="mx-auto overflow-hidden rounded-xl border bg-slate-50/60"
                         :style="{ maxWidth: '428px', borderColor: selectedDesign?.border || '#e2e8f0' }">
                        <iframe x-ref="themePreviewPay"
                                title="Invitation theme preview"
                                class="w-full border-0 block bg-transparent"
                                :style="{ height: previewHeight + 'px' }"></iframe>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}">{{ __('ui.quantity') }} *</label>
                            <input type="number" name="quantity" x-model.number="qty" min="1" max="{{ $maxTickets }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ __('ui.ticket_label') }}</label>
                            <input name="ticket_label" value="{{ old('ticket_label', __('ui.invitation')) }}" class="{{ $fieldClass }}" placeholder="{{ __('ui.invitation') }}">
                        </div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 px-5 py-4 text-sm space-y-2">
                        <div class="flex justify-between"><span class="text-mute">{{ __('ui.price_per_ticket') }}</span><span class="font-bold" x-text="'$' + unitNow.toFixed(2)"></span></div>
                        <div class="flex justify-between"><span class="text-mute">{{ __('ui.subtotal') }}</span><span class="font-bold" x-text="'$' + subtotal.toFixed(2)"></span></div>
                        <div class="flex justify-between"><span class="text-mute">{{ __('ui.service_fee') }}</span><span class="font-bold">${{ number_format($serviceFee, 2) }}</span></div>
                        <div class="flex justify-between pt-2 mt-1 border-t border-slate-200"><span class="font-extrabold text-ink">{{ __('ui.total_due') }}</span><span class="font-extrabold text-brand text-base" x-text="'$' + total.toFixed(2)"></span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <div class="mt-6 sticky bottom-3 z-10 {{ $wizardCard }}">
            <div class="flex items-center gap-3 px-4 sm:px-5 py-3.5">
                <button type="button" x-show="step > 0" x-cloak
                        @click="backStep()"
                        class="px-5 py-3 rounded-2xl border border-slate-200 bg-white text-sm font-extrabold text-ink hover:bg-slate-50 transition-colors">
                    {{ __('ui.back') }}
                </button>
                <button type="button" x-show="step < 3" x-cloak
                        @click="nextStep()"
                        class="flex-1 py-3 rounded-2xl bg-brand text-white font-extrabold text-sm shadow-lg shadow-brand/20 hover:bg-brand-dark transition-colors">
                    {{ __('ui.continue') }}
                </button>
                <button type="submit" x-show="step === 3" x-cloak
                        class="flex-1 py-3 rounded-2xl bg-brand text-white font-extrabold text-sm shadow-lg shadow-brand/20 hover:bg-brand-dark transition-colors"
                        x-text="i18n.continuePayment + ' · $' + total.toFixed(2)">
                </button>
            </div>
        </div>
    </form>
</div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function privateEventForm(cfg) {
    const designs = cfg.designs || {};
    const i18n = cfg.i18n || {};
    return {
        step: 0,
        i18n,
        stepLabels: i18n.stepLabels || ['Event info', 'Design', 'Invitation text', 'Payment'],
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
        previewUrl: cfg.previewUrl || '',
        previewHeight: 680,
        previewTimer: null,
        unit: cfg.unit,
        premiumExtra: cfg.premiumExtra,
        fee: cfg.fee,
        maxTickets: cfg.maxTickets || 500,
        get stepOfLabel() {
            const tpl = this.i18n.stepOf || 'Step {current} of {total}';
            return tpl.replace('{current}', this.step + 1).replace('{total}', '4');
        },
        init() {
            if (this.design && this.designs[this.design]) {
                this.invitationDesignId = this.designs[this.design].invitation_design_id || null;
                this.seedFieldDefaults();
                this.applyBasicsToFieldValues();
            }
            this.$watch('categoryId', () => {
                this.design = '';
                this.invitationDesignId = null;
                this.fitPickerTiles();
            });
            this.$watch('eventDate', () => this.applyBasicsToFieldValues());
            this.$watch('eventTime', () => this.applyBasicsToFieldValues());
            this.$watch('venue', () => this.applyBasicsToFieldValues());
            this.$watch('fieldValues', () => this.queuePreview(), { deep: true });
            this.$watch('invitationDesignId', () => this.queuePreview());
            this.$watch('step', (step) => {
                if (step === 1) this.fitPickerTiles();
                if (step === 2 || step === 3) this.refreshPreview();
            });
            this.observePickerTiles();
            window.addEventListener('message', (e) => {
                if (e.data && e.data.type === 'ekaadh-invite-preview-height' && e.data.height) {
                    this.previewHeight = Math.max(520, Number(e.data.height) || 680);
                }
            });
            // Ensure default time paints into the time input and card fields.
            this.$nextTick(() => this.applyBasicsToFieldValues());
        },
        observePickerTiles() {
            const apply = (tile) => {
                const inner = tile.querySelector('.invite-picker-tile-scale');
                if (!inner) return;
                const w = tile.clientWidth || 0;
                if (w < 8) return;
                inner.style.transform = `scale(${w / 420})`;
            };
            this.$nextTick(() => {
                document.querySelectorAll('.invite-picker-tile').forEach((tile) => {
                    apply(tile);
                    if (tile.dataset.fitObserved) return;
                    tile.dataset.fitObserved = '1';
                    new ResizeObserver(() => apply(tile)).observe(tile);
                });
            });
        },
        fitPickerTiles() {
            this.observePickerTiles();
        },
        selectDesign(slug, id) {
            this.design = slug;
            this.invitationDesignId = id || this.designs[slug]?.invitation_design_id || null;
            this.seedFieldDefaults();
            this.applyBasicsToFieldValues();
            this.stepError = '';
            this.queuePreview();
        },
        queuePreview() {
            clearTimeout(this.previewTimer);
            this.previewTimer = setTimeout(() => this.refreshPreview(), 280);
        },
        async refreshPreview() {
            if (!this.invitationDesignId || (this.step !== 2 && this.step !== 3) || !this.previewUrl) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            try {
                const res = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/html',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        invitation_design_id: this.invitationDesignId,
                        fields: this.fieldValues || {},
                        event_date: this.eventDate,
                        event_time: this.eventTime,
                        venue: this.venue,
                    }),
                });
                if (!res.ok) return;
                const html = await res.text();
                const frames = [this.$refs.themePreview, this.$refs.themePreviewPay].filter(Boolean);
                frames.forEach((frame) => { frame.srcdoc = html; });
            } catch (e) {}
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
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
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
                if (!this.categoryId) { this.stepError = this.i18n.chooseCategory || 'Choose a category.'; return false; }
                if (!String(this.description || '').trim()) { this.stepError = this.i18n.enterDescription || 'Enter a description.'; return false; }
                if (!this.eventDate) { this.stepError = this.i18n.chooseEventDate || 'Choose an event date.'; return false; }
                if (!this.eventTime) { this.stepError = this.i18n.chooseEventTime || 'Choose an event time.'; return false; }
                if (!String(this.venue || '').trim()) { this.stepError = this.i18n.enterVenue || 'Enter a venue.'; return false; }
                return true;
            }
            if (step === 1) {
                if (!this.design || !this.selectedDesign || !this.invitationDesignId) {
                    this.stepError = this.i18n.chooseInvitationDesign || 'Choose an invitation design.';
                    return false;
                }
                this.seedFieldDefaults();
                this.applyBasicsToFieldValues();
                return true;
            }
            if (step === 2) {
                if (!this.selectedDesign) {
                    this.stepError = this.i18n.chooseInvitationDesign || 'Choose an invitation design.';
                    return false;
                }
                for (const f of this.buyerDesignFields) {
                    const value = String(this.fieldValues[f.field_key] || '').trim();
                    if (f.is_required && !value) {
                        const fieldName = f.label || 'Field';
                        this.stepError = (this.i18n.fieldRequired || '{field} is required.').replace('{field}', fieldName);
                        return false;
                    }
                }
                return true;
            }
            if (step === 3) {
                const q = Number(this.qty) || 0;
                if (q < 1 || q > this.maxTickets) {
                    this.stepError = this.i18n.chooseValidQuantity || 'Choose a valid ticket quantity.';
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
        goToStep(i) {
            if (i > this.step) return;
            this.stepError = '';
            this.step = i;
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
