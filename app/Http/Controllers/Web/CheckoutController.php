<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OtpService;
use App\Services\Payments\WaafiPayGateway;
use App\Services\TicketQrService;
use App\Support\PaymentMessage;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private TicketQrService $qr,
        private OtpService $otp,
    ) {}

    public function show(string $slug): View
    {
        $event = Event::query()
            ->publicListing()
            ->with('ticketTypes')
            ->where('slug', $slug)
            ->firstOrFail();

        $customer = auth()->user();
        $customer = ($customer && $customer->isCustomer()) ? $customer : null;

        return view('checkout.show', [
            'event' => $event,
            'serviceFee' => $event->isFreeEvent() ? 0.0 : (float) \App\Models\Setting::getValue('service_fee', 1),
            'isFreeEvent' => $event->isFreeEvent(),
            'allowForceFail' => OrderService::allowsForceFail(),
            'customer' => $customer,
            'otpSendUrl' => route('otp.send'),
            'otpVerifyUrl' => route('otp.verify'),
            'waafiSandbox' => (bool) config('waafipay.sandbox'),
            'waafiTestWallets' => config('waafipay.test_wallets', []),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        // WaafiPay can take ~45s; PHP's default 30s limit turns that into a 500.
        set_time_limit(120);

        $event = Event::query()->publicListing()->where('slug', $slug)->first();
        if (! $event) {
            return redirect()->route('events.index')->withErrors([
                'event' => 'This event is no longer available for purchase.',
            ]);
        }

        $customer = $request->user();
        $customer = ($customer && $customer->isCustomer()) ? $customer : null;

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_phone' => ['required', 'string', 'max:30'],
            'payment_method' => [$event->isFreeEvent() ? 'nullable' : 'required', 'in:waafipay'],
            'qty' => ['required', 'array'],
            'qty.*' => ['integer', 'min:0', 'max:20'],
            'force_fail' => ['sometimes', 'boolean'],
            'otp_token' => ['nullable', 'string', 'max:80'],
            'otp_phone' => ['nullable', 'string', 'max:30'],
            'wallet_pin' => ['nullable', 'string', 'max:8'],
        ]);

        $chargePhone = Phone::normalize($data['buyer_phone']);
        $sandboxPay = (bool) config('waafipay.sandbox');
        $walletPin = WaafiPayGateway::sandboxPin($data['wallet_pin'] ?? null);
        if (! $event->isFreeEvent() && $sandboxPay && $walletPin === null) {
            return back()->withErrors([
                'wallet_pin' => WaafiPayGateway::sandboxPinError($data['wallet_pin'] ?? null),
            ])->withInput();
        }

        $identityPhone = $chargePhone;
        if ($customer) {
            $data['buyer_name'] = $customer->name;
            $identityPhone = Phone::normalize($customer->phone ?: $chargePhone);
            if (! $sandboxPay) {
                $chargePhone = $identityPhone;
            }
            $email = $customer->email;
            if ($email && ! str_ends_with(strtolower($email), '@ekaadh.local')) {
                $data['buyer_email'] = $email;
            }
        } else {
            $otpPhone = Phone::normalize($data['otp_phone'] ?? $chargePhone);
            if (! $sandboxPay && $otpPhone !== $chargePhone) {
                return back()->withErrors([
                    'otp_token' => 'Phone confirmation expired or invalid. Request a new code.',
                ])->withInput();
            }

            if ($this->otp->guestPhoneIsRegistered($otpPhone)) {
                return back()->withErrors([
                    'buyer_phone' => 'This phone number belongs to a customer account. Please sign in to continue.',
                ])->withInput();
            }

            try {
                $this->otp->consumeVerified($otpPhone, OtpService::PURPOSE_CHECKOUT, $data['otp_token'] ?? null);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }
            $identityPhone = $otpPhone;
        }

        $items = [];
        foreach ($data['qty'] as $ticketTypeId => $quantity) {
            if ((int) $quantity > 0) {
                $items[] = [
                    'ticket_type_id' => (int) $ticketTypeId,
                    'quantity' => (int) $quantity,
                ];
            }
        }

        try {
            $order = $this->orders->createCheckout(
                $event,
                [
                    'name' => $data['buyer_name'],
                    'email' => $data['buyer_email'] ?? null,
                    'phone' => $identityPhone,
                    'payment_method' => $data['payment_method'] ?? null,
                ],
                $items,
                $customer
            );

            $this->grantOrderAccess($request, $order);

            if ($order->status !== 'paid') {
                $order = $this->orders->pay(
                    $order,
                    $data['payment_method'],
                    $chargePhone,
                    OrderService::allowsForceFail() && $request->boolean('force_fail'),
                    $walletPin
                );
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->withErrors([
                'items' => 'This ticket type is no longer available. Refresh the page and try again.',
            ])->withInput();
        }

        return $this->redirectToOrder($request, $order);
    }

    public function confirmation(Request $request, string $orderNumber): View|RedirectResponse
    {
        $order = Order::query()
            ->with(['items.ticketType', 'items.tickets', 'event', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $this->assertOrderAccess($request, $order);

        if ($order->status !== 'paid') {
            return $this->redirectToOrder($request, $order);
        }

        $tickets = $order->items
            ->flatMap(fn ($item) => $item->tickets)
            ->map(function ($ticket) {
                $ticket->qr_image = $this->qr->imageUrl($ticket->ticket_code);
                $ticket->ticket_url = $this->qr->publicUrl($ticket->ticket_code);

                return $ticket;
            });

        return view('checkout.confirmation', compact('order', 'tickets'));
    }

    public function failed(Request $request, string $orderNumber): View
    {
        $order = Order::query()
            ->with(['event', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $friendlyMessage = PaymentMessage::forOrder($order);

        return view('checkout.failed', compact('order', 'friendlyMessage'));
    }

    public function pending(Request $request, string $orderNumber): View|RedirectResponse
    {
        $order = Order::query()
            ->with('event')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $this->assertOrderAccess($request, $order);

        $order = $this->orders->reconcile($order);

        if ($order->status === 'paid') {
            return $this->redirectToOrder($request, $order);
        }

        if ($order->status === 'failed') {
            return $this->redirectToOrder($request, $order);
        }

        return view('checkout.pending', compact('order'));
    }

    private function redirectToOrder(Request $request, Order $order): RedirectResponse
    {
        $this->grantOrderAccess($request, $order);

        $route = match ($order->status) {
            'paid' => 'checkout.confirmation',
            'pending' => 'checkout.pending',
            default => 'checkout.failed',
        };

        // Relative so www vs apex APP_URL mismatch cannot invalidate the signature.
        return redirect()->to(URL::temporarySignedRoute(
            $route,
            now()->addDays(7),
            ['orderNumber' => $order->order_number],
            absolute: false,
        ));
    }

    private function grantOrderAccess(Request $request, Order $order): void
    {
        $request->session()->put('checkout_access.'.$order->order_number, true);
    }

    private function assertOrderAccess(Request $request, Order $order): void
    {
        if ($request->hasValidSignature() || $request->hasValidRelativeSignature()) {
            $this->grantOrderAccess($request, $order);

            return;
        }

        if ($request->session()->boolean('checkout_access.'.$order->order_number)) {
            return;
        }

        $user = $request->user();
        if ($user && (int) $order->user_id === (int) $user->id) {
            return;
        }

        abort(404);
    }
}
