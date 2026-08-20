@extends('layouts.admin')
@section('title', $order->order_number)
@section('heading', $order->order_number)

@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-mute hover:text-ink">← Orders</a>
@endsection

@section('content')
@php
    $sc = ['paid'=>'bg-emerald-50 text-emerald-700 border-emerald-100','pending'=>'bg-amber-50 text-amber-700 border-amber-100','failed'=>'bg-red-50 text-red-600 border-red-100','cancelled'=>'bg-slate-50 text-mute border-slate-100','refunded'=>'bg-violet-50 text-violet-700 border-violet-100'];
    $tickets = $order->items->flatMap->tickets;
    $payment = $order->payment;
@endphp

<div class="flex flex-wrap items-center gap-2 mb-5">
    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $sc[$order->status] ?? 'bg-slate-50 text-mute' }}">{{ $order->status }}</span>
    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-mute">{{ $order->channelLabel() }}</span>
    @if($order->user_id)
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-brand/10 text-brand">Registered user</span>
    @else
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Guest checkout</span>
    @endif
    <span class="text-xs text-mute ml-auto">{{ $order->created_at?->format('D, M j, Y · g:i A') }} · {{ $order->created_at?->diffForHumans() }}</span>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h3 class="text-sm font-bold mb-4">Buyer</h3>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Name</dt>
                    <dd class="font-semibold">{{ $order->buyer_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Phone</dt>
                    <dd class="flex flex-wrap items-center gap-2">
                        <span>{{ $order->buyer_phone ?: '—' }}</span>
                        @if($order->buyer_phone)
                            <a href="tel:{{ $order->buyer_phone }}" class="text-xs font-bold text-brand">Call</a>
                        @endif
                        @if($order->whatsappUrl())
                            <a href="{{ $order->whatsappUrl() }}" target="_blank" class="text-xs font-bold text-emerald-700">WhatsApp</a>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Email</dt>
                    <dd>{{ $order->buyer_email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-mute mb-1">Account</dt>
                    <dd>{{ $order->user?->name ?: 'Guest' }}@if($order->user) <span class="text-mute">· {{ $order->user->phone }}</span>@endif</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h3 class="text-sm font-bold mb-1">Event</h3>
            <p class="font-semibold mt-2">{{ $order->event?->title ?: '—' }}</p>
            <p class="text-sm text-mute mt-0.5">{{ $order->event?->organizer?->business_name }} · {{ $order->event?->venue }} {{ $order->event?->city }}</p>
            @if($order->event?->organizer)
                <a href="{{ route('admin.organizers.show', $order->event->organizer) }}" class="inline-block mt-2 text-xs font-bold text-brand">Organizer profile →</a>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
                <h3 class="text-sm font-bold">Tickets</h3>
                <span class="text-xs text-mute">{{ $tickets->count() }} issued · {{ $order->items->sum('quantity') }} ordered</span>
            </div>
            @if($order->items->isNotEmpty())
                <div class="px-5 py-3 border-b border-slate-50 text-xs text-mute space-y-1">
                    @foreach($order->items as $item)
                        <div>{{ $item->quantity }} × {{ $item->ticketType?->name ?? $item->tickets->first()?->ticket_type_name ?? 'Ticket' }} · ${{ number_format((float) $item->subtotal, 0) }}</div>
                    @endforeach
                </div>
            @endif
            <div class="divide-y divide-slate-50">
                @forelse($tickets as $ticket)
                    <div class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div>
                            <div class="font-mono text-xs font-bold">{{ $ticket->ticket_code }}</div>
                            <div class="text-xs text-mute">{{ $ticket->ticket_type_name }} · {{ $ticket->holder_name }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $ticket->status === 'valid' ? 'bg-emerald-50 text-emerald-700' : ($ticket->status === 'used' ? 'bg-slate-100 text-mute' : 'bg-red-50 text-red-600') }}">{{ $ticket->status }}</span>
                            <a href="{{ route('tickets.show', $ticket->ticket_code) }}" target="_blank" class="text-xs font-bold text-brand">Open</a>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-mute">
                        @if($order->status === 'paid')
                            Paid, but no tickets were issued. Check fulfillment.
                        @else
                            Tickets are created after a successful payment.
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h3 class="text-sm font-bold mb-3">Money</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-mute">Subtotal</span><span class="font-semibold">${{ number_format((float) $order->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-mute">Service fee</span><span class="font-semibold">${{ number_format((float) $order->service_fee, 2) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-2"><span class="font-bold">Customer paid</span><span class="font-black">${{ number_format((float) $order->total_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-mute">Platform commission</span><span class="font-semibold text-brand">${{ number_format((float) $order->commission_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-mute">Organizer net</span><span class="font-bold">${{ number_format($order->organizerNet(), 2) }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold">Payment</h3>
                @if($payment)
                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-xs font-bold text-brand">Payment record →</a>
                @endif
            </div>
            @if($payment)
                @php $mismatch = $payment->mismatchesOrder(); @endphp
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-mute">Gateway</dt>
                        <dd class="font-semibold uppercase">{{ $payment->provider }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-mute">Charge status</dt>
                        <dd class="font-semibold">{{ $payment->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-mute text-xs font-bold mb-1">Transaction ID</dt>
                        <dd class="font-mono text-xs break-all">{{ $payment->transaction_id ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-mute">Wallet</dt>
                        <dd>{{ $payment->phone_number ?: '—' }}</dd>
                    </div>
                    @if($mismatch)
                        <p class="text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-xl px-3 py-2">Charge and order status do not match. Investigate before telling the customer it is paid or failed.</p>
                    @endif
                    @if(in_array($payment->status, ['failed', 'initiated'], true))
                        <div>
                            <dt class="text-xs font-bold text-mute mb-1">What to tell the customer</dt>
                            <dd class="text-sm leading-relaxed">{{ $payment->failureMessage() }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                <p class="text-sm text-mute">No payment was attempted for this order.</p>
            @endif
        </div>
    </div>
</div>
@endsection
