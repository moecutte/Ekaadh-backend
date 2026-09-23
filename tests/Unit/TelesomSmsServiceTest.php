<?php

namespace Tests\Unit;

use App\Services\TelesomSmsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelesomSmsServiceTest extends TestCase
{
    public function test_send_otp_posts_numeric_code_to_otp_endpoint(): void
    {
        config([
            'telesom.sender_id' => 'EKAADH',
            'telesom.username' => 'user',
            'telesom.password' => 'pass',
            'telesom.secret_key' => 'secret',
            'telesom.client_ref' => 'TLS-240',
            'telesom.base_url' => 'https://sms.mytelesom.com',
            'telesom.otp_path' => '/index.php/smsotpapi/v1/otp',
        ]);

        Http::fake([
            'sms.mytelesom.com/*' => Http::response([
                'status' => 'accepted',
                'request_id' => 'req-otp-1',
            ], 202),
        ]);

        $result = app(TelesomSmsService::class)->sendOtp('+252633001111', '77889', 600);

        $this->assertSame('accepted', $result['status']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sms.mytelesom.com/index.php/smsotpapi/v1/otp'
                && $request->hasHeader('SenderID', 'EKAADH')
                && $request->hasHeader('X-Auth-Key')
                && ($data['to'] ?? null) === ['252633001111']
                && ($data['message'] ?? null) === '77889'
                && ($data['type'] ?? null) === 'text'
                && ($data['client_ref'] ?? null) === 'TLS-240';
        });
    }

    public function test_send_keeps_using_standard_sms_endpoint(): void
    {
        config([
            'telesom.sender_id' => 'EKAADH',
            'telesom.username' => 'user',
            'telesom.password' => 'pass',
            'telesom.secret_key' => 'secret',
            'telesom.client_ref' => 'TLS-240',
            'telesom.base_url' => 'https://sms.mytelesom.com',
            'telesom.sms_path' => '/index.php/smsapi/v1/messages',
        ]);

        Http::fake([
            'sms.mytelesom.com/*' => Http::response([
                'status' => 'accepted',
                'request_id' => 'req-sms-1',
            ], 202),
        ]);

        app(TelesomSmsService::class)->send('+252633001111', 'Ticket ready');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://sms.mytelesom.com/index.php/smsapi/v1/messages'
                && ($data['to'] ?? null) === ['252633001111']
                && ($data['message'] ?? null) === 'Ticket ready';
        });
    }
}
