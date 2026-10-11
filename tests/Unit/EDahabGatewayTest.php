<?php

namespace Tests\Unit;

use App\Services\Payments\EDahabClient;
use App\Services\Payments\EDahabGateway;
use App\Support\CheckoutPayments;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EDahabGatewayTest extends TestCase
{
    public function test_hash_matches_the_documented_example(): void
    {
        $body = json_encode(
            ['apiKey' => '123', 'invoiceId' => 'ABC'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $this->assertSame('{"apiKey":"123","invoiceId":"ABC"}', $body);
        $this->assertSame(
            'e31f35b1b8e354de379d250f5d87c421a6bb738441d199e8ed23dddbd8cd07e4',
            EDahabClient::hash((string) $body, 'secretapikey')
        );
    }

    public function test_wallet_number_strips_the_country_code(): void
    {
        $this->assertSame('652345678', EDahabClient::walletNumber('+252652345678'));
        $this->assertSame('', EDahabClient::walletNumber('65'));
    }

    public function test_issue_invoice_marks_a_paid_push_as_success(): void
    {
        $this->configureEdahab();
        Http::fake([
            'edahab.net/*' => Http::response([
                'InvoiceStatus' => 'Paid',
                'TransactionId' => 'MP260222.1316',
                'invoiceId' => 'INV123456789',
                'StatusCode' => 0,
                'StatusDescription' => 'Success',
            ]),
        ]);

        $result = app(EDahabGateway::class)->initiate(10, 'ORD-1', [
            'phone' => '+252652345678',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame('MP260222.1316', $result['transaction_id']);
        $this->assertSame('INV123456789', $result['raw']['invoice_id']);

        Http::assertSent(function ($request) {
            $body = $request->body();
            $hash = EDahabClient::hash($body, 'secretapikey');

            return str_contains($request->url(), 'https://edahab.net/api/api/Issueinvoice?hash='.$hash)
                && str_contains($body, '"edahabNumber":"652345678"')
                && str_contains($body, '"agentCode":"5555"')
                && str_contains($body, '"amount":10');
        });
    }

    public function test_pending_invoice_can_be_confirmed_later(): void
    {
        $this->configureEdahab();
        Http::fake([
            'edahab.net/*' => Http::sequence()
                ->push([
                    'InvoiceStatus' => 'Pending',
                    'invoiceId' => 'INV123456789',
                    'StatusCode' => 0,
                    'StatusDescription' => 'Success',
                ])
                ->push([
                    'InvoiceStatus' => 'Paid',
                    'TransactionId' => 'MP260222.1316',
                    'invoiceId' => 'INV123456789',
                    'StatusCode' => 0,
                    'StatusDescription' => 'Success',
                ]),
        ]);

        $gateway = app(EDahabGateway::class);
        $pending = $gateway->initiate(1, 'ORD-2', ['phone' => '0652345678']);
        $this->assertSame('pending', $pending['status']);
        $this->assertSame('INV123456789', $pending['transaction_id']);

        $paid = $gateway->inquire('ORD-2', 'INV123456789');
        $this->assertSame('success', $paid['status']);
        $this->assertSame('MP260222.1316', $paid['transaction_id']);
    }

    public function test_checkout_accepts_edahab_only_when_credentials_are_ready(): void
    {
        config(['edahab.ready' => false, 'waafipay.card_checkout_enabled' => false]);
        $this->assertNotContains('edahab', CheckoutPayments::methods());

        config(['edahab.ready' => true]);
        $this->assertContains('edahab', CheckoutPayments::methods());
    }

    private function configureEdahab(): void
    {
        config([
            'edahab.api_key' => 'test-key',
            'edahab.api_secret' => 'secretapikey',
            'edahab.agent_code' => '5555',
            'edahab.currency' => 'USD',
            'edahab.base_url' => 'https://edahab.net/api/api/',
            'edahab.timeout' => 15,
            'edahab.cafile' => '',
        ]);
    }
}
