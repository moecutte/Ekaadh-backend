@include('tickets.partials.invite-copy')
@include('tickets.partials.invite-motion')
<article class="relative overflow-hidden shadow-2xl mx-auto invitation-design-card invite-motion"
         style="max-width: 420px; background: radial-gradient(120% 80% at 50% 0%, #fff7f4 0%, {{ $design['card_bg'] }} 55%); color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 18px;">
    <div class="absolute inset-3 pointer-events-none rounded-14" style="border: 1px solid {{ $design['border'] }}; border-radius: 12px;"></div>
    <div class="absolute inset-4 pointer-events-none" style="border: 1px solid {{ $design['accent'] }}40; border-radius: 10px;"></div>

    @foreach([8,18,28,40,52,64,76,88] as $i => $left)
        <span class="inv-petal" style="left: {{ $left }}%; animation-duration: {{ 9 + ($i % 5) }}s; animation-delay: {{ $i * 0.85 }}s; color: {{ $i % 2 ? '#d4a5b0' : $design['accent'] }}; font-size: {{ 11 + ($i % 4) * 3 }}px;">{{ $i % 3 === 0 ? '✿' : '❀' }}</span>
    @endforeach

    <svg class="absolute top-6 left-1/2 -translate-x-1/2 inv-float opacity-70" width="86" height="28" viewBox="0 0 86 28" fill="none" aria-hidden="true">
        <path class="inv-draw" d="M4 18 C18 4, 28 4, 43 14 C58 24, 68 24, 82 10" stroke="{{ $design['accent'] }}" stroke-width="1.2"/>
        <circle cx="43" cy="14" r="2.2" fill="{{ $design['accent'] }}"/>
    </svg>

    <div class="relative px-8 {{ $compact ? 'py-8' : 'py-12' }}">
        @include('tickets.partials.invite-stack', ['scriptLine' => "You're Invited", 'guestLabel' => 'With love, celebrating'])
    </div>
</article>
