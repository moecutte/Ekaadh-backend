<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { margin: 0; background: transparent; }
        body { padding: 8px 4px 16px; }
    </style>
</head>
<body>
    @include('tickets.partials.designed-card', [
        'ticket' => $ticket,
        'qrImage' => $qrImage,
        'design' => $design,
        'compact' => false,
        'showQr' => $showQr ?? true,
    ])
    <script>
        function postHeight() {
            const h = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
            if (window.parent !== window) {
                window.parent.postMessage({ type: 'ekaadh-invite-preview-height', height: h }, '*');
            }
        }
        window.addEventListener('load', postHeight);
        setTimeout(postHeight, 400);
        setTimeout(postHeight, 1200);
    </script>
</body>
</html>
