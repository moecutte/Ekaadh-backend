@extends('layouts.app')

@section('title', __('ui.payment_pending'))

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 py-16 text-center">
    <div class="w-24 h-24 mx-auto rounded-full bg-brand/10 text-brand flex items-center justify-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h1 class="text-3xl font-black mb-3">{{ __('ui.payment_pending') }}</h1>
    <p class="text-mute mb-2">{{ __('ui.payment_pending_for_order', ['order' => $order->order_number]) }}</p>
    <p class="text-mute mb-8">{{ __('ui.payment_pending_hint') }}</p>
    <a href="{{ route('tickets.index') }}" class="inline-block w-full rounded-2xl bg-brand text-white font-extrabold py-4 mb-3">{{ __('ui.booked_events') }}</a>
    <a href="{{ route('home') }}" class="inline-block text-sm font-bold text-mute">{{ __('ui.back_to_home') }}</a>
</div>
<script>setTimeout(function () { window.location.reload(); }, 8000);</script>
@endsection
