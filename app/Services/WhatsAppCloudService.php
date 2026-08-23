<?php

namespace App\Services;

use App\Support\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppCloudService
{
    public function configured(): bool
    {
        return filled($this->token())
            && filled($this->phoneNumberId());
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

        $to = Phone::internationalDigits($toE164);
        if ($to === '') {
            $to = preg_replace('/\D+/', '', $toE164) ?? '';
        }
        if ($to === '' || strlen($to) < 8) {
            throw new RuntimeException('Invalid WhatsApp recipient phone.');
        }

        $languages = $this->languageCandidates();
        $lastError = 'Failed to send WhatsApp message.';
        $lastStatus = 0;

        foreach ($languages as $index => $language) {
            $response = $this->postTemplate($to, $templateName, $bodyParams, $language);

            if ($response->successful()) {
                $json = $response->json();

                Log::info('WhatsApp Cloud API message accepted', [
                    'message_id' => data_get($json, 'messages.0.id'),
                    'template' => $templateName,
                    'language' => $language,
                    'recipient' => $this->redact($to),
                ]);

                return is_array($json) ? $json : [];
            }

            $lastStatus = $response->status();
            $lastError = $this->graphError($response) ?: $lastError;
            $retryable = $this->shouldRetryLanguage($response) && $index < count($languages) - 1;

            Log::error('WhatsApp Cloud API send failed', [
                'status' => $lastStatus,
                'error' => $lastError,
                'body' => $response->body(),
                'template' => $templateName,
                'language' => $language,
                'retrying' => $retryable,
                'recipient' => $this->redact($to),
            ]);

            if (! $retryable) {
                break;
            }
        }

        throw new RuntimeException($lastError);
    }

    /**
     * @param  list<string>  $bodyParams
     */
    private function postTemplate(string $to, string $templateName, array $bodyParams, string $language): Response
    {
        $version = trim((string) config('whatsapp.api_version', 'v21.0'), '/');
        $phoneNumberId = $this->phoneNumberId();
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
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language,
                    'policy' => 'deterministic',
                ],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        $request = Http::timeout((int) config('whatsapp.timeout', 20))
            ->acceptJson()
            ->asJson()
            ->withToken($this->token());

        $cafile = (string) config('whatsapp.cafile', '');
        if ($cafile !== '' && is_file($cafile)) {
            $request = $request->withOptions(['verify' => $cafile]);
        }

        try {
            return $request->post($url, $payload);
        } catch (ConnectionException $e) {
            $ssl = str_contains(strtolower($e->getMessage()), 'ssl')
                || str_contains($e->getMessage(), 'certificate')
                || str_contains($e->getMessage(), 'cURL error 60');

            Log::error($ssl ? 'WhatsApp Cloud API SSL verification failed' : 'WhatsApp Cloud API request timed out', [
                'error' => $e->getMessage(),
                'template' => $templateName,
                'recipient' => $this->redact($to),
            ]);

            throw new RuntimeException(
                $ssl
                    ? 'Could not connect to WhatsApp because PHP could not verify the SSL certificate. Set WHATSAPP_CAFILE (or WAAFIPAY_CAFILE) to a CA bundle.'
                    : 'WhatsApp Cloud API request timed out.',
                0,
                $e
            );
        }
    }

    public function ticketTemplate(): string
    {
        return trim((string) config('whatsapp.template_ticket', ''));
    }

    public function inviteTemplate(): string
    {
        return trim((string) config('whatsapp.template_invite', ''));
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

    /**
     * @return list<string>
     */
    private function languageCandidates(): array
    {
        $primary = trim((string) config('whatsapp.template_lang', 'en'));
        if ($primary === '') {
            $primary = 'en';
        }

        $candidates = [$primary];
        foreach (['en_US', 'en', 'en_GB'] as $code) {
            if (strcasecmp($code, $primary) !== 0) {
                $candidates[] = $code;
            }
        }

        return $candidates;
    }

    private function shouldRetryLanguage(Response $response): bool
    {
        $code = (int) data_get($response->json(), 'error.code');
        $subcode = (int) data_get($response->json(), 'error.error_subcode');
        $message = strtolower($this->graphError($response));

        return in_array($code, [132000, 132001], true)
            || $subcode === 2494010
            || str_contains($message, 'template name does not exist in the translation')
            || str_contains($message, 'template not found');
    }

    private function graphError(Response $response): string
    {
        $json = $response->json();
        $message = trim((string) data_get($json, 'error.error_user_msg', ''));
        if ($message === '') {
            $message = trim((string) data_get($json, 'error.message', ''));
        }
        $details = trim((string) data_get($json, 'error.error_data.details', ''));
        if ($details !== '') {
            $message = $message === '' ? $details : $message.' '.$details;
        }

        if ($message === '') {
            return 'Failed to send WhatsApp message (HTTP '.$response->status().').';
        }

        return $message;
    }

    private function token(): string
    {
        return trim((string) config('whatsapp.token', ''));
    }

    private function phoneNumberId(): string
    {
        return trim((string) config('whatsapp.phone_number_id', ''));
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
