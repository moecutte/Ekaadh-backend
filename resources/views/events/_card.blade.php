@php
    $price = $event->ticketTypes->min('price');
    $catBadge = [
        'Music' => 'bg-purple-100 text-purple-700',
        'Concerts' => 'bg-purple-100 text-purple-700',
        'Sports' => 'bg-green-100 text-green-700',
        'Comedy' => 'bg-pink-100 text-pink-700',
        'Tech' => 'bg-sky-100 text-sky-700',
        'Conferences' => 'bg-sky-100 text-sky-700',
        'Food' => 'bg-orange-100 text-orange-700',
        'Business' => 'bg-indigo-100 text-indigo-700',
        'Culture' => 'bg-amber-100 text-amber-700',
        'Education' => 'bg-teal-100 text-teal-700',
    ];
@endphp
<a href="{{ route('events.show', $event->slug) }}" class="group block bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-200">
    <div class="relative h-44 overflow-hidden bg-slate-200">
        @if($event->cover_image)
            <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        @endif
        @if($event->category)
            <div class="absolute top-3 left-3">
                <span class="inline-flex text-[11px] font-bold px-2.5 py-1 rounded-full {{ $catBadge[$event->category] ?? 'bg-white text-ink' }}">{{ $event->category }}</span>
            </div>
        @endif
        @if($event->is_featured)
            <div class="absolute top-3 right-3 bg-brand text-white text-xs font-bold px-2.5 py-1 rounded-full">{{ __('ui.featured') }}</div>
        @endif
    </div>
    <div class="p-4">
        <h3 class="font-bold text-ink text-sm leading-snug mb-2.5 line-clamp-2 group-hover:text-brand transition">{{ $event->title }}</h3>
        <div class="space-y-1 mb-3">
            <div class="flex items-center gap-1.5 text-mute text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>{{ $event->event_date?->format('M j, Y') }}@if($event->event_time) · {{ date('g:i A', strtotime($event->event_time)) }}@endif</span>
            </div>
            <div class="flex items-center gap-1.5 text-mute text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="line-clamp-1">{{ $event->venue }}{{ $event->city ? ', '.$event->city : '' }}</span>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <p class="text-sm">
                <span class="text-mute text-xs">{{ __('ui.from') }} </span>
                @if($price !== null)
                    <span class="font-extrabold text-ink">${{ number_format((float) $price, 0) }}</span>
                @else
                    <span class="font-extrabold text-ink">—</span>
                @endif
            </p>
            <span class="bg-brand group-hover:bg-brand-dark text-white text-xs font-bold px-3.5 py-1.5 rounded-lg transition-colors">{{ __('ui.get_tickets') }}</span>
        </div>
    </div>
</a>
