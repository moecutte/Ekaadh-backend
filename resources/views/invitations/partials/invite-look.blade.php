{{-- Same invitation card + envelope as /i/{token}. --}}
@php
    $showQr = $showQr ?? false;
    $compact = $compact ?? false;
    $withEnvelope = $withEnvelope ?? true;
    $autoOpen = $autoOpen ?? false;
    $hideReplay = $hideReplay ?? false;
    $envelopeGuest = trim((string) ($envelopeGuest ?? ''));
    $card = [
        'ticket' => $ticket,
        'qrImage' => $qrImage ?? '',
        'design' => $design,
        'showQr' => $showQr,
        'compact' => $compact,
    ];
@endphp

@if($withEnvelope)
    @include('invitations.partials.envelope-open-styles')
    <div class="inv-env-stage mx-auto mb-6"
         style="--env-paper: {{ $design['header_from'] ?? '#3d2a32' }}; --env-flap: {{ $design['header_to'] ?? '#8b5a6b' }}; --env-accent: {{ $design['accent'] ?? '#c5a059' }}; --env-ink: {{ $design['text'] ?? '#fff' }};">
        <div class="inv-env {{ $autoOpen ? 'is-open is-done' : '' }}"
             style="--env-paper: {{ $design['header_from'] ?? '#3d2a32' }}; --env-flap: {{ $design['header_to'] ?? '#8b5a6b' }}; --env-accent: {{ $design['accent'] ?? '#c5a059' }}; --env-ink: {{ $design['text'] ?? '#fff' }};">
            <div class="inv-env-shell" style="background: var(--env-paper);">
                <div class="inv-env-flap" aria-hidden="true">
                    <div class="inv-env-flap-face" style="background: linear-gradient(165deg, var(--env-flap), var(--env-paper));"></div>
                </div>
                <div class="inv-env-seal" aria-hidden="true">
                    <span class="inv-env-seal-wax"></span>
                    <span class="inv-env-seal-icon">{{ $design['ornament'] ?: '❖' }}</span>
                </div>

                <div class="inv-env-letter">
                    <div id="invitation-share-card" class="invitation-share-card">
                        @include('tickets.partials.designed-card', $card)
                    </div>
                </div>

                <div class="inv-env-front" style="background: var(--env-paper);"></div>

                <button type="button" class="inv-env-hit js-open-envelope" aria-label="Open invitation">
                    <span class="inv-env-cta">
                        <span class="block text-[10px] font-bold tracking-[0.28em] uppercase mb-1" style="color: #fff;">{{ $design['badge'] ?: 'Invitation' }}</span>
                        <span class="block text-lg font-semibold" style="font-family: 'Great Vibes', cursive; color: #fff;">
                            @if($envelopeGuest !== '')
                                For {{ $envelopeGuest }}
                            @else
                                Open your invitation
                            @endif
                        </span>
                        <span class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold bg-white/95" style="color: var(--env-paper);">Tap to open</span>
                    </span>
                </button>
            </div>
        </div>
        @unless($hideReplay)
            <p class="inv-env-replay text-center mt-3">
                <button type="button" class="js-replay-envelope text-[11px] font-bold underline-offset-2 hover:underline" style="color: {{ $design['muted'] ?? '#64748b' }};">Replay envelope opening</button>
            </p>
        @endunless
    </div>
@else
    @include('tickets.partials.designed-card', $card)
@endif
