<?php

namespace App\Console\Commands;

use App\Services\WhatsAppCloudService;
use App\Support\Phone;
use Illuminate\Console\Command;
use Throwable;

class WhatsAppTestCommand extends Command
{
    protected $signature = 'whatsapp:test
                            {phone : Recipient mobile (e.g. 0631234567)}
                            {--type=ticket : ticket or invite}';

    protected $description = 'Send an approved WhatsApp Cloud API template to verify credentials';

    public function handle(WhatsAppCloudService $whatsapp): int
    {
        $type = strtolower((string) $this->option('type'));
        if (! in_array($type, ['ticket', 'invite'], true)) {
            $this->error('Type must be ticket or invite.');

            return self::FAILURE;
        }

        $phone = Phone::normalize((string) $this->argument('phone'));
        if ($phone === '') {
            $this->error('Invalid phone number.');

            return self::FAILURE;
        }

        $ready = $type === 'ticket' ? $whatsapp->canSendTicket() : $whatsapp->canSendInvite();
        if (! $ready) {
            $this->error('WhatsApp Cloud API is not ready for this template.');
            $this->line('Set WHATSAPP_TOKEN, WHATSAPP_PHONE_NUMBER_ID, and the matching template name in .env');

            return self::FAILURE;
        }

        $template = $type === 'ticket' ? $whatsapp->ticketTemplate() : $whatsapp->inviteTemplate();
        $params = $type === 'ticket'
            ? ['Test Event', '1', url('/')]
            : ['Guest', 'Test Event', '1', url('/')];

        $this->info("Sending {$type} template `{$template}` to {$phone}…");

        try {
            $result = $whatsapp->sendTemplate($phone, $template, $params);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->line('Check laravel.log for the Graph API response.');

            return self::FAILURE;
        }

        $messageId = data_get($result, 'messages.0.id', 'unknown');
        $this->info("Accepted by Meta. message_id={$messageId}");

        return self::SUCCESS;
    }
}
