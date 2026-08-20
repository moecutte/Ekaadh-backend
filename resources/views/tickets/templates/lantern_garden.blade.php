@include('tickets.partials.invite-copy')
@include('tickets.partials.invite-motion')
<article class="relative overflow-hidden shadow-xl mx-auto invitation-design-card invite-motion"
         style="max-width: 420px; background: linear-gradient(180deg, #3b2416 0%, #6b3f24 22%, {{ $design['card_bg'] }} 22.1%); color: {{ $design['text'] }}; border-radius: 24px;">
    <div class="absolute inset-x-0 top-0 h-[22%] overflow-hidden pointer-events-none">
        @foreach([12, 28, 46, 62, 78] as $i => $left)
            <span class="inv-lantern" style="left: {{ $left }}%; animation-duration: {{ 10 + $i * 1.4 }}s; animation-delay: {{ $i * 0.9 }}s;">
                <span class="block w-5 h-7 rounded-sm" style="background: linear-gradient(180deg, #fde68a, #d97706); box-shadow: 0 0 16px #fbbf24aa;"></span>
            </span>
        @endforeach
        <p class="relative text-center pt-6 text-[10px] tracking-[0.32em] uppercase font-bold text-amber-100/90">{{ $badge ?: 'Ceremony' }}</p>
        <p class="relative text-center mt-1 text-2xl font-semibold text-amber-50" style="font-family: 'Playfair Display', serif;">{{ $inviteLine ?: 'You are invited' }}</p>
    </div>

    <svg class="absolute left-3 top-[26%] opacity-50" width="36" height="120" viewBox="0 0 36 120" fill="none" aria-hidden="true">
        <path class="inv-draw" d="M18 4 L32 18 L18 32 L4 18 Z M18 40 L32 54 L18 68 L4 54 Z M18 76 L32 90 L18 104 L4 90 Z" stroke="{{ $design['accent'] }}" stroke-width="1"/>
    </svg>
    <svg class="absolute right-3 top-[26%] opacity-50" width="36" height="120" viewBox="0 0 36 120" fill="none" aria-hidden="true">
        <path class="inv-draw" d="M18 4 L32 18 L18 32 L4 18 Z M18 40 L32 54 L18 68 L4 54 Z M18 76 L32 90 L18 104 L4 90 Z" stroke="{{ $design['accent'] }}" stroke-width="1"/>
    </svg>

    <div class="relative px-8 {{ $compact ? 'pt-28 pb-8' : 'pt-32 pb-10' }}">
        @include('tickets.partials.invite-stack', [
            'scriptLine' => 'Join the Light',
            'inviteLine' => '',
            'nameFont' => "'Playfair Display', serif",
            'bodyFont' => "'Cormorant Garamond', serif",
            'guestLabel' => 'Celebrating with',
        ])
    </div>
</article>
