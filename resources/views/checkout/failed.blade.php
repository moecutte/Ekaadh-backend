@extends('layouts.app')

@section('title', __('ui.payment_failed'))

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 py-16 text-center">
    <div class="w-24 h-24 mx-auto rounded-full bg-red-100 text-red-500 flex items-center justify-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
    <h1 class="text-3xl font-black mb-3">{{ __('ui.payment_failed') }}</h1>
    <p class="text-mute mb-2">{{ __('ui.payment_failed_for_order', ['order' => $order->order_number]) }}</p>
    <p class="text-mute mb-8">{{ __('ui.check_wallet_or_method') }}</p>
    <a href="{{ route('checkout.show', $order->event->slug) }}" class="inline-block w-full rounded-2xl bg-red-500 text-white font-extrabold py-4 mb-3">{{ __('ui.try_again') }}</a>
    <a href="{{ route('home') }}" class="inline-block text-sm font-bold text-mute">{{ __('ui.back_to_home') }}</a>
</div>
@endsection
