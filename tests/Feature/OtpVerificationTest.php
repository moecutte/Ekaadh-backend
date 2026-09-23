<?php

namespace Tests\Feature;

use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['otp.fixed_code' => '123456']);
    }

    public function test_empty_token_asks_to_confirm_phone(): void
    {
        $otp = app(OtpService::class);

        try {
            $otp->assertVerified('+252611111111', OtpService::PURPOSE_CHECKOUT, '');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Confirm your phone number with the code we sent.',
                $e->errors()['otp_token'][0]
            );
        }
    }

    public function test_verified_token_is_accepted_across_phone_formats(): void
    {
        $otp = app(OtpService::class);
        $token = $otp->verify('+2520611111111', OtpService::PURPOSE_CHECKOUT, '123456');

        $this->assertNotEmpty($token);
        $otp->assertVerified('+252611111111', OtpService::PURPOSE_CHECKOUT, $token);
        $otp->consumeVerified('0611111111', OtpService::PURPOSE_CHECKOUT, ' '.$token.' ');
    }

    public function test_api_otp_verify_returns_token(): void
    {
        $this->postJson('/api/v1/otp/verify', [
            'phone' => '+252611111111',
            'purpose' => 'checkout',
            'otp' => '123456',
        ])->assertOk()
            ->assertJsonPath('phone', '+252611111111')
            ->assertJsonStructure(['otp_token']);
    }

    public function test_web_otp_send_returns_json_when_sms_fails(): void
    {
        config([
            'otp.fixed_code' => '',
            'otp.expose_debug_code' => false,
            'telesom.sender_id' => 'EKAADH',
            'telesom.username' => 'user',
            'telesom.password' => 'pass',
            'telesom.secret_key' => 'secret',
            'telesom.base_url' => 'https://sms.mytelesom.com',
        ]);

        Http::fake([
            'sms.mytelesom.com/*' => Http::response([
                'status' => 'rejected',
                'error' => 'Insufficient credit',
            ], 400),
        ]);

        $this->postJson('/otp/send', [
            'phone' => '+252633001111',
            'purpose' => 'checkout',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_otp_send_posts_code_to_telesom_otp_endpoint(): void
    {
        config([
            'otp.fixed_code' => '',
            'otp.expose_debug_code' => false,
            'telesom.sender_id' => 'EKAADH',
            'telesom.username' => 'user',
            'telesom.password' => 'pass',
            'telesom.secret_key' => 'secret',
            'telesom.client_ref' => 'TLS-240',
            'telesom.base_url' => 'https://sms.mytelesom.com',
        ]);

        Http::fake([
            'sms.mytelesom.com/*' => Http::response([
                'status' => 'accepted',
                'request_id' => 'req-otp-1',
            ], 202),
        ]);

        $this->postJson('/api/v1/otp/send', [
            'phone' => '+252633001111',
            'purpose' => 'checkout',
        ])->assertOk()
            ->assertJsonPath('message', 'A confirmation code was sent to your phone.');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sms.mytelesom.com/index.php/smsotpapi/v1/otp'
                && $request->hasHeader('SenderID', 'EKAADH')
                && $request->hasHeader('X-Auth-Key')
                && ($data['to'] ?? null) === ['252633001111']
                && preg_match('/^\d{6}$/', (string) ($data['message'] ?? '')) === 1
                && ($data['type'] ?? null) === 'text'
                && ($data['client_ref'] ?? null) === 'TLS-240';
        });
    }
}
