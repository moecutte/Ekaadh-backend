@php
    $tier = $tier ?? 'standard';
    $isPremiumTier = $tier === 'premium';
@endphp
<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
    @foreach($pickerPreviews as $designId => $preview)
        @php
            $catalog = $preview['design'];
            $slug = $catalog['id'];
            $catId = (int) ($catalog['private_event_category_id'] ?? 0);
            $rowTier = $catalog['category'] ?? 'standard';
        @endphp
        @if($rowTier === $tier)
            <button type="button"
                    x-show="Number(categoryId) === {{ $catId }}"
                    x-cloak
                    class="relative block rounded-2xl border-2 overflow-hidden transition-all duration-200 bg-white text-left"
                    @click="selectDesign(@js($slug), {{ (int) $designId }})"
                    :class="design === @js($slug)
                        ? '{{ $isPremiumTier ? 'border-amber-500 shadow-lg shadow-amber-200/60 ring-2 ring-amber-200 scale-[1.02]' : 'border-brand shadow-lg shadow-brand/15 ring-2 ring-brand/20 scale-[1.02]' }}'
                        : '{{ $isPremiumTier ? 'border-slate-100 hover:border-amber-200 hover:shadow-md' : 'border-slate-100 hover:border-slate-200 hover:shadow-md' }}'">
                <div class="invite-picker-tile">
                    <div class="invite-picker-tile-scale">
                        @include('tickets.partials.designed-card', [
                            'ticket' => $preview['ticket'],
                            'qrImage' => '',
                            'design' => $catalog,
                            'showQr' => false,
                            'compact' => true,
                        ])
                    </div>
                </div>
                @if($isPremiumTier)
                    <p class="px-2 py-1.5 text-[10px] font-bold text-center bg-amber-50 text-amber-800">+${{ number_format($premiumSurcharge, 2) }}</p>
                @endif
                <span x-show="design === @js($slug)"
                      class="absolute top-2 right-2 w-6 h-6 rounded-full {{ $isPremiumTier ? 'bg-amber-500' : 'bg-brand' }} text-white flex items-center justify-center text-xs font-bold shadow-md">✓</span>
            </button>
        @endif
    @endforeach
</div>
