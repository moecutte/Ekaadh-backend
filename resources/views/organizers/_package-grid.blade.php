@php
    /** @var \Illuminate\Support\Collection $plans */
@endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
    @forelse($plans as $plan)
        <div class="rounded-2xl border p-6 relative {{ $plan['highlight'] ? 'border-brand bg-ink shadow-2xl scale-[1.02]' : 'border-slate-100 bg-white' }}">
            @if($plan['highlight'])
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand text-white text-xs font-extrabold px-4 py-1 rounded-full">
                    {{ __('ui.most_popular') }}
                </div>
            @endif
            <h3 class="font-extrabold text-xl mb-1 {{ $plan['highlight'] ? 'text-white' : 'text-ink' }}">{{ $plan['name'] }}</h3>
            <div class="flex items-baseline gap-1 mb-1">
                <span class="text-4xl font-extrabold {{ $plan['highlight'] ? 'text-brand' : 'text-ink' }}">{{ $plan['price'] }}</span>
                @if($plan['period'])
                    <span class="text-sm {{ $plan['highlight'] ? 'text-slate-400' : 'text-mute' }}">/ {{ $plan['period'] }}</span>
                @endif
            </div>
            @if(! empty($plan['meta']))
                <p class="text-xs font-bold mb-2 {{ $plan['highlight'] ? 'text-brand' : 'text-brand' }}">{{ $plan['meta'] }}</p>
            @endif
            <p class="text-sm mb-6 {{ $plan['highlight'] ? 'text-slate-400' : 'text-mute' }}">{{ $plan['desc'] }}</p>
            <ul class="space-y-2.5 mb-7">
                @foreach($plan['features'] as $f)
                    <li class="flex items-start gap-2.5 text-sm">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 {{ $plan['highlight'] ? 'bg-brand/20' : 'bg-brand/10' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span class="{{ $plan['highlight'] ? 'text-slate-300' : 'text-ink' }}">{{ $f }}</span>
                    </li>
                @endforeach
            </ul>
            <a
                href="{{ ($plan['billing_type'] ?? '') === 'custom' ? 'mailto:sales@ekaadh.com' : $registerUrl }}"
                class="block w-full text-center font-bold py-3 rounded-xl text-sm transition-colors {{ $plan['highlight'] ? 'bg-brand hover:bg-brand-dark text-white' : 'border border-slate-200 text-ink hover:bg-slate-50' }}"
            >
                {{ auth()->check() && ($plan['billing_type'] ?? '') !== 'custom' ? __('ui.go_to_dashboard') : $plan['cta'] }}
            </a>
        </div>
    @empty
        <div class="md:col-span-3 text-center text-mute text-sm py-8">{{ $emptyMessage ?? __('ui.org_pricing_soon') }}</div>
    @endforelse
</div>
