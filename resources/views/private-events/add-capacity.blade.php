@extends('layouts.app')

@section('title', __('ui.buy_more_tickets'))

@section('content')
@php
    $thumb = $event->invitationDesign?->thumbnail_url
        ?: $event->invitationDesign?->graphic_url
        ?: $event->cover_image;
    $card = 'rounded-[1.75rem] bg-white border border-slate-100 shadow-[0_18px_40px_-28px_rgba(15,26,46,0.35)] overflow-hidden';
    $bar = 'h-1 bg-gradient-to-r from-brand via-[#4a51b8] to-brand/40';
@endphp
<div class="relative overflow-hidden" x-data="topUp({{ (float) $unitPrice }}, {{ (float) $serviceFee }})">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-brand/10 via-brand/5 to-transparent"></div>
    <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-7">
        <a href="{{ route('private-events.show', $event) }}" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-mute hover:text-brand transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('ui.event_details') }}
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
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ __('ui.buy_more_tickets') }}</p>
                        <h1 class="mt-1 text-xl sm:text-2xl font-extrabold tracking-tight text-ink leading-tight">{{ $event->title }}</h1>
                        <p class="text-sm text-mute mt-1.5">{{ __('ui.price_each', ['price' => number_format($unitPrice, 2)]) }}</p>
                    </div>
                </div>
            </div>
        </header>

        @if($errors->any())
            <div class="mb-4 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm font-semibold px-4 py-3.5">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('private-events.capacity.store', $event) }}" class="{{ $card }}">
            <div class="{{ $bar }}"></div>
            <div class="px-5 sm:px-6 py-5 sm:py-6 space-y-5">
                @csrf
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand mb-3">{{ __('ui.quantity') }}</p>
                    <input type="number" name="quantity" x-model.number="qty" min="1" max="{{ $maxTickets }}" value="{{ old('quantity', 10) }}" required
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/15 transition">
                </div>
                <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3.5 text-sm">
                    <div class="flex justify-between"><span class="text-mute">{{ __('ui.subtotal') }}</span><span class="font-bold" x-text="'$' + subtotal.toFixed(2)"></span></div>
                    <div class="flex justify-between mt-1"><span class="text-mute">{{ __('ui.fee') }}</span><span>${{ number_format($serviceFee, 2) }}</span></div>
                    <div class="flex justify-between mt-2 pt-2 border-t border-slate-200 font-extrabold"><span>{{ __('ui.total') }}</span><span class="text-brand" x-text="'$' + total.toFixed(2)"></span></div>
                </div>
                <button class="w-full py-3 rounded-2xl bg-brand text-white font-extrabold text-sm shadow-lg shadow-brand/20 hover:bg-brand-dark transition-colors">{{ __('ui.continue_to_payment') }}</button>
            </div>
        </form>
    </div>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function topUp(unit, fee) {
    return {
        qty: {{ (int) old('quantity', 10) }},
        get subtotal() { return Math.max(0, Number(this.qty) || 0) * unit; },
        get total() { return this.subtotal + fee; },
    }
}
</script>
@endsection
