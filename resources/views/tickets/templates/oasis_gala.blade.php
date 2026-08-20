@include('tickets.partials.invite-copy')
@include('tickets.partials.invite-motion')
<article class="relative overflow-hidden shadow-2xl mx-auto invitation-design-card invite-motion"
         style="max-width: 420px; background: radial-gradient(90% 60% at 50% 100%, #134e4a 0%, {{ $design['card_bg'] }} 55%); color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 18px;">
    <svg class="absolute inset-0 w-full h-full opacity-25 inv-spin-slow pointer-events-none" viewBox="0 0 200 280" fill="none" aria-hidden="true">
        <polygon points="100,20 180,100 100,180 20,100" stroke="{{ $design['accent'] }}" stroke-width="0.6"/>
        <polygon points="100,48 152,100 100,152 48,100" stroke="{{ $design['accent'] }}" stroke-width="0.6"/>
        <circle cx="100" cy="100" r="28" stroke="{{ $design['accent'] }}" stroke-width="0.5"/>
        <circle cx="100" cy="100" r="8" fill="{{ $design['accent'] }}" fill-opacity=".35"/>
    </svg>
    <div class="absolute bottom-[-20%] left-1/2 -translate-x-1/2 w-72 h-72 rounded-full inv-glow"
         style="background: radial-gradient(circle, {{ $design['accent'] }}55, transparent 70%);"></div>
    <div class="absolute inset-0 inv-foil opacity-20"></div>

    <div class="relative px-7 {{ $compact ? 'py-8' : 'py-11' }} text-center">
        <p class="inv-shimmer-text mb-3" style="font-family: 'Cinzel', serif; letter-spacing: .4em; font-size: 10px; text-transform: uppercase; background-image: linear-gradient(90deg, #b7a056, #f4e7b0, #d4af37, #b7a056);">
            {{ $badge ?: 'Evening Ceremony' }}
        </p>
        @include('tickets.partials.invite-stack', [
            'scriptLine' => 'An Oasis',
            'inviteLine' => '',
            'scriptFont' => "'Great Vibes', cursive",
            'nameFont' => "'Cinzel', serif",
            'bodyFont' => "'Cormorant Garamond', serif",
            'guestLabel' => 'Honoured guest',
        ])
    </div>
</article>
