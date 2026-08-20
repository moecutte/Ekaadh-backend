<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\PanelAlert;
use App\Support\Phone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    public const TYPE_SUPPORT_REPLY = 'support_reply';

    public const TYPE_EVENT_REMINDER = 'event_reminder';

    public const TYPE_INVITATION_RECEIVED = 'invitation_received';

    public const TYPE_PRIVATE_EVENT_PAID = 'private_event_paid';

    public const TYPE_INVITE_SEND_FAILED = 'invite_send_failed';

    public const TYPE_TICKETS_READY = 'tickets_ready';

    public function enabled(): bool
    {
        if (! config('fcm.enabled')) {
            return false;
        }

        $project = trim((string) config('fcm.project_id'));
        $credentials = (string) config('fcm.credentials');

        return $project !== '' && is_readable($credentials);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToUser(User $user, string $title, string $body, string $type, array $data = [], bool $inbox = false): void
    {
        if ($inbox) {
            try {
                $user->notify(new PanelAlert($title, $body, $type, null, $data));
            } catch (Throwable $e) {
                report($e);
            }
        }

        if (! $user->push_notifications_enabled) {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        $this->sendToTokens($tokens, $title, $body, $type, $data);
    }

    public function sendToPhone(?string $phone, string $title, string $body, string $type, array $data = [], bool $inbox = false): void
    {
        $variants = Phone::variants($phone);
        if ($variants === []) {
            return;
        }

        $users = User::query()->whereIn('phone', $variants)->get();
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $type, $data, $inbox);
        }
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, string>  $data
     */
    public function sendToTokens(array $tokens, string $title, string $body, string $type, array $data = []): void
    {
        if ($tokens === []) {
            return;
        }

        if (! $this->enabled()) {
            Log::info('FCM push skipped (not configured)', [
                'type' => $type,
                'title' => $title,
                'tokens' => count($tokens),
            ]);

            return;
        }

        $payloadData = array_merge(['type' => $type], $data);
        $payloadData = array_map(static fn ($v) => (string) $v, $payloadData);

        foreach ($tokens as $token) {
            try {
                $this->sendOne($token, $title, $body, $payloadData);
            } catch (Throwable $e) {
                Log::warning('FCM push failed', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, string>  $data
     */
    private function sendOne(string $token, string $title, string $body, array $data): void
    {
        $projectId = config('fcm.project_id');
        $accessToken = $this->accessToken();

        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->status() === 404 || $response->status() === 410
            || str_contains($response->body(), 'UNREGISTERED')
            || str_contains($response->body(), 'INVALID_ARGUMENT')) {
            DeviceToken::query()->where('token', $token)->delete();

            return;
        }

        if (! $response->successful()) {
            throw new \RuntimeException('FCM HTTP '.$response->status().': '.$response->body());
        }

        DeviceToken::query()->where('token', $token)->update(['last_used_at' => now()]);
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $path = (string) config('fcm.credentials');
            $json = json_decode((string) file_get_contents($path), true);
            if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
                throw new \RuntimeException('Invalid FCM credentials JSON.');
            }

            $now = time();
            $header = $this->b64(['alg' => 'RS256', 'typ' => 'JWT']);
            $claim = $this->b64([
                'iss' => $json['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);

            $unsigned = $header.'.'.$claim;
            $key = openssl_pkey_get_private($json['private_key']);
            if ($key === false) {
                throw new \RuntimeException('Unable to read FCM private key.');
            }

            $signature = '';
            openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
            $jwt = $unsigned.'.'.$this->b64raw($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new \RuntimeException('Unable to obtain FCM access token: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function b64(array $data): string
    {
        return $this->b64raw(json_encode($data, JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    private function b64raw(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
