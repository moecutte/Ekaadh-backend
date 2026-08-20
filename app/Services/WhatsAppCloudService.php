<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppCloudService
{
    public function configured(): bool
    {
        return filled(config('whatsapp.token'))
            && filled(config('whatsapp.phone_number_id'));
    }

    public function enabled(): bool
    {
        return $this->canSendTicket() || $this->canSendInvite();
    }

    public function canSendTicket(): bool
    {
        return $this->configured() && filled($this->ticketTemplate());
    }

    public function canSendInvite(): bool
    {
        return $this->configured() && filled($this->inviteTemplate());
    }

    /**
     * Send an approved WhatsApp Cloud API template message.
     *
     * @param  list<string>  $bodyParams  Ordered body variables {{1}}, {{2}}, …
     * @return array<string, mixed>
     */
    public function sendTemplate(string $toE164, string $templateName, array $bodyParams): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('WhatsApp Cloud API is not configured.');
        }

        if ($templateName === '') {
            throw new RuntimeException('WhatsApp template name is required.');
        }

        $to = $this->digitsOnly($toE164);
        if ($to === '' || strlen($to) < 8) {
            throw new RuntimeException('Invalid WhatsApp recipient phone.');
        }

        $version = trim((string) config('whatsapp.api_version', 'v21.0'), '/');
        $phoneNumberId = (string) config('whatsapp.phone_number_id');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $components = [];
        if ($bodyParams !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $text) => ['type' => 'text', 'text' => $this->sanitizeParam($text)],
                    $bodyParams
                ),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => (string) config('whatsapp.template_lang', 'en'),
                ],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        $response = Http::timeout((int) config('whatsapp.timeout', 20))
            ->acceptJson()
            ->withToken((string) config('whatsapp.token'))
            ->post($url, $payload);

        if (! $response->successful()) {
            Log::error('WhatsApp Cloud API send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'template' => $templateName,
                'recipient' => $this->redact($to),
            ]);

            throw new RuntimeException('Failed to send WhatsApp message.');
        }

        $json = $response->json();

        Log::info('WhatsApp Cloud API message accepted', [
            'message_id' => data_get($json, 'messages.0.id'),
            'template' => $templateName,
            'recipient' => $this->redact($to),
        ]);

        return is_array($json) ? $json : [];
    }

    public function ticketTemplate(): string
    {
        return (string) config('whatsapp.template_ticket', '');
    }

    public function inviteTemplate(): string
    {
        return (string) config('whatsapp.template_invite', '');
    }

    private function digitsOnly(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Meta rejects some newlines / tabs in template parameters.
     */
    private function sanitizeParam(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        return $text === '' ? '-' : mb_substr($text, 0, 1024);
    }

    private function redact(string $phone): string
    {
        $digits = $this->digitsOnly($phone);
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
