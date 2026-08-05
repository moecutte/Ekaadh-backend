@php
    $compact = $compact ?? false;
    $forPdf = $forPdf ?? false;
    $showQr = $showQr ?? true;
    $event = $ticket->event;
    $values = $design['field_values'] ?? [];
    $fields = collect($design['fields'] ?? [])->where('show_on_card', true)->values();
    $graphic = $graphicSrc ?? ($design['graphic_url'] ?? null);
    $scale = $compact ? 0.85 : 1;
@endphp

@if(! $forPdf)
<link href="{{ \App\Support\InvitationFonts::googleCssUrlForFields($fields->all()) }}" rel="stylesheet">
@endif

<div class="mx-auto" style="max-width: {{ $compact ? '360px' : '420px' }};">
    <article class="relative overflow-hidden shadow-2xl mx-auto"
             style="aspect-ratio: 3 / 4.2; background: {{ $design['card_bg'] ?? '#fff' }}; color: {{ $design['text'] ?? '#111' }};">
        @if($graphic)
            <img src="{{ $graphic }}" alt="" class="absolute inset-0 w-full h-full object-cover" style="z-index: 0;">
        @endif

        @foreach($fields as $field)
            @php
                $key = $field['field_key'];
                $type = $field['field_type'] ?? 'text';
                if ($type === 'qr') {
                    continue;
                }
                $x = $field['pos_x'] ?? 20;
                $y = $field['pos_y'] ?? 30;
                $w = $field['box_width'] ?? 60;
                $raw = $values[$key] ?? $field['default_text'] ?? '';
                if ($key === 'guest_name') {
                    $raw = $ticket->holder_name ?: $raw;
                }
                $raw = trim((string) $raw);
                $family = \App\Support\InvitationFonts::cssFontFamily($field['font_family'] ?? 'Montserrat');
            @endphp
            @if($raw !== '')
            <div class="absolute px-1 leading-tight" style="left: {{ $x }}%; top: {{ $y }}%; width: {{ $w }}%; z-index: 10;
                        text-align: {{ $field['text_align'] ?? 'center' }};
                        font-size: {{ ($field['font_size'] ?? 18) * $scale }}px;
                        font-family: {{ $family }};
                        font-weight: {{ $field['font_weight'] ?? '400' }};
                        font-style: {{ $field['font_style'] ?? 'normal' }};
                        color: {{ $field['color'] ?? ($design['text'] ?? '#111') }};">
                {{ $raw }}
            </div>
            @endif
        @endforeach

        @if($showQr)
            @foreach($fields as $field)
                @if(($field['field_type'] ?? '') === 'qr')
                    @php
                        $x = $field['pos_x'] ?? 35;
                        $y = $field['pos_y'] ?? 75;
                        $w = $field['box_width'] ?? 25;
                        $qrSize = max(48, min(180, (float) $w * 4.2));
                        $metaSize = max(7, (int) round(9 * $scale));
                        $muted = $design['muted'] ?? '#6b6280';
                        $accent = $design['accent'] ?? '#705898';
                        $statusOk = $ticket->status === 'valid';
                    @endphp
                    <div class="absolute flex flex-col items-center text-center bg-white/92 px-1 py-1 rounded-sm"
                         style="left: {{ $x }}%; top: {{ $y }}%; width: {{ $w }}%; z-index: 15; color: {{ $muted }};">
                        <img src="{{ $qrImage }}" alt="QR" style="width: 100%; max-width: {{ $qrSize }}px; aspect-ratio: 1; object-fit: contain;">
                        <p style="margin: 2px 0 0; font-size: {{ $metaSize }}px; line-height: 1.25; width: 100%;
                                  font-family: Montserrat, sans-serif;">
                            Scan at entry · Status:
                            <span style="color: {{ $statusOk ? $accent : '#ef4444' }}; font-weight: 600;">{{ ucfirst($ticket->status) }}</span>
                        </p>
                    </div>
                @endif
            @endforeach

            @php
                $hasQrField = $fields->contains(fn ($f) => ($f['field_type'] ?? '') === 'qr');
                $muted = $design['muted'] ?? '#6b6280';
                $accent = $design['accent'] ?? '#705898';
                $statusOk = $ticket->status === 'valid';
                $metaSize = max(7, (int) round(9 * $scale));
            @endphp
            @if(! $hasQrField && ! empty($qrImage))
                <div class="absolute flex flex-col items-center text-center bg-white/95 px-2 py-2 rounded-md shadow-sm"
                     style="left: 50%; bottom: 3%; transform: translateX(-50%); width: 32%; z-index: 15; color: {{ $muted }};">
                    <img src="{{ $qrImage }}" alt="QR" style="width: 100%; max-width: 120px; aspect-ratio: 1; object-fit: contain;">
                    <p style="margin: 2px 0 0; font-size: {{ $metaSize }}px; line-height: 1.25; width: 100%;
                              font-family: Montserrat, sans-serif;">
                        {{ $ticket->ticket_code }}
                        · <span style="color: {{ $statusOk ? $accent : '#ef4444' }}; font-weight: 600;">{{ ucfirst($ticket->status) }}</span>
                    </p>
                </div>
            @endif
        @endif
    </article>
</div>
