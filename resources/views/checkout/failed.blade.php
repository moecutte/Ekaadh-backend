@extends('layouts.app')

@section('title', 'Payment Failed')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 py-16 text-center">
    <div class="w-24 h-24 mx-auto rounded-full bg-red-100 text-red-500 flex items-center justify-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
    <h1 class="text-3xl font-black mb-3">Payment Failed</h1>
    <p class="text-mute mb-2">We could not process your payment for order <span class="font-bold text-ink">{{ $order->order_number }}</span>.</p>
    <p class="text-mute mb-8">Check your mobile wallet balance or try a different method.</p>
    <a href="{{ route('checkout.show', $order->event->slug) }}" class="inline-block w-full rounded-2xl bg-red-500 text-white font-extrabold py-4 mb-3">Try again</a>
    <a href="{{ route('home') }}" class="inline-block text-sm font-bold text-mute">Back to home</a>
</div>
@endsection
