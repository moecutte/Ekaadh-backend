<?php

return [

    'token' => env('WHATSAPP_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'timeout' => (int) env('WHATSAPP_TIMEOUT', 20),

    'cafile' => env('WHATSAPP_CAFILE', env('WAAFIPAY_CAFILE')),

    /*
    |--------------------------------------------------------------------------
    | Message templates (Meta Business Manager)
    |--------------------------------------------------------------------------
    |
    | Bodies must match parameter order used in TicketDeliveryService:
    |
    | Paid ticket orders send SMS only (no WhatsApp). Ticket template is
    | optional and used only by `php artisan whatsapp:test --type=ticket`.
    |
    | Ticket (WHATSAPP_TEMPLATE_TICKET):
    |   Your Ekaadh tickets for {{1}} ({{2}}) are ready. Open {{3}} to view them.
    |   → event title, ticket count, ticket URL
    |
    | Invite (WHATSAPP_TEMPLATE_INVITE):
    |   Ekaadh: Salaam {{1}}, waxaa lagugu casuumay munaasabada {{2}}. {{3}} tigidh. Fur casuumaddaada: {{4}}.
    |   → guest name, event title, qty, invitation URL
    |
    | Category: Utility (preferred). Leave names empty until templates are approved.
    | Approve a Somali (so) language version in Meta; set WHATSAPP_TEMPLATE_LANG=so.
    |
    */
    'template_ticket' => env('WHATSAPP_TEMPLATE_TICKET'),

    'template_invite' => env('WHATSAPP_TEMPLATE_INVITE'),

    'template_lang' => env('WHATSAPP_TEMPLATE_LANG', 'so'),

];
