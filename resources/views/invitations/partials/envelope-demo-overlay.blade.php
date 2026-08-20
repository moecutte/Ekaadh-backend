@include('invitations.partials.envelope-open-styles')
<div class="absolute inset-0 inv-env js-open-envelope rounded-lg"
     x-show="envelopeDemo"
     x-cloak
     @mousedown.prevent.stop
     @click.prevent.stop
     style="z-index: 80; pointer-events: auto; cursor: pointer; --env-paper: {{ $design->header_from ?: '#3d2a32' }}; --env-flap: {{ $design->header_to ?: '#8b5a6b' }}; --env-accent: {{ $design->accent ?: '#c5a059' }};">
    <div class="inv-env-flap" aria-hidden="true">
        <div class="inv-env-flap-face" style="background: linear-gradient(165deg, var(--env-flap), var(--env-paper));"></div>
    </div>
    <div class="inv-env-seal" aria-hidden="true">
        <span class="inv-env-seal-wax"></span>
        <span class="inv-env-seal-icon">{{ $design->bladeCopy()['ornament'] ?? '❖' }}</span>
    </div>
    <div class="inv-env-front" style="background: var(--env-paper);"></div>
    <button type="button" class="inv-env-hit" aria-label="Open invitation">
        <span class="inv-env-cta" x-show="!envelopeOpen">
            <span class="block text-[10px] font-bold tracking-[0.28em] uppercase text-white/90">Invitation</span>
            <span class="block text-lg text-white" style="font-family: 'Great Vibes', cursive;">Tap to open</span>
        </span>
    </button>
</div>
