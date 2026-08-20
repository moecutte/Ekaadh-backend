@extends('layouts.admin')
@section('title', 'Payment #'.$payment->id)
@section('heading', $payment->transaction_id ?: 'Payment #'.$payment->id)

@section('actions')
    <a href="{{ route('admin.payments.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-mute hover:text-ink">← Payments</a>
@endsection

@section('content')
@php
    $order = $payment->order;
    $psc = ['success'=>'bg-emerald-50 text-emerald-700 border-emerald-100','initiated'=>'bg-amber-50 text-amber-700 border-amber-100','failed'=>'bg-red-50 text-red-600 border-red-100'];
    $osc = ['paid'=>'bg-emerald-50 text-emerald-700 border-emerald-100','pending'=>'bg-amber-50 text-amber-700 border-amber-100','failed'=>'bg-red-50 text-red-600 border-red-100','cancelled'=>'bg-slate-50 text-mute border-slate-100','refunded'=>'bg-violet-50 text-violet-700 border-violet-100'];
@endphp

<div class="flex flex-wrap items-center gap-2 mb-5">
    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $psc[$payment->status] ?? 'bg-slate-50 text-mute' }}">charge {{ $payment->status }}</span>
    @if($order)
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $osc[$order->status] ?? 'bg-slate-50 text-mute' }}">order {{ $order->status }}</span>
    @endif
    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-mute uppercase">{{ $payment->provider }}</span>
    @if($payment->isStuck())
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-800">Stuck — no result after 15 minutes</span>
    @endif
    @if($payment->mismatchesOrder())
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-red-50 text-red-700">Status mismatch</span>
    @endif
    <span class="text-xs text-mute ml-auto">{{ $payment->created_at?->format('D, M j, Y · g:i A') }} · {{ $payment->created_at?->diffForHumans() }}</span>
</div>

@if($payment->mismatchesOrder())
    <div class="mb-5 rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700 font-semibold">
        The wallet charge and the order are not in the same state. Do not confirm tickets until this is reconciled.
    </div>
@endif

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h3 class="text-sm font-bold mb-4">Charge</h3>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-mute mb-1">Transaction ID</dt>
                    <dd class="font-mono text-xs break-all">{{ $payment->transaction_id ?: 'None returned by the gateway' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Amount</dt>
                    <dd class="font-black text-lg">${{ number_format((float) $payment->amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Wallet phone</dt>
                    <dd>{{ $payment->phone_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Provider</dt>
                    <dd class="font-semibold uppercase">{{ $payment->provider }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Charge status</dt>
                    <dd class="font-semibold">{{ $payment->status }}</dd>
                </div>
            </dl>
            @if(in_array($payment->status, ['failed', 'initiated'], true))
                <div class="mt-5 rounded-xl bg-slate-50 border border-slate-100 p-4">
                    <p class="text-[11px] font-bold uppercase text-mute mb-1">What to tell the customer</p>
                    <p class="text-sm leading-relaxed">{{ $payment->failureMessage() }}</p>
                </div>
            @endif
        </div>

        @if($order)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold">Linked order</h3>
                    <a href="{{ route('admin.orders.show', $order->order_number) }}" class="text-xs font-bold text-brand">Open order →</a>
                </div>
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-bold text-mute mb-1">Order</dt>
                        <dd class="font-mono font-bold">{{ $order->order_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-mute mb-1">Buyer</dt>
                        <dd>{{ $order->buyer_name }} · {{ $order->buyer_phone }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-bold text-mute mb-1">Event</dt>
                        <dd>{{ $order->event?->title }} · {{ $order->event?->organizer?->business_name }}</dd>
                    </div>
                </dl>
            </div>
        @endif
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h3 class="text-sm font-bold mb-3">How to handle this</h3>
            <ul class="text-sm text-mute space-y-2 list-disc pl-4">
                @if($payment->status === 'success' && $order?->status === 'paid')
                    <li>Payment is complete. Tickets should already be on the order.</li>
                @elseif($payment->status === 'success' && $order?->status !== 'paid')
                    <li>The wallet was charged but the order is not marked paid. Escalate — the customer may have paid without tickets.</li>
                @elseif($payment->status === 'failed')
                    <li>No money should have been taken. Ask them to retry with a funded wallet and approve the PIN prompt.</li>
                @elseif($payment->isStuck())
                    <li>The customer may still have a pending prompt on their phone. Ask them to approve or cancel it, then retry.</li>
                @else
                    <li>Wait for the customer to approve the request on their phone.</li>
                @endif
            </ul>
        </div>

        @if(is_array($payment->raw_response) && $payment->raw_response !== [])
            <details class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <summary class="text-sm font-bold cursor-pointer">Gateway payload</summary>
                <pre class="mt-3 text-[11px] leading-relaxed text-mute overflow-x-auto whitespace-pre-wrap">{{ json_encode($payment->raw_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        @endif
    </div>
</div>
@endsection
