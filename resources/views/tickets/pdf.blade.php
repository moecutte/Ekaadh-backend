<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $ticket->ticket_code }}</title>
@php
    $event = $ticket->event;
    $design = \App\Support\TicketDesigns::resolveForEvent($event);
    $isPrivateInvite = (bool) ($event?->is_private);
    $metaParts = array_filter([
        $event?->event_date?->format('M j, Y'),
        $event?->event_time ? date('g:i A', strtotime($event->event_time)) : null,
        $event?->venue,
    ]);
    $meta = implode(' · ', $metaParts);
    $statusOk = $ticket->status === 'valid';
    $pageBg = $design['header_from'];
    $cardBg = $design['card_bg'];
    $textColor = $design['text'];
    $mutedColor = $design['muted'];
    $accentColor = $design['accent'];
    $borderColor = $design['border'];
    $radius = in_array($design['id'], ['wedding', 'royal_gold', 'formal'], true) ? '8px' : '22px';
@endphp
    <style>
        @page { margin: 28px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            background: {{ $pageBg }};
            color: {{ $textColor }};
        }
        .page {
            background: {{ $pageBg }};
            padding: 18px 14px 20px;
        }
        .header {
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 14px;
        }
        .header .brand { float: left; }
        .header .title { float: right; }
        .clear { clear: both; }
        .card {
            background: {{ $cardBg }};
            border-radius: {{ $radius }};
            overflow: hidden;
            border: 2px solid {{ $borderColor }};
        }
        .invite-top {
            text-align: center;
            padding: 22px 20px 10px;
            color: {{ $textColor }};
        }
        .invite-kicker {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: {{ $mutedColor }};
            margin: 0 0 8px;
            font-weight: bold;
        }
        .invite-script {
            font-size: 20px;
            color: {{ $accentColor }};
            margin: 0 0 8px;
            font-style: italic;
            font-weight: bold;
        }
        .invite-request {
            font-size: 11px;
            color: {{ $mutedColor }};
            margin: 0 0 12px;
            font-style: italic;
        }
        .event-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 8px;
            line-height: 1.3;
        }
        .meta-line {
            font-size: 11px;
            color: {{ $mutedColor }};
            margin: 0;
        }
        .cover {
            height: 150px;
            background: {{ $design['header_to'] }};
            position: relative;
            overflow: hidden;
        }
        .cover img.bg {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
        .cover-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            top: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.72), rgba(0,0,0,0.12));
        }
        .cover-text {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 14px;
            color: #ffffff;
        }
        .cover-text .event {
            font-size: 16px;
            font-weight: bold;
            line-height: 1.25;
            margin: 0 0 4px;
        }
        .cover-text .meta {
            font-size: 10px;
            color: rgba(255,255,255,0.78);
            margin: 0;
        }
        .body {
            padding: 18px 20px 8px;
        }
        .row {
            width: 100%;
            margin-bottom: 12px;
        }
        .col-left { float: left; width: 58%; }
        .col-right { float: right; width: 40%; text-align: right; }
        .label {
            font-size: 9px;
            color: {{ $mutedColor }};
            font-weight: bold;
            margin: 0 0 3px;
        }
        .value {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            color: {{ $textColor }};
        }
        .code {
            text-align: center;
            color: {{ $accentColor }};
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 4px 0 12px;
        }
        .tear {
            margin: 4px 8px 10px;
            height: 22px;
        }
        .notch {
            width: 20px;
            height: 20px;
            background: {{ $pageBg }};
            border-radius: 50%;
            float: left;
        }
        .notch.right { float: right; }
        .dash {
            margin: 9px 28px 0;
            border-top: 2px dashed {{ $borderColor }};
            height: 0;
        }
        .qr-wrap {
            text-align: center;
            padding: 8px 16px 22px;
        }
        .qr-wrap img {
            width: 200px;
            height: 200px;
            border: 1px solid {{ $borderColor }};
            border-radius: 12px;
            padding: 8px;
            background: #fff;
        }
        .status {
            font-size: 10px;
            color: {{ $mutedColor }};
            font-weight: bold;
            margin: 10px 0 0;
        }
        .status .ok { color: {{ $accentColor }}; }
        .status .bad { color: #ef4444; }
        .order {
            font-size: 9px;
            color: {{ $mutedColor }};
            margin: 6px 0 0;
        }
        .footer {
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 9px;
            margin-top: 14px;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="brand">ekaadh</div>
        <div class="title">{{ $isPrivateInvite ? 'Your Invitation' : 'Your Ticket' }}</div>
        <div class="clear"></div>
    </div>

    <div class="card">
        @if($isPrivateInvite)
            <div class="invite-top">
                <p class="invite-kicker">{{ $design['invite_line'] ?: $design['badge'] }}</p>
                <p class="invite-script">You're Invited</p>
                @if(!empty($design['request_line']))
                    <p class="invite-request">{{ $design['request_line'] }}</p>
                @endif
                <p class="event-title">{{ $event?->title ?? 'Event' }}</p>
                @if($meta !== '')
                    <p class="meta-line">{{ $meta }}</p>
                @endif
            </div>
        @else
            <div class="cover">
                @if(! empty($coverDataUri))
                    <img class="bg" src="{{ $coverDataUri }}" alt="">
                @endif
                <div class="cover-overlay"></div>
                <div class="cover-text">
                    <p class="event">{{ $event?->title ?? 'Event' }}</p>
                    @if($meta !== '')
                        <p class="meta">{{ $meta }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="body">
            <div class="row">
                <div class="col-left">
                    <p class="label">{{ $isPrivateInvite ? 'Guest' : 'Ticket Holder' }}</p>
                    <p class="value">{{ $ticket->holder_name ?: '—' }}</p>
                </div>
                <div class="col-right">
                    <p class="label">{{ $isPrivateInvite ? 'Admission' : 'Type' }}</p>
                    <p class="value">{{ $ticket->ticket_type_name }}</p>
                </div>
                <div class="clear"></div>
            </div>

            <div class="code">{{ $ticket->ticket_code }}{{ $isPrivateInvite ? '' : ' · ADMIT ONE' }}</div>
        </div>

        <div class="tear">
            <div class="notch"></div>
            <div class="notch right"></div>
            <div class="dash"></div>
            <div class="clear"></div>
        </div>

        <div class="qr-wrap">
            <img src="{{ $qrDataUri }}" alt="Ticket QR">
            <p class="status">
                {{ $design['footer_line'] ?? 'Scan at entry' }} · Status:
                <span class="{{ $statusOk ? 'ok' : 'bad' }}">{{ ucfirst($ticket->status) }}</span>
            </p>
            @if($ticket->orderItem?->order?->order_number)
                <p class="order">Order {{ $ticket->orderItem->order->order_number }}</p>
            @endif
        </div>
    </div>

    <div class="footer">
        {{ $isPrivateInvite
            ? ($design['footer_line'] ?? 'Present this invitation at the venue entrance.')
            : 'Present this ticket at the venue entrance. Do not share your QR with others.' }}
    </div>
</div>
</body>
</html>
