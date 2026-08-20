@include('tickets.partials.invite-copy')
@include('tickets.partials.invite-motion')
<article class="relative overflow-hidden shadow-2xl mx-auto invitation-design-card invite-motion"
         style="max-width: 420px; background: linear-gradient(180deg, #f4ead8 0%, {{ $design['card_bg'] }} 38%, #efe4d0 100%); color: {{ $design['text'] }}; border: 1px solid {{ $design['border'] }};">
    <div class="absolute inset-x-0 top-0 h-1.5" style="background: linear-gradient(90deg, transparent, {{ $design['accent'] }}, transparent);"></div>
    <div class="absolute inset-x-0 bottom-0 h-1.5" style="background: linear-gradient(90deg, transparent, {{ $design['accent'] }}, transparent);"></div>
    <div class="absolute inset-0 inv-foil opacity-30"></div>

    <div class="absolute top-5 left-5 w-10 h-10 inv-float" style="border-top: 2px solid {{ $design['accent'] }}; border-left: 2px solid {{ $design['accent'] }};"></div>
    <div class="absolute top-5 right-5 w-10 h-10 inv-float" style="border-top: 2px solid {{ $design['accent'] }}; border-right: 2px solid {{ $design['accent'] }}; animation-delay: -1.5s;"></div>
    <div class="absolute bottom-5 left-5 w-10 h-10" style="border-bottom: 2px solid {{ $design['accent'] }}; border-left: 2px solid {{ $design['accent'] }};"></div>
    <div class="absolute bottom-5 right-5 w-10 h-10" style="border-bottom: 2px solid {{ $design['accent'] }}; border-right: 2px solid {{ $design['accent'] }};"></div>

    <div class="relative px-9 {{ $compact ? 'py-9' : 'py-12' }} text-center">
        <div class="mx-auto mb-4 flex items-center justify-center gap-2 inv-rise">
            <span class="h-px w-8" style="background: {{ $design['accent'] }};"></span>
            <span class="inv-pulse text-lg" style="color: {{ $design['accent'] }};">◆</span>
            <span class="h-px w-8" style="background: {{ $design['accent'] }};"></span>
        </div>
        @include('tickets.partials.invite-stack', [
            'scriptLine' => 'An Evening',
            'scriptFont' => "'Great Vibes', cursive",
            'nameFont' => "'Playfair Display', serif",
            'bodyFont' => "'Cormorant Garamond', serif",
            'guestLabel' => 'Reserved for',
        ])
    </div>
</article>
