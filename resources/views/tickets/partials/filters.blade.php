{{-- Booked events filters (customer account + guest lookup). --}}
@php
    $when = $when ?? 'all';
    $filtersActive = $filtersActive ?? false;
    $filterOptions = $filterOptions ?? ['categories' => [], 'cities' => []];
    $preserve = $preserveQuery ?? [];
    $clearUrl = route('tickets.index', $preserve);
    $whenQuery = array_merge($preserve, request()->except(['page', 'when']));
@endphp

<form method="GET" action="{{ route('tickets.index') }}" class="{{ $card }} mb-4">
    <div class="{{ $bar }}"></div>
    <div class="px-4 sm:px-5 py-4 space-y-3">
        @foreach($preserve as $key => $value)
            @if($value !== null && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <input type="hidden" name="when" value="{{ $when }}">

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">{{ __('ui.search') }}</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search_tickets_placeholder') }}"
                       class="w-full rounded-xl bg-page border border-slate-200 px-4 py-2.5 text-sm font-medium outline-none focus:border-brand">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">{{ __('ui.ticket_origin') }}</label>
                <select name="origin" class="w-full rounded-xl bg-page border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none">
                    <option value="">{{ __('ui.all_origins') }}</option>
                    <option value="private" @selected(request('origin') === 'private')>{{ __('ui.ticket_origin_private') }}</option>
                    <option value="invitation" @selected(request('origin') === 'invitation')>{{ __('ui.ticket_origin_invitation') }}</option>
                    <option value="website_paid" @selected(request('origin') === 'website_paid')>{{ __('ui.ticket_origin_website_paid') }}</option>
                    <option value="website_free" @selected(request('origin') === 'website_free')>{{ __('ui.ticket_origin_website_free') }}</option>
                </select>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">{{ __('ui.status') }}</label>
                <select name="status" class="w-full rounded-xl bg-page border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none">
                    <option value="">{{ __('ui.all_status') }}</option>
                    <option value="valid" @selected(request('status') === 'valid')>{{ __('ui.valid') }}</option>
                    <option value="used" @selected(request('status') === 'used')>{{ __('ui.used') }}</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>{{ __('ui.cancelled') }}</option>
                </select>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">{{ __('ui.category') }}</label>
                <select name="category" class="w-full rounded-xl bg-page border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none">
                    <option value="">{{ __('ui.all_categories') }}</option>
                    @foreach($filterOptions['categories'] as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-mute block mb-1">{{ __('ui.city') }}</label>
                <select name="city" class="w-full rounded-xl bg-page border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none">
                    <option value="">{{ __('ui.all_cities') }}</option>
                    @foreach($filterOptions['cities'] as $city)
                        <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <button class="px-4 py-2 rounded-xl bg-brand text-white text-sm font-bold hover:bg-brand-dark">{{ __('ui.apply_filters') }}</button>
            @if($filtersActive)
                <a href="{{ $clearUrl }}" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-mute hover:text-ink">{{ __('ui.clear_filters') }}</a>
            @endif
        </div>
    </div>
</form>

<div class="flex flex-wrap items-center gap-2 mb-4">
    <a href="{{ route('tickets.index', array_merge($whenQuery, ['when' => 'all'])) }}"
       class="px-3.5 py-1.5 rounded-full text-xs font-bold border transition-colors {{ $when === 'all' ? 'bg-brand text-white border-brand' : 'bg-white text-mute border-slate-200 hover:border-brand/40 hover:text-ink' }}">
        {{ __('ui.all') }}
    </a>
    <a href="{{ route('tickets.index', array_merge($whenQuery, ['when' => 'upcoming'])) }}"
       class="px-3.5 py-1.5 rounded-full text-xs font-bold border transition-colors {{ $when === 'upcoming' ? 'bg-brand text-white border-brand' : 'bg-white text-mute border-slate-200 hover:border-brand/40 hover:text-ink' }}">
        {{ __('ui.upcoming') }}
    </a>
    <a href="{{ route('tickets.index', array_merge($whenQuery, ['when' => 'past'])) }}"
       class="px-3.5 py-1.5 rounded-full text-xs font-bold border transition-colors {{ $when === 'past' ? 'bg-brand text-white border-brand' : 'bg-white text-mute border-slate-200 hover:border-brand/40 hover:text-ink' }}">
        {{ __('ui.past') }}
    </a>
    <span class="text-xs text-mute ml-auto">{{ __('ui.tickets_count', ['count' => $tickets->count()]) }}</span>
</div>
