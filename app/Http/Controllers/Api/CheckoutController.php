<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OtpService;
use App\Services\Payments\WaafiPayGateway;
use App\Support\PaymentMessage;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private OtpService $otp,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_phone' => ['required', 'string', 'max:30'],
            'payment_method' => ['nullable', 'in:waafipay'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'pay_now' => ['sometimes', 'boolean'],
            'force_fail' => ['sometimes', 'boolean'],
            'otp_token' => ['nullable', 'string', 'max:80'],
            'otp_phone' => ['nullable', 'string', 'max:30'],
            'wallet_pin' => ['nullable', 'string', 'max:8'],
        ]);

        $forceFail = OrderService::allowsForceFail() && $request->boolean('force_fail');
        $event = Event::query()->findOrFail($data['event_id']);
        $chargePhone = Phone::normalize($data['buyer_phone']);
        $customer = auth('sanctum')->user();
        $customer = ($customer && method_exists($customer, 'isCustomer') && $customer->isCustomer())
            ? $customer
            : null;
        $sandboxPay = (bool) config('waafipay.sandbox');
        $walletPin = WaafiPayGateway::sandboxPin($data['wallet_pin'] ?? null);
        if (! $event->isFreeEvent() && $sandboxPay && $walletPin === null) {
            throw ValidationException::withMessages([
                'wallet_pin' => [WaafiPayGateway::sandboxPinError($data['wallet_pin'] ?? null)],
            ]);
        }

        $identityPhone = $chargePhone;
        if ($customer) {
            $identityPhone = Phone::normalize($customer->phone ?: $chargePhone);
            if (! $sandboxPay) {
                $chargePhone = $identityPhone;
            }
        } elseif (! $customer) {
            $otpPhone = Phone::normalize($data['otp_phone'] ?? $chargePhone);
            if (! $sandboxPay && $otpPhone !== $chargePhone) {
                throw ValidationException::withMessages([
                    'otp_token' => ['Phone confirmation expired or invalid. Request a new code.'],
                ]);
            }
            if ($this->otp->guestPhoneIsRegistered($otpPhone)) {
                throw ValidationException::withMessages([
                    'buyer_phone' => ['This phone number belongs to a customer account. Please sign in to continue.'],
                ]);
            }
            $this->otp->consumeVerified($otpPhone, OtpService::PURPOSE_CHECKOUT, $data['otp_token'] ?? null);
            $identityPhone = $otpPhone;
        }

        $order = $this->orders->createCheckout(
            $event,
            [
                'name' => $customer?->name ?? $data['buyer_name'],
                'email' => $this->resolveEmail($customer, $data['buyer_email'] ?? null),
                'phone' => $identityPhone,
                'payment_method' => $data['payment_method'] ?? null,
            ],
            $data['items'],
            $customer
        );

        if ($request->boolean('pay_now', true) && $order->status !== 'paid') {
            if (empty($data['payment_method'])) {
                return response()->json([
                    'message' => 'Payment method is required.',
                    'errors' => ['payment_method' => ['Pay with WaafiPay.']],
                ], 422);
            }

            $order = $this->orders->pay(
                $order,
                $data['payment_method'],
                $chargePhone,
                $forceFail,
                $walletPin
            );
        }

            $status = $order->status === 'paid' ? 201 : ($order->status === 'failed' ? 402 : ($order->status === 'pending' ? 202 : 201));

        return response()->json([
            'message' => $this->statusMessage($order),
            'order' => (new OrderResource($order))->resolve(),
        ], $status);
    }

    public function show(Request $request, string $orderNumber): OrderResource|JsonResponse
    {
        $phone = trim((string) $request->query('phone', ''));
        if ($phone === '') {
            return response()->json([
                'message' => 'Enter the checkout phone number.',
            ], 422);
        }

        $variants = Phone::variants($phone);
        $order = Order::query()
            ->with(['items.ticketType', 'items.tickets', 'event', 'payment'])
            ->where('order_number', $orderNumber)
            ->whereIn('buyer_phone', $variants)
            ->firstOrFail();

        $order = $this->orders->reconcile($order);

        return new OrderResource($order);
    }

    public function pay(Request $request, string $orderNumber): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:waafipay'],
            'phone' => ['required', 'string', 'max:30'],
            'force_fail' => ['sometimes', 'boolean'],
            'wallet_pin' => ['nullable', 'string', 'max:8'],
        ]);

        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();
        $phone = Phone::normalize($data['phone']);
        if (! Phone::matches($order->buyer_phone, $phone)) {
            throw ValidationException::withMessages([
                'phone' => ['Enter the checkout phone number for this order.'],
            ]);
        }
        $walletPin = WaafiPayGateway::sandboxPin($data['wallet_pin'] ?? null);
        if (config('waafipay.sandbox') && $walletPin === null) {
            throw ValidationException::withMessages([
                'wallet_pin' => [WaafiPayGateway::sandboxPinError($data['wallet_pin'] ?? null)],
            ]);
        }

        $order = $this->orders->pay(
            $order,
            $data['payment_method'],
            $phone,
            OrderService::allowsForceFail() && $request->boolean('force_fail'),
            $walletPin
        );

        $statusCode = match ($order->status) {
            'paid' => 200,
            'pending' => 202,
            default => 402,
        };

        return response()->json([
            'message' => $this->statusMessage($order),
            'order' => (new OrderResource($order))->resolve(),
        ], $statusCode);
    }

    private function resolveEmail($customer, ?string $email): ?string
    {
        if ($customer?->email && ! str_ends_with(strtolower($customer->email), '@ekaadh.local')) {
            return $customer->email;
        }

        return $email;
    }

    private function statusMessage(Order $order): string
    {
        return match ($order->status) {
            'paid' => 'Payment successful.',
            'pending' => 'Payment is being confirmed.',
            'failed' => PaymentMessage::forOrder($order),
            default => 'Order created.',
        };
    }
}
