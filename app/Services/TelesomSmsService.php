<?php

namespace App\Services;

use App\Support\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelesomSmsService
{
    public function enabled(): bool
    {
        return filled(config('telesom.sender_id'))
            && filled(config('telesom.username'))
            && filled(config('telesom.password'));
    }

    /**
     * Send a standard / bulk SMS (notifications, tickets, invitations).
     *
     * @param  list<string>|string  $recipients
     * @return array<string, mixed>
     */
    public function send(string|array $recipients, string $message): array
    {
        $to = $this->normalizeRecipients($recipients);
        $payload = [
            'to' => $to,
            'message' => $message,
            'type' => $this->messageType($message),
        ];

        $clientRef = trim((string) config('telesom.client_ref', ''));
        if ($clientRef !== '') {
            $payload['client_ref'] = $clientRef;
        }

        $callback = trim((string) config('telesom.callback_url', ''));
        if ($callback !== '') {
            $payload['callback_url'] = $callback;
        }

        return $this->post(config('telesom.sms_path'), $payload, $to);
    }

    /**
     * Send a verification code as a standard prepaid SMS.
     *
     * /smsotpapi requires an OTP-prepaid account. Until Telesom enables that
     * product on this SenderID, codes go out on /smsapi/v1/messages.
     *
     * @return array<string, mixed>
     */
    public function sendOtp(string $recipient, string $code, int $ttlSeconds): array
    {
        $minutes = max(1, (int) ceil($ttlSeconds / 60));
        $body = str_replace(
            [':code', ':minutes'],
            [$code, (string) $minutes],
            (string) config('otp.sms_message', 'Your Ekaadh code is :code. Valid for :minutes minutes.')
        );

        return $this->send($recipient, $body);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $recipients
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload, array $recipients): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Telesom SMS is not configured.');
        }

        $timestamp = now('Africa/Mogadishu')->format('Y-m-d');
        $url = rtrim((string) config('telesom.base_url'), '/').'/'.ltrim($path, '/');

        $request = Http::timeout((int) config('telesom.timeout', 20))
            ->acceptJson()
            ->asJson()
            ->withHeaders($this->authHeaders($timestamp));

        $cafile = (string) config('telesom.cafile', '');
        if ($cafile !== '' && is_file($cafile)) {
            $request = $request->withOptions(['verify' => $cafile]);
        }

        try {
            $response = $request->post($url, $payload);
        } catch (ConnectionException $e) {
            $ssl = str_contains(strtolower($e->getMessage()), 'ssl')
                || str_contains($e->getMessage(), 'certificate')
                || str_contains($e->getMessage(), 'cURL error 60');

            Log::error($ssl ? 'Telesom SMS SSL verification failed' : 'Telesom SMS request timed out', [
                'error' => $e->getMessage(),
                'recipients' => array_map(fn (string $n) => $this->redact($n), $recipients),
            ]);

            throw new RuntimeException(
                $ssl
                    ? 'Could not connect to Telesom SMS because PHP could not verify the SSL certificate. Set TELESOM_CAFILE (or WAAFIPAY_CAFILE) to a CA bundle.'
                    : 'Telesom SMS request timed out.',
                0,
                $e
            );
        }

        $json = $response->json();
        $json = is_array($json) ? $json : [];

        if (! $this->accepted($response, $json)) {
            $error = $this->errorMessage($json, $response);

            Log::error('Telesom SMS failed', [
                'status' => $response->status(),
                'error' => $error,
                'body' => $response->body(),
                'recipients' => array_map(fn (string $n) => $this->redact($n), $recipients),
            ]);

            throw new RuntimeException($error);
        }

        Log::info('Telesom SMS accepted', [
            'request_id' => data_get($json, 'request_id'),
            'status' => data_get($json, 'status', 'accepted'),
            'recipients' => array_map(fn (string $n) => $this->redact($n), $recipients),
        ]);

        return $json;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(string $timestamp): array
    {
        $senderId = (string) config('telesom.sender_id');
        $username = (string) config('telesom.username');
        $password = (string) config('telesom.password');
        $secret = (string) config('telesom.secret_key', '');
        if ($secret === '') {
            $secret = $password;
        }

        $signature = base64_encode(hash_hmac(
            'sha256',
            $senderId.$timestamp.$username.$password,
            $secret,
            true
        ));

        return [
            'SenderID' => $senderId,
            'Username' => $username,
            'X-Timestamp' => $timestamp,
            'X-Auth-Key' => $signature,
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function accepted(Response $response, array $json): bool
    {
        if ($response->status() === 202) {
            return $this->resultsOk($json);
        }

        if ($response->successful() && strtolower((string) data_get($json, 'status', '')) === 'accepted') {
            return $this->resultsOk($json);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function resultsOk(array $json): bool
    {
        $results = data_get($json, 'results');
        if (! is_array($results) || $results === []) {
            return true;
        }

        foreach ($results as $row) {
            $status = strtolower((string) data_get($row, 'status', ''));
            if (in_array($status, ['failed', 'rejected', 'error'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function errorMessage(array $json, Response $response): string
    {
        $error = data_get($json, 'error');

        if (is_string($error) && $error !== '') {
            return $error;
        }

        if (is_array($error)) {
            $message = (string) ($error['message'] ?? $error['code'] ?? '');
            if ($message !== '') {
                return $message;
            }
        }

        $results = data_get($json, 'results');
        if (is_array($results)) {
            foreach ($results as $row) {
                $description = (string) data_get($row, 'descriptions', '');
                if ($description !== '') {
                    return $description;
                }
            }
        }

        return 'Failed to send SMS via Telesom (HTTP '.$response->status().').';
    }

    /**
     * @param  list<string>|string  $recipients
     * @return list<string>
     */
    private function normalizeRecipients(string|array $recipients): array
    {
        $list = is_array($recipients) ? $recipients : [$recipients];
        $normalized = [];

        foreach ($list as $raw) {
            $phone = Phone::normalize((string) $raw);
            if ($phone === '' || strlen($phone) < 12) {
                throw new RuntimeException('Invalid Telesom SMS recipient phone number.');
            }
            $normalized[] = $phone;
        }

        if ($normalized === []) {
            throw new RuntimeException('No Telesom SMS recipients provided.');
        }

        return array_values(array_unique($normalized));
    }

    private function messageType(string $message): string
    {
        return preg_match('/[^\x00-\x7F]/', $message) === 1 ? 'unicode' : 'text';
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
