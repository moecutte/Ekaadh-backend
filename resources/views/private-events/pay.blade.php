@extends('layouts.app')

@section('title', __('ui.pay_for_tickets').' — '.$event->title)

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 py-10">
    <a href="{{ route('private-events.index') }}" class="text-sm font-bold text-mute hover:text-brand">&larr; {{ __('ui.my_private_events') }}</a>
    <h1 class="text-2xl font-extrabold mt-3 mb-1">{{ __('ui.pay_for_tickets') }}</h1>
    <p class="text-sm text-mute mb-6">{{ $event->title }}</p>

    @if(session('success'))
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm p-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-5 space-y-2 text-sm">
        @foreach($order->items as $item)
            <div class="flex justify-between gap-3">
                <span>{{ $item->quantity }} × {{ $item->ticketType?->name ?? __('ui.ticket') }} @ ${{ number_format($item->unit_price, 2) }}</span>
                <span class="font-bold">${{ number_format($item->subtotal, 2) }}</span>
            </div>
        @endforeach
        <div class="flex justify-between text-mute pt-2 border-t border-slate-50">
            <span>{{ __('ui.service_fee') }}</span>
            <span>${{ number_format($order->service_fee, 2) }}</span>
        </div>
        <div class="flex justify-between font-extrabold text-base pt-1">
            <span>{{ __('ui.total') }}</span>
            <span class="text-brand">${{ number_format($order->total_amount, 2) }}</span>
        </div>
        <p class="text-[11px] text-mute pt-2">{{ __('ui.order_label', ['number' => $order->order_number]) }}</p>
    </div>

    <form method="POST" action="{{ route('private-events.pay.store', $event) }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        @csrf
        <div>
            <label class="text-xs font-bold text-mute block mb-2">{{ __('ui.payment_method') }}</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-3 cursor-pointer has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                    <input type="radio" name="payment_method" value="zaad" checked class="text-brand">
                    <span class="text-sm font-bold">Zaad</span>
                </label>
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-3 cursor-pointer has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                    <input type="radio" name="payment_method" value="edahab" class="text-brand">
                    <span class="text-sm font-bold">eDahab</span>
                </label>
            </div>
        </div>
        @if($allowForceFail)
            <label class="flex items-center gap-2 text-xs text-mute">
                <input type="checkbox" name="force_fail" value="1"> {{ __('ui.simulate_failed_payment') }}
            </label>
        @endif
        <button type="submit" class="w-full py-3.5 rounded-2xl bg-brand text-white font-extrabold text-sm hover:bg-brand-dark">{{ __('ui.pay_amount', ['amount' => number_format($order->total_amount, 2)]) }}</button>
    </form>
</div>
@endsection
