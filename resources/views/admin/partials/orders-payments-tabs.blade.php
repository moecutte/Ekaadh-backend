@php
    $tabs = [
        ['route' => 'admin.orders.index', 'label' => 'Orders', 'match' => 'admin.orders.*'],
        ['route' => 'admin.payments.index', 'label' => 'Payments', 'match' => 'admin.payments.*'],
    ];
@endphp
<div class="flex items-center gap-1 mb-5 bg-white rounded-2xl border border-slate-100 p-1 w-fit">
    @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs($tab['match']) ? 'bg-brand text-white shadow-sm shadow-brand/20' : 'text-mute hover:text-ink hover:bg-slate-50' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
