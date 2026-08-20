<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizerProfile;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\OrderService;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_api_controller_is_wired(): void
    {
        $this->getJson('/api/v1/tickets/EKD-DOESNOTEXIST')->assertNotFound();
    }

    public function test_pay_requires_matching_phone(): void
    {
        $this->bindGateway($this->successGateway());
        $order = $this->pendingOrder();

        $this->postJson('/api/v1/orders/'.$order->order_number.'/pay', [
            'payment_method' => 'waafipay',
            'phone' => '+252611000000',
        ])->assertStatus(422);

        $this->postJson('/api/v1/orders/'.$order->order_number.'/pay', [
            'payment_method' => 'waafipay',
        ])->assertStatus(422);
    }

    public function test_timeout_keeps_order_pending_and_does_not_charge_twice(): void
    {
        $gateway = new class implements PaymentGatewayInterface
        {
            public int $initiates = 0;

            public int $inquiries = 0;

            public function name(): string
            {
                return 'waafipay';
            }

            public function initiate(float $amount, string $reference, array $options = []): array
            {
                $this->initiates++;

                return [
                    'status' => 'pending',
                    'transaction_id' => 'WAAFI-TIMEOUT-'.$reference,
                    'message' => 'Confirming',
                    'raw' => ['result' => 'TIMEOUT', 'reference' => $reference],
                ];
            }

            public function inquire(string $reference, ?string $transactionId = null): array
            {
                $this->inquiries++;

                return [
                    'status' => 'unknown',
                    'transaction_id' => $transactionId ?: $reference,
                    'message' => 'Confirming',
                    'raw' => ['result' => 'NOT_FOUND', 'reference' => $reference],
                ];
            }
        };

        $this->bindGateway($gateway);
        $order = $this->pendingOrder();

        app(OrderService::class)->pay($order, 'waafipay', $order->buyer_phone);
        $order->refresh();

        $this->assertSame('pending', $order->status);
        $this->assertSame('initiated', $order->payment?->status);
        $this->assertSame(1, $gateway->initiates);

        app(OrderService::class)->pay($order->fresh(), 'waafipay', $order->buyer_phone);
        $this->assertSame(1, $gateway->initiates);
        $this->assertSame(1, $gateway->inquiries);
        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_reconcile_issues_tickets_when_inquiry_succeeds(): void
    {
        $gateway = new class implements PaymentGatewayInterface
        {
            public function name(): string
            {
                return 'waafipay';
            }

            public function initiate(float $amount, string $reference, array $options = []): array
            {
                return [
                    'status' => 'pending',
                    'transaction_id' => 'WAAFI-TIMEOUT-'.$reference,
                    'message' => 'Confirming',
                    'raw' => ['result' => 'TIMEOUT'],
                ];
            }

            public function inquire(string $reference, ?string $transactionId = null): array
            {
                return [
                    'status' => 'success',
                    'transaction_id' => 'WAAFI-OK',
                    'message' => 'Payment successful.',
                    'raw' => ['result' => 'APPROVED'],
                ];
            }
        };

        $this->bindGateway($gateway);
        $order = $this->pendingOrder();
        $type = $order->items()->first()->ticketType;
        $soldBefore = (int) $type->quantity_sold;

        $service = app(OrderService::class);
        $service->pay($order, 'waafipay', $order->buyer_phone);
        $paid = $service->reconcile($order->fresh());

        $this->assertSame('paid', $paid->status);
        $this->assertSame(2, Ticket::query()->count());
        $this->assertTrue(Str::startsWith(Ticket::query()->first()->ticket_code, 'EKD-'));
        $this->assertSame(16, strlen(explode('-', Ticket::query()->first()->ticket_code)[1]));
        $this->assertSame($soldBefore + 2, (int) $type->fresh()->quantity_sold);
    }

    private function bindGateway(PaymentGatewayInterface $gateway): void
    {
        $this->app->instance(PaymentGatewayInterface::class, $gateway);
    }

    private function successGateway(): PaymentGatewayInterface
    {
        return new class implements PaymentGatewayInterface
        {
            public function name(): string
            {
                return 'waafipay';
            }

            public function initiate(float $amount, string $reference, array $options = []): array
            {
                return [
                    'status' => 'success',
                    'transaction_id' => 'WAAFI-OK',
                    'message' => 'ok',
                    'raw' => ['result' => 'APPROVED'],
                ];
            }

            public function inquire(string $reference, ?string $transactionId = null): array
            {
                return [
                    'status' => 'unknown',
                    'transaction_id' => $reference,
                    'message' => '',
                    'raw' => [],
                ];
            }
        };
    }

    private function pendingOrder(): Order
    {
        Setting::setValue('service_fee', '1');
        Setting::setValue('default_commission_rate', '10');

        $organizer = User::factory()->create(['role' => User::ROLE_ORGANIZER]);
        $profile = OrganizerProfile::query()->create([
            'user_id' => $organizer->id,
            'business_name' => 'Test Org',
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);

        $event = Event::query()->create([
            'organizer_id' => $profile->id,
            'title' => 'Test Event',
            'slug' => 'test-event-'.Str::lower(Str::random(8)),
            'venue' => 'Hargeisa',
            'city' => 'Hargeisa',
            'event_date' => now()->addDays(10)->toDateString(),
            'event_time' => '18:00:00',
            'status' => 'published',
            'is_private' => false,
        ]);

        $type = TicketType::query()->create([
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 10,
            'quantity_available' => 50,
            'quantity_sold' => 0,
            'max_per_order' => 10,
        ]);

        return app(OrderService::class)->createCheckout(
            $event,
            [
                'name' => 'Buyer',
                'email' => 'buyer@example.com',
                'phone' => '+252633333333',
                'payment_method' => 'waafipay',
            ],
            [['ticket_type_id' => $type->id, 'quantity' => 2]],
        );
    }
}
