<?php

namespace Tests\Feature;

use App\Services\OtpService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
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
}
