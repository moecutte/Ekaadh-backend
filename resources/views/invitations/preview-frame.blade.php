<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="{{ rtrim(url('/'), '/') }}/">
    <title>Invitation preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('invitations.partials.invitation-fonts')
    <style>
        html, body { margin: 0; background: transparent; }
        body { padding: {{ !empty($compact) ? '0' : '8px 4px 16px' }}; }
        @if(!empty($compact))
        .invitation-design-card, article.invitation-design-card { max-width: 420px !important; }
        @endif
    </style>
</head>
<body>
    @include('invitations.partials.invite-look', [
        'ticket' => $ticket,
        'qrImage' => $qrImage ?? '',
        'design' => $design,
        'showQr' => $showQr ?? false,
        'compact' => $compact ?? false,
        'withEnvelope' => $withEnvelope ?? true,
        'autoOpen' => $autoOpen ?? true,
        'hideReplay' => $hideReplay ?? false,
        'envelopeGuest' => $envelopeGuest ?? '',
    ])
    <script>
        function postHeight() {
            const h = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
            if (window.parent !== window) {
                window.parent.postMessage({ type: 'ekaadh-invite-preview-height', height: h }, '*');
            }
            if (window.EkaadhPreview && window.EkaadhPreview.postMessage) {
                window.EkaadhPreview.postMessage(String(h));
            }
        }
        window.addEventListener('load', postHeight);
        setTimeout(postHeight, 400);
        setTimeout(postHeight, 1200);
        document.addEventListener('click', () => setTimeout(postHeight, 200));
    </script>
</body>
</html>
