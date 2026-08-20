@include('tickets.partials.invite-copy')
@include('tickets.partials.invite-motion')
<article class="relative overflow-hidden shadow-xl mx-auto invitation-design-card invite-motion"
         style="max-width: 420px; background: linear-gradient(180deg, #f7faf6 0%, {{ $design['card_bg'] }} 40%); color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }}; border-radius: 22px;">
    <svg class="absolute top-3 left-3 opacity-70" width="72" height="72" viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <path class="inv-draw" d="M8 58 C12 30, 30 18, 36 8 C42 18, 60 30, 64 58" stroke="{{ $design['accent'] }}" stroke-width="1.1"/>
        <path class="inv-draw" d="M20 58 C24 40, 36 32, 36 22 C36 32, 48 40, 52 58" stroke="{{ $design['accent'] }}" stroke-width="1"/>
    </svg>
    <svg class="absolute top-3 right-3 opacity-70" width="72" height="72" viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <path class="inv-draw" d="M64 58 C60 30, 42 18, 36 8 C30 18, 12 30, 8 58" stroke="{{ $design['accent'] }}" stroke-width="1.1"/>
    </svg>
    <svg class="absolute bottom-3 left-3 opacity-50" width="64" height="64" viewBox="0 0 64 64" fill="none" aria-hidden="true">
        <path class="inv-draw" d="M8 8 C14 28, 28 40, 32 56 C36 40, 50 28, 56 8" stroke="{{ $design['accent'] }}" stroke-width="1"/>
    </svg>
    <svg class="absolute bottom-3 right-3 opacity-50" width="64" height="64" viewBox="0 0 64 64" fill="none" aria-hidden="true">
        <path class="inv-draw" d="M56 8 C50 28, 36 40, 32 56 C28 40, 14 28, 8 8" stroke="{{ $design['accent'] }}" stroke-width="1"/>
    </svg>

    <div class="absolute left-1/2 top-[42%] -translate-x-1/2 -translate-y-1/2 opacity-20 inv-spin-slow pointer-events-none" aria-hidden="true">
        <div class="w-28 h-28 rounded-full" style="border: 2px solid {{ $design['accent'] }};"></div>
        <div class="absolute inset-3 rounded-full" style="border: 1px dashed {{ $design['accent'] }};"></div>
        <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full" style="background: {{ $design['accent'] }};"></div>
    </div>

    <div class="relative px-8 {{ $compact ? 'py-8' : 'py-11' }}">
        <div class="mx-auto mb-3 inv-pulse text-2xl" style="color: {{ $design['accent'] }};">○</div>
        @include('tickets.partials.invite-stack', [
            'scriptLine' => 'A Promise',
            'nameFont' => "'Cormorant Garamond', serif",
            'guestLabel' => 'Join us in celebrating',
        ])
    </div>
</article>
