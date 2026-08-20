<?php

return [

    'token' => env('WHATSAPP_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'timeout' => (int) env('WHATSAPP_TIMEOUT', 20),

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
    |   Ekaadh: Hi {{1}}, you're invited to {{2}}. {{3}} ticket(s). Open {{4}} to view your invitation.
    |   → guest name, event title, qty, invitation URL
    |
    | Category: Utility (preferred). Leave names empty until templates are approved.
    |
    */
    'template_ticket' => env('WHATSAPP_TEMPLATE_TICKET'),

    'template_invite' => env('WHATSAPP_TEMPLATE_INVITE'),

    'template_lang' => env('WHATSAPP_TEMPLATE_LANG', 'en'),

];
