@include('tickets.partials.invite-copy')
@include('tickets.partials.invite-motion')
<article class="relative overflow-hidden shadow-2xl mx-auto invitation-design-card invite-motion"
         style="max-width: 420px; background: radial-gradient(90% 70% at 50% 0%, #1b2a58 0%, {{ $design['card_bg'] }} 58%); color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 20px;">
    <div class="absolute -top-8 left-[-10%] w-[70%] h-40 rounded-full inv-glow pointer-events-none"
         style="background: radial-gradient(circle, {{ $design['accent'] }}44, transparent 70%); animation-name: inv-aurora;"></div>
    <div class="absolute top-8 right-[-8%] w-40 h-40 rounded-full inv-glow pointer-events-none"
         style="background: radial-gradient(circle, #7dd3fc33, transparent 68%); animation-delay: -2s;"></div>
    <div class="absolute top-7 left-1/2 -translate-x-1/2 w-16 h-16 rounded-full inv-float"
         style="background: radial-gradient(circle at 35% 35%, #fff7d6, #e8d48b 42%, #b45309 100%); box-shadow: 0 0 24px #fde68a88;"></div>

    @foreach(range(0, 22) as $i)
        <span class="inv-star inv-twinkle" style="left: {{ 4 + ($i * 4.2) }}%; top: {{ 4 + (($i * 13) % 52) }}%; animation-delay: {{ $i * 0.14 }}s; width: {{ 2 + ($i % 3) }}px; height: {{ 2 + ($i % 3) }}px;"></span>
    @endforeach

    <div class="relative px-7 {{ $compact ? 'py-10' : 'pt-24 pb-11' }} text-center">
        <p class="inv-rise text-[10px] tracking-[0.4em] uppercase mb-3" style="color: {{ $design['muted'] }};">{{ $badge ?: 'Engagement' }}</p>
        @include('tickets.partials.invite-stack', [
            'scriptLine' => 'Under the Stars',
            'inviteLine' => '',
            'scriptFont' => "'Tangerine', cursive",
            'nameFont' => "'Playfair Display', serif",
            'bodyFont' => "'Cormorant Garamond', serif",
            'guestLabel' => 'Written in the stars for',
        ])
    </div>
</article>
