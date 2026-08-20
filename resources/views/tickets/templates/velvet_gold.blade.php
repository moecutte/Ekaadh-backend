@include('tickets.partials.invite-copy')
@include('tickets.partials.invite-motion')
<article class="relative overflow-hidden shadow-2xl mx-auto invitation-design-card invite-motion"
         style="max-width: 420px; background: radial-gradient(80% 50% at 50% 0%, #4a1d28 0%, {{ $design['card_bg'] }} 48%); color: {{ $design['text'] }}; border: 3px double {{ $design['border'] }}; border-radius: 6px;">
    <div class="absolute inset-0 inv-foil opacity-40"></div>
    <div class="absolute inset-2 pointer-events-none" style="border: 1px solid {{ $design['accent'] }}66;"></div>
    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-64 h-64 rounded-full inv-glow" style="background: radial-gradient(circle, {{ $design['accent'] }}55, transparent 68%);"></div>

    @foreach(range(0, 14) as $i)
        <span class="inv-star inv-twinkle" style="left: {{ 8 + ($i * 6.2) }}%; top: {{ 6 + (($i * 17) % 38) }}%; animation-delay: {{ $i * 0.18 }}s; background: {{ $design['accent'] }}; box-shadow: 0 0 8px {{ $design['accent'] }};"></span>
    @endforeach

    <div class="relative px-7 {{ $compact ? 'py-8' : 'py-11' }} text-center">
        <div class="mx-auto mb-4 w-14 h-14 rounded-full flex items-center justify-center text-2xl inv-pulse"
             style="border: 1.5px solid {{ $design['accent'] }}; color: {{ $design['accent'] }}; box-shadow: 0 0 18px {{ $design['accent'] }}55;">❖</div>
        <p class="inv-shimmer-text mb-2" style="font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: .42em; text-transform: uppercase; background-image: linear-gradient(90deg, #8a6a32, #f3e0a6, #c5a059, #f8ecc0, #8a6a32);">
            {{ $badge ?: 'Royal Wedding' }}
        </p>
        @include('tickets.partials.invite-stack', [
            'scriptLine' => 'An Honour',
            'scriptFont' => "'Great Vibes', cursive",
            'nameFont' => "'Cinzel', serif",
            'bodyFont' => "'Cormorant Garamond', serif",
            'guestLabel' => 'Presented to',
        ])
    </div>
</article>
