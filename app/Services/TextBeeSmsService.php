<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TextBeeSmsService
{
    public function enabled(): bool
    {
        return filled(config('textbee.api_key')) && filled(config('textbee.device_id'));
    }

    /**
     * Send an SMS via TextBee gateway.
     *
     * @return array<string, mixed>
     */
    public function send(string $recipient, string $message): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('TextBee SMS is not configured.');
        }

        $url = config('textbee.base_url').'/gateway/devices/'.config('textbee.device_id').'/send-sms';

        $response = Http::timeout((int) config('textbee.timeout', 20))
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => (string) config('textbee.api_key'),
            ])
            ->post($url, [
                'recipients' => [$recipient],
                'message' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('TextBee SMS failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'recipient' => $this->redact($recipient),
            ]);

            throw new RuntimeException('Failed to send SMS via TextBee.');
        }

        $json = $response->json();

        Log::info('TextBee SMS accepted', [
            'id' => data_get($json, 'data._id'),
            'status' => data_get($json, 'data.status'),
            'recipient' => $this->redact($recipient),
        ]);

        return is_array($json) ? $json : [];
    }

    private function redact(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
