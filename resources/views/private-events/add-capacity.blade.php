@extends('layouts.app')

@section('title', 'Buy more tickets')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 py-10" x-data="topUp({{ (float) $unitPrice }}, {{ (float) $serviceFee }})">
    <a href="{{ route('private-events.show', $event) }}" class="text-sm font-bold text-mute hover:text-brand">&larr; Back</a>
    <h1 class="text-2xl font-extrabold mt-3 mb-2">Buy more tickets</h1>
    <p class="text-sm text-mute mb-6">{{ $event->title }} · ${{ number_format($unitPrice, 2) }} each</p>

    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm p-4">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('private-events.capacity.store', $event) }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        @csrf
        <div>
            <label class="text-xs font-bold text-mute block mb-1.5">Quantity</label>
            <input type="number" name="quantity" x-model.number="qty" min="1" max="{{ $maxTickets }}" value="{{ old('quantity', 10) }}" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
        </div>
        <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm">
            <div class="flex justify-between"><span class="text-mute">Subtotal</span><span class="font-bold" x-text="'$' + subtotal.toFixed(2)"></span></div>
            <div class="flex justify-between mt-1"><span class="text-mute">Fee</span><span>${{ number_format($serviceFee, 2) }}</span></div>
            <div class="flex justify-between mt-2 pt-2 border-t border-slate-200 font-extrabold"><span>Total</span><span class="text-brand" x-text="'$' + total.toFixed(2)"></span></div>
        </div>
        <button class="w-full py-3 rounded-2xl bg-brand text-white font-extrabold text-sm">Continue to payment</button>
    </form>
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
