<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizerProfile;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\OrderService;
use App\Services\OtpService;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\TicketQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_show_hides_qr_without_owner_proof(): void
    {
        $this->bindGateway($this->successGateway());
        $order = $this->pendingOrder();
        $paid = app(OrderService::class)->pay($order, 'waafipay', $order->buyer_phone);
        $code = Ticket::query()->firstOrFail()->ticket_code;

        $this->getJson('/api/v1/tickets/'.$code)->assertNotFound();

        $this->getJson('/api/v1/tickets/'.$code.'?phone=+252611000000')->assertNotFound();

        $this->getJson('/api/v1/tickets/'.$code.'?phone='.urlencode($paid->buyer_phone))
            ->assertOk()
            ->assertJsonPath('data.ticket_code', $code)
            ->assertJsonPath('data.qr_payload', app(TicketQrService::class)->payload($code));

        $owner = User::factory()->create(['phone' => $paid->buyer_phone]);
        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/tickets/'.$code)
            ->assertOk()
            ->assertJsonPath('data.ticket_code', $code);
    }

    public function test_otp_send_fails_closed_in_production_without_sms(): void
    {
        $this->app['env'] = 'production';
        config([
            'otp.fixed_code' => '',
            'otp.expose_debug_code' => false,
            'telesom.sender_id' => '',
            'telesom.username' => '',
            'telesom.password' => '',
        ]);

        $this->expectException(ValidationException::class);
        app(OtpService::class)->send('+252633001111', OtpService::PURPOSE_REGISTER);
    }

    public function test_stale_pending_payment_releases_stock(): void
    {
        $this->bindGateway($this->timeoutGateway());
        $order = $this->pendingOrder();
        $type = $order->items()->first()->ticketType;
        $soldBefore = (int) $type->quantity_sold;

        app(OrderService::class)->pay($order, 'waafipay', $order->buyer_phone);
        $order->refresh();
        $this->assertSame('pending', $order->status);
        $this->assertSame($soldBefore + 2, (int) $type->fresh()->quantity_sold);

        $this->travel(31)->minutes();
        $expired = app(OrderService::class)->expireStaleInitiated($order->fresh(), 30);

        $this->assertSame('failed', $expired->status);
        $this->assertSame('failed', $expired->payment?->status);
        $this->assertSame($soldBefore, (int) $type->fresh()->quantity_sold);
        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_check_in_rejects_unsigned_code_unless_manual(): void
    {
        $this->bindGateway($this->successGateway());
        $order = $this->pendingOrder();
        app(OrderService::class)->pay($order, 'waafipay', $order->buyer_phone);
        $ticket = Ticket::query()->firstOrFail();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $checkIn = app(CheckInService::class);

        $unsigned = $checkIn->scan($ticket->ticket_code, $staff, $ticket->event_id, false);
        $this->assertSame('invalid', $unsigned['result']);

        $manual = $checkIn->scan($ticket->ticket_code, $staff, $ticket->event_id, true);
        $this->assertSame('valid', $manual['result']);

        $signed = $checkIn->scan(app(TicketQrService::class)->payload($ticket->ticket_code), $staff, $ticket->event_id, false);
        $this->assertSame('used', $signed['result']);
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

    private function timeoutGateway(): PaymentGatewayInterface
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
                    'status' => 'pending',
                    'transaction_id' => 'WAAFI-TIMEOUT-'.$reference,
                    'message' => 'Confirming',
                    'raw' => ['result' => 'TIMEOUT', 'reference' => $reference],
                ];
            }

            public function inquire(string $reference, ?string $transactionId = null): array
            {
                return [
                    'status' => 'unknown',
                    'transaction_id' => $transactionId ?: $reference,
                    'message' => 'Confirming',
                    'raw' => ['result' => 'NOT_FOUND'],
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
