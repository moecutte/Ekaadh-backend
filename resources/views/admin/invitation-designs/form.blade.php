@extends('layouts.admin')
@section('title', $design->exists ? 'Edit design' : 'New design')
@section('heading', $design->exists ? 'Edit design' : 'New invitation design')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Dancing+Script:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Great+Vibes&family=Italianno&family=Josefin+Sans:ital,wght@0,400;0,600;0,700;1,400&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Merriweather:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;500;600;700&family=Mr+De+Haviland&family=Parisienne&family=Pinyon+Script&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&family=Raleway:ital,wght@0,400;0,600;0,700;1,400&family=Rouge+Script&family=Sacramento&family=Satisfy&family=Source+Sans+3:wght@400;600;700&family=Tangerine:wght@400;700&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<a href="{{ route('admin.invitation-designs.index') }}" class="text-sm font-bold text-mute hover:text-brand">&larr; Designs</a>

@if(session('success'))
    <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm p-3">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mt-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-3">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mt-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-3">
        <p class="font-bold mb-1">Could not save field</p>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $fieldsPayload = $design->exists
        ? $design->fields->map(fn ($f) => [
            'id' => $f->id,
            'field_key' => $f->field_key,
            'label' => $f->label,
            'field_type' => $f->field_type,
            'default_text' => $f->default_text ?? '',
            'placeholder' => $f->placeholder,
            'is_required' => (bool) $f->is_required,
            'maps_to_couple' => (bool) $f->maps_to_couple,
            'show_on_card' => (bool) $f->show_on_card,
            'pos_x' => (float) ($f->pos_x ?? 20),
            'pos_y' => (float) ($f->pos_y ?? 30),
            'box_width' => (float) ($f->box_width ?? 60),
            'font_size' => (int) ($f->font_size ?? 18),
            'font_family' => $f->font_family ?: 'Montserrat',
            'font_weight' => $f->font_weight ?: '400',
            'font_style' => $f->font_style ?: 'normal',
            'color' => $f->color ?: ($design->text_color ?: '#3d3348'),
            'text_align' => $f->text_align ?: 'center',
            'sort_order' => (int) $f->sort_order,
            'update_url' => route('admin.invitation-designs.fields.update', [$design, $f]),
            'delete_url' => route('admin.invitation-designs.fields.destroy', [$design, $f]),
        ])->values()->all()
        : [];
@endphp

<div class="mt-5"
     x-data="designEditor({
        graphicUrl: @js($design->graphic_url),
        cardBg: @js($design->card_bg ?: '#faf7fc'),
        fields: @js($fieldsPayload),
        csrf: @js(csrf_token()),
        storeFieldUrl: @js($design->exists ? route('admin.invitation-designs.fields.store', $design) : null),
     })">

<form method="POST"
      action="{{ $design->exists ? route('admin.invitation-designs.update', $design) : route('admin.invitation-designs.store') }}"
      enctype="multipart/form-data"
      class="grid lg:grid-cols-12 gap-5">
    @csrf
    @if($design->exists) @method('PUT') @endif

    <div class="lg:col-span-5 space-y-5">
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
                    <label class="text-xs font-bold text-mute block mb-1">Render mode *</label>
                    <select name="render_mode" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        <option value="overlay" @selected(old('render_mode', $design->render_mode ?? 'overlay')==='overlay')>Overlay (graphic)</option>
                        <option value="blade" @selected(old('render_mode', $design->render_mode)==='blade')>Built-in layout</option>
                    </select>
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
                <label class="text-xs font-bold text-mute block mb-1">Blade key</label>
                <input name="blade_key" value="{{ old('blade_key', $design->blade_key) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="optional">
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    'accent' => ['Accent', '#705898'],
                    'accent_soft' => ['Accent soft', '#f3eef8'],
                    'card_bg' => ['Card BG', '#faf7fc'],
                    'text_color' => ['Text', '#3d3348'],
                    'muted_color' => ['Muted', '#6b6280'],
                    'border_color' => ['Border', '#c5a059'],
                    'header_from' => ['Header from', '#4b3664'],
                    'header_to' => ['Header to', '#9b84b6'],
                ] as $key => [$label, $fallback])
                    @php $val = old($key, $design->{$key}) ?: $fallback; @endphp
                    <div>
                        <label class="text-xs font-bold text-mute block mb-1">{{ $label }}</label>
                        <div class="flex items-center gap-2">
                            @if($key === 'card_bg')
                                <input type="color" x-model="cardBg"
                                       class="h-10 w-12 shrink-0 rounded-lg border border-slate-200 bg-white p-1 cursor-pointer">
                                <input type="text" name="card_bg" x-model="cardBg"
                                       pattern="^#([A-Fa-f0-9]{6})$" maxlength="7"
                                       class="flex-1 min-w-0 rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono">
                            @else
                                <input type="color" value="{{ $val }}"
                                       class="h-10 w-12 shrink-0 rounded-lg border border-slate-200 bg-white p-1 cursor-pointer"
                                       oninput="this.nextElementSibling.value = this.value">
                                <input type="text" name="{{ $key }}" value="{{ $val }}"
                                       pattern="^#([A-Fa-f0-9]{6})$" maxlength="7"
                                       class="flex-1 min-w-0 rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono"
                                       oninput="if(/^#[A-Fa-f0-9]{6}$/.test(this.value)) this.previousElementSibling.value = this.value">
                            @endif
                        </div>
                    </div>
                @endforeach
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
            <h3 class="text-sm font-bold">Upload graphic</h3>
            <p class="text-xs text-mute">Upload PNG/JPG artwork. Preview updates instantly on the right.</p>
            <input type="file" name="graphic" accept="image/*" class="w-full text-sm"
                   @change="onGraphicPicked($event)">
            <input type="file" name="thumbnail" accept="image/*" class="w-full text-sm">
            <p class="text-[11px] text-mute">Thumbnail optional.</p>
        </div>

        <button class="w-full py-3 rounded-2xl bg-brand text-white font-extrabold text-sm">{{ $design->exists ? 'Save design settings' : 'Create design & continue' }}</button>
        @unless($design->exists)
            <p class="text-xs text-mute text-center">After creating, you’ll place text fields on the preview by dragging.</p>
        @endunless
    </div>

    {{-- Live visual canvas --}}
    <div class="lg:col-span-7">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 sticky top-4">
            <div class="flex items-center justify-between mb-3 gap-2">
                <div>
                    <h3 class="text-sm font-bold">Live design preview</h3>
                    <p class="text-xs text-mute mt-0.5">Drag to move · pull handles to resize · changes auto-save</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold px-2 py-1 rounded-full transition-colors"
                          x-show="saveState"
                          x-cloak
                          :class="saveState === 'saving' ? 'bg-amber-50 text-amber-700' : (saveState === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700')"
                          x-text="saveState === 'saving' ? 'Saving…' : (saveState === 'error' ? 'Save failed' : 'Saved')"></span>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-slate-100 text-mute" x-show="activeFieldId && !saveState" x-cloak>Editing selected field</span>
                </div>
            </div>

            <div class="mx-auto rounded-xl border border-slate-200 shadow-inner bg-slate-50/50 p-2"
                 style="max-width: 436px;"
                 x-ref="stage"
                 @mousemove.window="onPointerMove($event)"
                 @mouseup.window="endPointer()"
                 @mouseleave.window="endPointer()">
                <div class="relative w-full select-none rounded-lg"
                     :style="{ aspectRatio: '3 / 4.2', background: cardBg }"
                     x-ref="canvas">
                    <div class="absolute inset-0 overflow-hidden rounded-lg pointer-events-none">
                        <template x-if="previewSrc">
                            <img :src="previewSrc" alt="Design graphic" class="absolute inset-0 w-full h-full object-cover">
                        </template>
                        <template x-if="!previewSrc">
                            <div class="absolute inset-0 flex items-center justify-center text-sm text-mute px-6 text-center pointer-events-none">
                                Upload a graphic to see the invitation preview here.
                            </div>
                        </template>
                    </div>

                    <template x-for="field in fields" :key="field.id || field._tmp">
                        <div class="absolute group"
                             :class="activeFieldId === (field.id || field._tmp) ? 'z-20' : 'z-10'"
                             :style="boxStyle(field)"
                             @click.stop="selectField(field)">
                            <div class="absolute -top-6 left-0 right-0 flex items-center gap-1 cursor-grab active:cursor-grabbing z-30"
                                 @mousedown.prevent="startDrag(field, $event)">
                                <span class="text-[9px] font-bold truncate px-1.5 py-0.5 rounded"
                                      :class="activeFieldId === (field.id || field._tmp) ? 'bg-brand text-white' : 'bg-black/55 text-white'"
                                      x-text="'⠿ ' + (field.field_type === 'qr' ? 'QR code' : field.label)"></span>
                            </div>
                            <template x-if="field.field_type === 'qr'">
                                <div class="flex flex-col items-center text-center rounded border-2 border-dashed bg-white/90 px-1 py-1 w-full"
                                     :class="activeFieldId === (field.id || field._tmp) ? 'border-brand' : 'border-slate-400'">
                                    <div class="w-full aspect-square max-w-[72px] mx-auto flex items-center justify-center bg-slate-100">
                                        <span class="text-[10px] font-bold text-slate-600">QR</span>
                                    </div>
                                    <p class="mt-0.5 text-[7px] leading-tight text-slate-600 w-full">
                                        Scan at entry · Status: <span class="font-semibold text-brand">Valid</span>
                                    </p>
                                </div>
                            </template>
                            <template x-if="field.field_type !== 'qr'">
                            {{-- Match ticket/user overlay: px-1 only — no border/py (those shift glyphs vs live preview) --}}
                            <div class="min-h-[1.2em] outline-none px-1 leading-tight rounded-sm"
                                 :class="activeFieldId === (field.id || field._tmp)
                                    ? 'outline outline-1 outline-dashed outline-brand bg-white/20'
                                    : 'outline outline-1 outline-dashed outline-transparent hover:outline-brand/40'"
                                 :style="textStyle(field)"
                                 contenteditable="true"
                                 spellcheck="false"
                                 x-init="$el.innerText = field.default_text || field.label || ''"
                                 x-effect="if (document.activeElement !== $el) $el.innerText = field.default_text || field.label || ''"
                                 @focus="selectField(field)"
                                 @input="field.default_text = $event.target.innerText"
                                 @blur="queueAutosave(field)"></div>
                            </template>

                            {{-- Free resize handles (active field only) --}}
                            <div class="absolute inset-0 pointer-events-none z-40"
                                 x-show="activeFieldId === (field.id || field._tmp)"
                                 x-cloak>
                                <span class="pointer-events-auto absolute -left-1.5 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white border-2 border-brand shadow cursor-ew-resize"
                                      @mousedown.prevent.stop="startResize(field, 'w', $event)"></span>
                                <span class="pointer-events-auto absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white border-2 border-brand shadow cursor-ew-resize"
                                      @mousedown.prevent.stop="startResize(field, 'e', $event)"></span>
                                <span class="pointer-events-auto absolute left-1/2 -translate-x-1/2 -top-1.5 w-3 h-3 rounded-full bg-white border-2 border-brand shadow cursor-ns-resize"
                                      @mousedown.prevent.stop="startResize(field, 'n', $event)"></span>
                                <span class="pointer-events-auto absolute left-1/2 -translate-x-1/2 -bottom-1.5 w-3 h-3 rounded-full bg-white border-2 border-brand shadow cursor-ns-resize"
                                      @mousedown.prevent.stop="startResize(field, 's', $event)"></span>
                                <span class="pointer-events-auto absolute -left-1.5 -top-1.5 w-3.5 h-3.5 rounded-sm bg-brand border-2 border-white shadow cursor-nwse-resize"
                                      @mousedown.prevent.stop="startResize(field, 'nw', $event)"></span>
                                <span class="pointer-events-auto absolute -right-1.5 -top-1.5 w-3.5 h-3.5 rounded-sm bg-brand border-2 border-white shadow cursor-nesw-resize"
                                      @mousedown.prevent.stop="startResize(field, 'ne', $event)"></span>
                                <span class="pointer-events-auto absolute -left-1.5 -bottom-1.5 w-3.5 h-3.5 rounded-sm bg-brand border-2 border-white shadow cursor-nesw-resize"
                                      @mousedown.prevent.stop="startResize(field, 'sw', $event)"></span>
                                <span class="pointer-events-auto absolute -right-1.5 -bottom-1.5 w-3.5 h-3.5 rounded-sm bg-brand border-2 border-white shadow cursor-nwse-resize"
                                      @mousedown.prevent.stop="startResize(field, 'se', $event)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <p class="text-[11px] text-mute mt-3 text-center" x-show="fields.length" x-cloak>
                Drag <strong>⠿</strong> to move · corner/edge handles to resize · layout auto-saves
            </p>
        </div>
    </div>
</form>

@if($design->exists)
<div class="mt-6 grid lg:grid-cols-12 gap-5">
    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h3 class="text-sm font-bold mb-1">Text fields</h3>
        <p class="text-xs text-mute mb-3">Collapsed by default — click a field to edit. Selecting on the canvas also opens it.</p>

        <div class="space-y-2">
            <template x-for="field in fields" :key="'form-'+ (field.id || field._tmp)">
                <form method="POST"
                      :action="field.update_url || '#'"
                      @submit="handleFieldSubmit($event, field)"
                      class="border rounded-xl overflow-hidden"
                      :class="activeFieldId === (field.id || field._tmp) ? 'border-brand ring-2 ring-brand/15' : 'border-slate-100'">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="pos_x" :value="Number(field.pos_x).toFixed(2)">
                    <input type="hidden" name="pos_y" :value="Number(field.pos_y).toFixed(2)">
                    <input type="hidden" name="box_width" :value="Number(field.box_width).toFixed(2)">
                    <input type="hidden" name="default_text" :value="field.default_text">
                    <input type="hidden" name="placeholder" :value="field.placeholder || ''">
                    <input type="hidden" name="font_weight" :value="field.font_weight || '400'">
                    <input type="hidden" name="font_style" :value="field.font_style || 'normal'">

                    <button type="button"
                            class="w-full flex items-center gap-2 px-3 py-2.5 text-left hover:bg-slate-50"
                            @click="toggleFieldPanel(field)">
                        <span class="text-mute text-xs w-4 shrink-0"
                              x-text="isFieldOpen(field) ? '▾' : '▸'"></span>
                        <span class="font-bold text-sm flex-1 min-w-0 truncate" x-text="field.label || field.field_key"></span>
                        <span class="text-[10px] text-mute font-mono shrink-0"
                              x-text="'x '+Number(field.pos_x).toFixed(0)+'% · y '+Number(field.pos_y).toFixed(0)+'%'"></span>
                    </button>

                    <div class="px-3 pb-3 space-y-2 border-t border-slate-100" x-show="isFieldOpen(field)" x-cloak @click="selectField(field)">
                    <div x-show="field.field_type !== 'qr'">
                        <label class="text-[10px] font-bold uppercase text-mute">Text on card</label>
                        <input x-model="field.default_text" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs" placeholder="Type here or on the preview">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Key</label>
                            <input name="field_key" x-model="field.field_key" required class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-mono">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Label</label>
                            <input name="label" x-model="field.label" required class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Type</label>
                            <select name="field_type" x-model="field.field_type" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs"
                                    @change="onFieldTypeChange(field)">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="date_month">Month name (auto)</option>
                                <option value="date_day">Day of month (auto)</option>
                                <option value="date_year">Year (auto)</option>
                                <option value="date_time">Time (auto)</option>
                                <option value="qr">QR code spot</option>
                            </select>
                            <p class="text-[10px] text-mute mt-0.5" x-show="isAutoDateType(field.field_type)" x-cloak>
                                Filled from the buyer’s event date/time.
                            </p>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Width %</label>
                            <input type="number" step="0.1" x-model.number="field.box_width" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Font size</label>
                            <input type="number" name="font_size" x-model.number="field.font_size" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Color</label>
                            <input type="color" x-model="field.color" class="w-full h-8 rounded-lg border border-slate-200 px-1 py-0.5">
                            <input type="hidden" name="color" :value="field.color">
                        </div>
                        <div class="col-span-2">
                            <label class="text-[10px] font-bold uppercase text-mute">Font family</label>
                            <select name="font_family" x-model="field.font_family"
                                    class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm"
                                    :style="{ fontFamily: `'${field.font_family}', serif` }">
                                <template x-for="font in fontFamilies" :key="'ff-'+field.id+'-'+font">
                                    <option :value="font" :style="{ fontFamily: `'${font}', serif` }" x-text="font"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-base leading-none"
                               :style="{ fontFamily: `'${field.font_family}', serif`, fontWeight: field.font_weight || '400', color: field.color }"
                               x-text="field.default_text || 'Sample Aa'"></p>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Align</label>
                            <select name="text_align" x-model="field.text_align" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase text-mute">Sort</label>
                            <input type="number" name="sort_order" x-model.number="field.sort_order" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                        </div>
                        <div class="col-span-2" x-show="field.field_type !== 'qr'">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold cursor-pointer select-none">
                                <input type="checkbox"
                                       class="rounded border-slate-300"
                                       :checked="Number(field.font_weight || 400) >= 600"
                                       @change="field.font_weight = $event.target.checked ? '700' : '400'">
                                <span class="font-bold">Bold text</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 text-xs">
                        <label class="flex items-center gap-1"><input type="checkbox" name="is_required" value="1" :checked="field.is_required" @change="field.is_required = $event.target.checked"> Required</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="maps_to_couple" value="1" :checked="field.maps_to_couple" @change="field.maps_to_couple = $event.target.checked"> Couple field</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="show_on_card" value="1" :checked="field.show_on_card" @change="field.show_on_card = $event.target.checked"> Show on card</label>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="px-3 py-1.5 rounded-lg bg-brand text-white text-xs font-bold"
                                x-text="field.id ? 'Save field' : 'Save new field →'"></button>
                        <button type="button" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-bold"
                                @click.prevent="deleteField(field)">Delete</button>
                    </div>
                    <p class="text-[10px] text-amber-700" x-show="!field.id" x-cloak>
                        New fields are not saved until you click <strong>Add &amp; save field</strong> on the right.
                    </p>
                    </div>
                </form>
            </template>
            <p class="text-sm text-mute" x-show="!fields.length">No fields yet — add one on the right, then drag it on the preview.</p>
        </div>
    </div>

    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 h-fit">
        <button type="button" class="w-full flex items-center justify-between gap-2 text-left"
                @click="addFieldOpen = !addFieldOpen">
            <h3 class="text-sm font-bold">Add text field</h3>
            <span class="text-mute text-xs" x-text="addFieldOpen ? '▾' : '▸'"></span>
        </button>
        <div x-show="addFieldOpen" x-cloak>
        <p class="text-xs text-mute mb-3 mt-2">Creates a box in the center of the preview. Drag it into place, type the sample text, then save.</p>
        <form method="POST" action="{{ route('admin.invitation-designs.fields.store', $design) }}" class="space-y-3"
              @submit.prevent="submitNewField($event)">
            @csrf
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Key *</label>
                <input name="field_key" x-model="newField.field_key" required
                       pattern="[a-z0-9_]+"
                       title="Lowercase letters, numbers, and underscores only"
                       placeholder="invite_line"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono"
                       @input="newField._keyTouched = true"
                       @blur="newField.field_key = slugKey(newField.field_key)">
                <p class="text-[10px] text-mute mt-1">Use snake_case, e.g. <code>guest_name</code>. Auto-filled from the label if left blank.</p>
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Customer label *</label>
                <input name="label" x-model="newField.label" required placeholder="Invite line"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                       @input="if (!newField._keyTouched) newField.field_key = slugKey(newField.label)">
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Default / sample text</label>
                <input name="default_text" x-model="newField.default_text" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Type what appears on the card">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">Font size</label>
                    <input type="number" name="font_size" x-model.number="newField.font_size" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-bold text-mute block mb-1">Color</label>
                    <input type="color" x-model="newField.color" class="w-full h-10 rounded-xl border border-slate-200 px-1">
                    <input type="hidden" name="color" :value="newField.color">
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Font family</label>
                <select name="font_family" x-model="newField.font_family"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        :style="{ fontFamily: `'${newField.font_family}', serif` }">
                    <template x-for="font in fontFamilies" :key="'new-'+font">
                        <option :value="font" :style="{ fontFamily: `'${font}', serif` }" x-text="font"></option>
                    </template>
                </select>
                <p class="mt-2 text-lg leading-none"
                   :style="{ fontFamily: `'${newField.font_family}', serif`, fontWeight: newField.font_weight || '400', color: newField.color }"
                   x-text="newField.default_text || 'Sample Aa'"></p>
            </div>
            <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                <input type="checkbox"
                       class="rounded border-slate-300"
                       :checked="Number(newField.font_weight || 400) >= 600"
                       @change="newField.font_weight = $event.target.checked ? '700' : '400'">
                <span class="font-bold">Bold text</span>
            </label>
            <div>
                <label class="text-xs font-bold text-mute block mb-1">Field type</label>
                <select x-model="newField.field_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        @change="onFieldTypeChange(newField)">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="date_month">Month name (auto from event date)</option>
                    <option value="date_day">Day of month (auto)</option>
                    <option value="date_year">Year (auto)</option>
                    <option value="date_time">Time (auto from event time)</option>
                </select>
                <p class="text-[10px] text-mute mt-1" x-show="isAutoDateType(newField.field_type)" x-cloak>
                    Buyers won’t type this — choosing event date/time fills it on the invitation.
                </p>
            </div>
            <input type="hidden" name="field_type" :value="newField.field_type || 'text'">
            <input type="hidden" name="font_style" value="normal">
            <input type="hidden" name="text_align" value="center">
            <input type="hidden" name="pos_x" :value="newField.pos_x">
            <input type="hidden" name="pos_y" :value="newField.pos_y">
            <input type="hidden" name="box_width" :value="newField.box_width">
            <input type="hidden" name="font_weight" :value="newField.font_weight || '400'">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_required" value="1"> Required</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="maps_to_couple" value="1"> Couple field (Aroos/Meher)</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_on_card" value="1" checked> Show on card</label>
            <button type="button" class="w-full py-2 rounded-xl border border-slate-200 text-sm font-bold" @click="previewNewField()">Preview on canvas</button>
            <button type="button" class="w-full py-2 rounded-xl border border-brand/30 text-brand text-sm font-bold" @click="addDatePartFields()">Add date &amp; time fields</button>
            <button type="button" class="w-full py-2 rounded-xl border border-brand/30 text-brand text-sm font-bold" @click="addQrSpot()">Add QR code spot</button>
            <button type="submit" class="w-full py-2.5 rounded-xl bg-brand text-white text-sm font-bold">Add &amp; save field</button>
        </form>
        </div>
    </div>
</div>
@endif
</div>

<script>
function designEditor(cfg) {
    return {
        previewSrc: cfg.graphicUrl || null,
        cardBg: cfg.cardBg || '#faf7fc',
        fields: (cfg.fields || []).map(f => ({ ...f })),
        csrf: cfg.csrf,
        activeFieldId: null,
        openFieldId: null,
        addFieldOpen: true,
        dragging: null,
        resizing: null,
        saveState: '',
        saveTimer: null,
        saveClearTimer: null,
        fontFamilies: [
            // Script / calligraphy
            'Great Vibes',
            'Dancing Script',
            'Sacramento',
            'Pinyon Script',
            'Allura',
            'Alex Brush',
            'Italianno',
            'Parisienne',
            'Satisfy',
            'Tangerine',
            'Rouge Script',
            'Mr De Haviland',
            // Elegant serif
            'Playfair Display',
            'Cormorant Garamond',
            'Cinzel',
            'Lora',
            'EB Garamond',
            'Libre Baskerville',
            'Merriweather',
            'Amiri',
            // Clean sans
            'Montserrat',
            'Poppins',
            'Source Sans 3',
            'Raleway',
            'Josefin Sans',
            'Quicksand',
        ],
        newField: {
            field_key: '',
            label: '',
            default_text: 'Sample text',
            field_type: 'text',
            font_size: 18,
            font_family: 'Great Vibes',
            font_weight: '400',
            color: '#3d3348',
            pos_x: 20,
            pos_y: 35,
            box_width: 60,
            _keyTouched: false,
        },
        slugKey(value) {
            return String(value || '')
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '')
                .replace(/_+/g, '_');
        },
        isAutoDateType(type) {
            return ['date_month', 'date_day', 'date_year', 'date_time'].includes(String(type || ''));
        },
        dateTypeMeta(type) {
            const map = {
                date_month: { key: 'date_month', label: 'Month', sample: 'January', y: 28 },
                date_day: { key: 'date_day', label: 'Day', sample: '15', y: 36 },
                date_year: { key: 'date_year', label: 'Year', sample: String(new Date().getFullYear()), y: 44 },
                date_time: { key: 'date_time', label: 'Time', sample: '6:00 PM', y: 52 },
            };
            return map[type] || null;
        },
        onFieldTypeChange(field) {
            if (!this.isAutoDateType(field.field_type)) return;
            const meta = this.dateTypeMeta(field.field_type);
            if (!meta) return;
            if (!field.field_key || this.isAutoDateType(field._lastAutoType)) {
                field.field_key = meta.key;
                field._keyTouched = true;
            }
            if (!field.label || this.isAutoDateType(field._lastAutoType)) {
                field.label = meta.label;
            }
            field.default_text = meta.sample;
            field._lastAutoType = field.field_type;
        },
        async addDatePartFields() {
            if (!cfg.storeFieldUrl) {
                alert('Save the design first, then add date fields.');
                return;
            }
            const existingTypes = new Set(this.fields.map(f => f.field_type));
            const types = ['date_month', 'date_day', 'date_year', 'date_time'];
            const missing = types.filter(t => !existingTypes.has(t));
            if (!missing.length) {
                alert('This design already has month, day, year, and time fields.');
                return;
            }
            for (const type of missing) {
                const meta = this.dateTypeMeta(type);
                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('field_key', meta.key);
                body.append('label', meta.label);
                body.append('default_text', meta.sample);
                body.append('field_type', type);
                body.append('font_size', '18');
                body.append('font_family', 'Great Vibes');
                body.append('font_weight', '400');
                body.append('font_style', 'normal');
                body.append('color', '#3d3348');
                body.append('text_align', 'center');
                body.append('pos_x', '20');
                body.append('pos_y', String(meta.y));
                body.append('box_width', '60');
                body.append('show_on_card', '1');
                const res = await fetch(cfg.storeFieldUrl, {
                    method: 'POST',
                    body,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                // Laravel may redirect (302) on success for non-JSON — treat redirect/ok as success.
                if (!res.ok && res.status !== 302 && res.type !== 'opaqueredirect') {
                    // Still try redirect follow: fetch follows redirects; check final URL or reload anyway on HTML success.
                    if (res.status >= 400) {
                        alert('Could not add ' + meta.label + ' field. Try adding it manually.');
                        return;
                    }
                }
            }
            window.location.reload();
        },
        syncPreviewIntoNewField() {
            const tmp = this.fields.find(f => !f.id);
            if (!tmp) return;
            this.newField.pos_x = tmp.pos_x;
            this.newField.pos_y = tmp.pos_y;
            this.newField.box_width = tmp.box_width;
            this.newField.default_text = tmp.default_text;
            this.newField.font_size = tmp.font_size;
            this.newField.font_family = tmp.font_family;
            this.newField.font_weight = tmp.font_weight || '400';
            this.newField.color = tmp.color;
            if (tmp.field_key) this.newField.field_key = tmp.field_key;
            if (tmp.label) this.newField.label = tmp.label;
            if (tmp.field_type) this.newField.field_type = tmp.field_type;
        },
        submitNewField(e) {
            this.syncPreviewIntoNewField();
            if (!this.newField.field_key) {
                this.newField.field_key = this.slugKey(this.newField.label);
            } else {
                this.newField.field_key = this.slugKey(this.newField.field_key);
            }
            if (!this.newField.field_key) {
                alert('Add a key or label first (e.g. guest_name).');
                return;
            }
            if (!this.newField.label) {
                alert('Customer label is required.');
                return;
            }
            this.$nextTick(() => {
                // Native submit skips Alpine handlers and uses current input values.
                e.target.submit();
            });
        },
        onGraphicPicked(e) {
            const file = e.target.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => { this.previewSrc = ev.target.result; };
            reader.readAsDataURL(file);
        },
        selectField(field) {
            const id = field.id || field._tmp;
            this.activeFieldId = id;
            this.openFieldId = id;
            if (field.font_family && !this.fontFamilies.includes(field.font_family)) {
                this.fontFamilies = [field.font_family, ...this.fontFamilies];
            }
        },
        fieldKey(field) {
            return field.id || field._tmp;
        },
        isFieldOpen(field) {
            return this.openFieldId === this.fieldKey(field);
        },
        toggleFieldPanel(field) {
            const id = this.fieldKey(field);
            if (this.openFieldId === id) {
                this.openFieldId = null;
                return;
            }
            this.selectField(field);
        },
        boxStyle(field) {
            return {
                left: (field.pos_x ?? 20) + '%',
                top: (field.pos_y ?? 30) + '%',
                width: (field.box_width ?? 60) + '%',
            };
        },
        textStyle(field) {
            return {
                fontSize: (field.font_size || 18) + 'px',
                fontFamily: `'${field.font_family || 'Montserrat'}', serif`,
                fontWeight: field.font_weight || '400',
                fontStyle: field.font_style || 'normal',
                color: field.color || '#3d3348',
                textAlign: field.text_align || 'center',
                lineHeight: '1.25',
                cursor: 'text',
            };
        },
        round1(n) {
            return Math.round(n * 10) / 10;
        },
        clamp(n, min, max) {
            return Math.min(max, Math.max(min, n));
        },
        startDrag(field, e) {
            this.selectField(field);
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const rect = canvas.getBoundingClientRect();
            this.resizing = null;
            this.dragging = {
                field,
                offsetX: e.clientX - rect.left - (field.pos_x / 100) * rect.width,
                offsetY: e.clientY - rect.top - (field.pos_y / 100) * rect.height,
                dirty: false,
            };
        },
        startResize(field, handle, e) {
            this.selectField(field);
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const rect = canvas.getBoundingClientRect();
            this.dragging = null;
            this.resizing = {
                field,
                handle,
                startX: e.clientX,
                startY: e.clientY,
                startPosX: Number(field.pos_x) || 0,
                startPosY: Number(field.pos_y) || 0,
                startWidth: Number(field.box_width) || 60,
                startFont: Number(field.font_size) || 18,
                canvasW: rect.width,
                canvasH: rect.height,
                dirty: false,
            };
        },
        onPointerMove(e) {
            if (this.dragging) {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                const rect = canvas.getBoundingClientRect();
                const w = Number(this.dragging.field.box_width) || 60;
                let x = ((e.clientX - rect.left - this.dragging.offsetX) / rect.width) * 100;
                let y = ((e.clientY - rect.top - this.dragging.offsetY) / rect.height) * 100;
                x = this.clamp(x, 0, 100 - w);
                y = this.clamp(y, 0, 95);
                this.dragging.field.pos_x = this.round1(x);
                this.dragging.field.pos_y = this.round1(y);
                this.dragging.dirty = true;
                return;
            }
            if (!this.resizing) return;

            const r = this.resizing;
            const dxPct = ((e.clientX - r.startX) / r.canvasW) * 100;
            const dyPct = ((e.clientY - r.startY) / r.canvasH) * 100;
            const h = r.handle;
            let x = r.startPosX;
            let y = r.startPosY;
            let w = r.startWidth;
            let font = r.startFont;

            // Horizontal resize
            if (h.includes('e')) {
                w = this.clamp(r.startWidth + dxPct, 8, 100 - r.startPosX);
            }
            if (h.includes('w')) {
                const right = r.startPosX + r.startWidth;
                x = this.clamp(r.startPosX + dxPct, 0, right - 8);
                w = this.clamp(right - x, 8, 100);
            }

            // Vertical: move box and/or scale font (no stored height — font size is the visual height control)
            if (h === 'n' || h === 'nw' || h === 'ne') {
                y = this.clamp(r.startPosY + dyPct, 0, 95);
                if (h !== 'n') {
                    const scale = this.clamp(1 - (dyPct / 40), 0.55, 1.8);
                    font = this.clamp(Math.round(r.startFont * scale), 8, 72);
                } else {
                    const scale = this.clamp(1 - (dyPct / 35), 0.55, 1.8);
                    font = this.clamp(Math.round(r.startFont * scale), 8, 72);
                }
            }
            if (h === 's' || h === 'sw' || h === 'se') {
                const scale = this.clamp(1 + (dyPct / 35), 0.55, 1.8);
                font = this.clamp(Math.round(r.startFont * scale), 8, 72);
            }

            // Corner free scale also ties font to width change when mostly horizontal
            if ((h === 'se' || h === 'ne' || h === 'sw' || h === 'nw') && Math.abs(dxPct) > Math.abs(dyPct) * 1.2) {
                const scaleW = this.clamp(w / r.startWidth, 0.55, 1.8);
                font = this.clamp(Math.round(r.startFont * scaleW), 8, 72);
            }

            r.field.pos_x = this.round1(x);
            r.field.pos_y = this.round1(y);
            r.field.box_width = this.round1(w);
            if (r.field.field_type !== 'qr') {
                r.field.font_size = font;
            }
            r.dirty = true;
        },
        endPointer() {
            const target = this.dragging?.dirty ? this.dragging.field
                : (this.resizing?.dirty ? this.resizing.field : null);
            this.dragging = null;
            this.resizing = null;
            if (target) {
                this.queueAutosave(target);
            }
        },
        queueAutosave(field) {
            if (!field?.id || !field.update_url) return;
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.autosaveField(field), 280);
        },
        async autosaveField(field) {
            if (!field?.id || !field.update_url) return;
            this.saveState = 'saving';
            clearTimeout(this.saveClearTimer);

            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('_method', 'PUT');
            body.append('field_key', field.field_key || '');
            body.append('label', field.label || '');
            body.append('field_type', field.field_type || 'text');
            body.append('default_text', field.default_text ?? '');
            body.append('placeholder', field.placeholder || '');
            body.append('pos_x', Number(field.pos_x ?? 0).toFixed(2));
            body.append('pos_y', Number(field.pos_y ?? 0).toFixed(2));
            body.append('box_width', Number(field.box_width ?? 60).toFixed(2));
            body.append('font_size', String(field.font_size ?? 18));
            body.append('font_family', field.font_family || 'Montserrat');
            body.append('font_weight', field.font_weight || '400');
            body.append('font_style', field.font_style || 'normal');
            body.append('color', field.color || '#3d3348');
            body.append('text_align', field.text_align || 'center');
            body.append('sort_order', String(field.sort_order ?? 0));
            if (field.is_required) body.append('is_required', '1');
            if (field.maps_to_couple) body.append('maps_to_couple', '1');
            if (field.show_on_card !== false) body.append('show_on_card', '1');

            try {
                const res = await fetch(field.update_url, {
                    method: 'POST',
                    body,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.saveState = 'error';
                    return;
                }
                const data = await res.json().catch(() => null);
                if (data?.field) {
                    Object.assign(field, {
                        pos_x: data.field.pos_x,
                        pos_y: data.field.pos_y,
                        box_width: data.field.box_width,
                        font_size: data.field.font_size,
                        default_text: data.field.default_text,
                    });
                }
                this.saveState = 'saved';
                this.saveClearTimer = setTimeout(() => { this.saveState = ''; }, 1600);
            } catch (err) {
                this.saveState = 'error';
            }
        },
        addQrSpot() {
            if (this.fields.some(f => f.field_type === 'qr')) {
                alert('This design already has a QR spot.');
                return;
            }
            this.newField.field_key = 'qr_code';
            this.newField.label = 'QR code';
            this.newField.field_type = 'qr';
            this.newField.default_text = '';
            this.newField.pos_x = 35;
            this.newField.pos_y = 75;
            this.newField.box_width = 25;
            this.previewNewField();
            this.addFieldOpen = true;
        },
        previewNewField() {
            if (this.newField.field_type !== 'qr' && !this.newField.label && !this.newField.field_key) return;
            const existing = this.fields.find(f => !f.id);
            const tmp = {
                ...(existing || {}),
                ...this.newField,
                _tmp: existing?._tmp || ('new-' + Date.now()),
                id: null,
                show_on_card: true,
                is_required: false,
                maps_to_couple: false,
                text_align: 'center',
                font_weight: this.newField.font_weight || '400',
                font_style: 'normal',
                sort_order: this.fields.filter(f => f.id).length + 1,
                field_type: this.newField.field_type || 'text',
                pos_x: existing?.pos_x ?? this.newField.pos_x,
                pos_y: existing?.pos_y ?? this.newField.pos_y,
                box_width: existing?.box_width ?? this.newField.box_width,
            };
            this.fields = this.fields.filter(f => f.id);
            this.fields.push(tmp);
            this.activeFieldId = tmp._tmp;
        },
        prepareNewField() {
            this.syncPreviewIntoNewField();
        },
        handleFieldSubmit(e, field) {
            if (!field.id || !field.update_url) {
                e.preventDefault();
                this.syncTmpToAddPanel(field);
                this.addFieldOpen = true;
                return false;
            }
        },
        syncTmpToAddPanel(field) {
            this.newField.field_key = field.field_key || '';
            this.newField.label = field.label || '';
            this.newField.default_text = field.default_text || '';
            this.newField.field_type = field.field_type || 'text';
            this.newField.font_size = field.font_size ?? 18;
            this.newField.font_family = field.font_family || 'Great Vibes';
            this.newField.font_weight = field.font_weight || '400';
            this.newField.color = field.color || '#3d3348';
            this.newField.pos_x = field.pos_x ?? 20;
            this.newField.pos_y = field.pos_y ?? 35;
            this.newField.box_width = field.box_width ?? 60;
        },
        async deleteField(field) {
            if (!field.id) {
                this.fields = this.fields.filter(f => f.id);
                this.openFieldId = null;
                this.activeFieldId = null;
                return;
            }
            if (!field.delete_url || !confirm('Remove this field?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = field.delete_url;
            form.innerHTML = `<input type="hidden" name="_token" value="${this.csrf}"><input type="hidden" name="_method" value="DELETE">`;
            document.body.appendChild(form);
            form.submit();
        },
    }
}
</script>
@endsection
