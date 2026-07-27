@php
    $name = old('name', $package?->name);
    $description = old('description', $package?->description);
    $commissionRate = old('commission_rate', $package?->commission_rate);
    $billingType = old('billing_type', $package?->billing_type ?? 'free');
    $price = old('price', $package?->price);
    $maxEvents = old('max_events_per_year', $package?->max_events_per_year);
    $maxTickets = old('max_tickets_per_event', $package?->max_tickets_per_event);
    $featuresText = old('features_text', $package ? implode("\n", $package->features ?? []) : '');
    $ctaLabel = old('cta_label', $package?->cta_label);
    $sortOrder = old('sort_order', $package?->sort_order);
    $isActive = old('is_active', $package?->is_active ?? true);
    $isHighlighted = old('is_highlighted', $package?->is_highlighted ?? false);
    $isDefault = old('is_default', $package?->is_default ?? false);
@endphp

<div>
    <label class="text-[11px] font-bold uppercase text-mute block mb-1">Name *</label>
    <input name="name" value="{{ $name }}" required maxlength="80" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand">
    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="text-[11px] font-bold uppercase text-mute block mb-1">Description</label>
    <input name="description" value="{{ $description }}" maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand">
</div>
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Billing type *</label>
        <select name="billing_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            @foreach(['free' => 'Free', 'per_event' => 'Per event', 'monthly' => 'Monthly', 'custom' => 'Custom'] as $value => $label)
                <option value="{{ $value }}" @selected($billingType === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Price ($)</label>
        <input type="number" step="0.01" min="0" name="price" value="{{ $price }}" placeholder="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>
</div>
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Commission %</label>
        <input type="number" step="0.1" min="0" max="100" name="commission_rate" value="{{ $commissionRate }}" placeholder="Platform default" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Sort order</label>
        <input type="number" min="0" name="sort_order" value="{{ $sortOrder }}" placeholder="Auto" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>
</div>
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Max events / year</label>
        <input type="number" min="1" name="max_events_per_year" value="{{ $maxEvents }}" placeholder="Unlimited" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="text-[11px] font-bold uppercase text-mute block mb-1">Max tickets / event</label>
        <input type="number" min="1" name="max_tickets_per_event" value="{{ $maxTickets }}" placeholder="Unlimited" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>
</div>
<div>
    <label class="text-[11px] font-bold uppercase text-mute block mb-1">CTA label</label>
    <input name="cta_label" value="{{ $ctaLabel }}" maxlength="80" placeholder="e.g. Get Started Free" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
</div>
<div>
    <label class="text-[11px] font-bold uppercase text-mute block mb-1">Features (one per line)</label>
    <textarea name="features_text" rows="5" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand resize-y">{{ $featuresText }}</textarea>
</div>
<div class="flex flex-wrap gap-4 text-sm">
    <label class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" @checked($isActive == '1' || $isActive === true || $isActive === 1)>
        <span class="font-semibold">Active</span>
    </label>
    <label class="flex items-center gap-2">
        <input type="hidden" name="is_highlighted" value="0">
        <input type="checkbox" name="is_highlighted" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" @checked($isHighlighted == '1' || $isHighlighted === true || $isHighlighted === 1)>
        <span class="font-semibold">Highlighted</span>
    </label>
    <label class="flex items-center gap-2">
        <input type="hidden" name="is_default" value="0">
        <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" @checked($isDefault == '1' || $isDefault === true || $isDefault === 1)>
        <span class="font-semibold">Default for new organizers</span>
    </label>
</div>
