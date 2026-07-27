<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OtpService;
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
            'payment_method' => ['nullable', 'in:zaad,edahab'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'pay_now' => ['sometimes', 'boolean'],
            'force_fail' => ['sometimes', 'boolean'],
            'otp_token' => ['nullable', 'string', 'max:80'],
        ]);

        $forceFail = OrderService::allowsForceFail() && $request->boolean('force_fail');
        $event = Event::query()->findOrFail($data['event_id']);
        $phone = Phone::normalize($data['buyer_phone']);
        $customer = auth('sanctum')->user();
        $customer = ($customer && method_exists($customer, 'isCustomer') && $customer->isCustomer())
            ? $customer
            : null;

        if ($customer) {
            $phone = Phone::normalize($customer->phone ?: $phone);
        } else {
            if ($this->otp->guestPhoneIsRegistered($phone)) {
                throw ValidationException::withMessages([
                    'buyer_phone' => ['This phone number belongs to a customer account. Please sign in to continue.'],
                ]);
            }
            $this->otp->consumeVerified($phone, OtpService::PURPOSE_CHECKOUT, $data['otp_token'] ?? null);
        }

        $order = $this->orders->createCheckout(
            $event,
            [
                'name' => $customer?->name ?? $data['buyer_name'],
                'email' => $this->resolveEmail($customer, $data['buyer_email'] ?? null),
                'phone' => $phone,
                'payment_method' => $data['payment_method'] ?? null,
            ],
            $data['items'],
            $customer
        );

        if ($request->boolean('pay_now', true)) {
            if (empty($data['payment_method'])) {
                return response()->json([
                    'message' => 'Payment method is required.',
                    'errors' => ['payment_method' => ['Choose Zaad or eDahab.']],
                ], 422);
            }

            $order = $this->orders->pay(
                $order,
                $data['payment_method'],
                $phone,
                $forceFail
            );
        }

        $status = $order->status === 'paid' ? 201 : ($order->status === 'failed' ? 402 : 201);

        return response()->json([
            'message' => match ($order->status) {
                'paid' => 'Payment successful.',
                'failed' => 'Payment failed.',
                default => 'Order created.',
            },
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

        return new OrderResource($order);
    }

    public function pay(Request $request, string $orderNumber): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:zaad,edahab'],
            'phone' => ['nullable', 'string', 'max:30'],
            'force_fail' => ['sometimes', 'boolean'],
        ]);

        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();
        $phone = Phone::normalize($data['phone'] ?? $order->buyer_phone);

        $order = $this->orders->pay(
            $order,
            $data['payment_method'],
            $phone,
            OrderService::allowsForceFail() && $request->boolean('force_fail')
        );

        return response()->json([
            'message' => $order->status === 'paid' ? 'Payment successful.' : 'Payment failed.',
            'order' => (new OrderResource($order))->resolve(),
        ], $order->status === 'paid' ? 200 : 402);
    }

    private function resolveEmail($customer, ?string $email): ?string
    {
        if ($customer?->email && ! str_ends_with(strtolower($customer->email), '@ekaadh.local')) {
            return $customer->email;
        }

        return $email;
    }
}
