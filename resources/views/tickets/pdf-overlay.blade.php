@php
    $event = $ticket->event;
    $values = $design['field_values'] ?? [];
    $fields = collect($design['fields'] ?? [])->where('show_on_card', true)->values()->all();
    $pageW = 420;
    $pageH = 595;
    $textFields = collect($fields)->filter(fn ($f) => ($f['field_type'] ?? 'text') !== 'qr');
    $qrFields = collect($fields)->filter(fn ($f) => ($f['field_type'] ?? '') === 'qr');
    $fontFaceCss = \App\Support\InvitationFonts::preparePdfFonts($fields);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invitation {{ $ticket->ticket_code }}</title>
    <style>
        @page { margin: 0; size: {{ $pageW }}pt {{ $pageH }}pt; }
        * { margin: 0; padding: 0; }
        html, body {
            margin: 0;
            padding: 0;
            width: {{ $pageW }}pt;
            height: {{ $pageH }}pt;
            background: {{ $design['card_bg'] ?? '#ffffff' }};
            font-family: DejaVu Sans, sans-serif;
        }
        .canvas {
            position: relative;
            width: {{ $pageW }}pt;
            height: {{ $pageH }}pt;
            overflow: hidden;
        }
        .canvas .bg {
            position: absolute;
            left: 0;
            top: 0;
            width: {{ $pageW }}pt;
            height: {{ $pageH }}pt;
        }
        .field {
            position: absolute;
            line-height: 1.25;
            word-wrap: break-word;
        }
        .qr-box {
            position: absolute;
            text-align: center;
        }
        .qr-box img {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body>
<div class="canvas">
    @if(! empty($graphicDataUri))
        <img class="bg" src="{{ $graphicDataUri }}" alt="">
    @endif

    @foreach($textFields as $field)
        @php
            $key = $field['field_key'];
            $x = (float) ($field['pos_x'] ?? 20);
            $y = (float) ($field['pos_y'] ?? 30);
            $w = (float) ($field['box_width'] ?? 60);
            $raw = $values[$key] ?? $field['default_text'] ?? '';
            if ($key === 'guest_name') {
                $raw = $ticket->holder_name ?: $raw;
            }
            $raw = trim((string) $raw);
            $fontSize = max(8, (float) ($field['font_size'] ?? 18));
            $align = $field['text_align'] ?? 'center';
            $weight = $field['font_weight'] ?? '400';
            $style = $field['font_style'] ?? 'normal';
            $color = $field['color'] ?? ($design['text'] ?? '#111111');
            $family = \App\Support\InvitationFonts::cssFontFamilyForPdf($field['font_family'] ?? null);
        @endphp
        @if($raw !== '')
            <div class="field" style="
                left: {{ $x }}%;
                top: {{ $y }}%;
                width: {{ $w }}%;
                text-align: {{ $align }};
                font-size: {{ $fontSize }}pt;
                font-family: {{ $family }};
                font-weight: {{ $weight }};
                font-style: {{ $style }};
                color: {{ $color }};
            ">{{ $raw }}</div>
        @endif
    @endforeach

    @foreach($qrFields as $field)
        @php
            $x = (float) ($field['pos_x'] ?? 35);
            $y = (float) ($field['pos_y'] ?? 75);
            $w = (float) ($field['box_width'] ?? 25);
            $qrPt = max(48, min(160, ($w / 100) * $pageW));
        @endphp
        <div class="qr-box" style="left: {{ $x }}%; top: {{ $y }}%; width: {{ $w }}%;">
            <img src="{{ $qrDataUri }}" alt="QR" style="width: {{ $qrPt }}pt; height: {{ $qrPt }}pt; margin: 0 auto;">
        </div>
    @endforeach
</div>
</body>
</html>
