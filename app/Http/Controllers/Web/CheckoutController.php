<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OtpService;
use App\Services\TicketQrService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'serviceFee' => (float) \App\Models\Setting::getValue('service_fee', 1),
            'allowForceFail' => OrderService::allowsForceFail(),
            'customer' => $customer,
            'otpSendUrl' => url('/api/v1/otp/send'),
            'otpVerifyUrl' => url('/api/v1/otp/verify'),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $event = Event::query()->publicListing()->where('slug', $slug)->firstOrFail();

        $customer = $request->user();
        $customer = ($customer && $customer->isCustomer()) ? $customer : null;

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_phone' => ['required', 'string', 'max:30'],
            'payment_method' => ['required', 'in:zaad,edahab'],
            'qty' => ['required', 'array'],
            'qty.*' => ['integer', 'min:0', 'max:20'],
            'force_fail' => ['sometimes', 'boolean'],
            'otp_token' => ['nullable', 'string', 'max:80'],
        ]);

        $phone = Phone::normalize($data['buyer_phone']);

        if ($customer) {
            $data['buyer_name'] = $customer->name;
            $phone = Phone::normalize($customer->phone ?: $phone);
            $email = $customer->email;
            if ($email && ! str_ends_with(strtolower($email), '@ekaadh.local')) {
                $data['buyer_email'] = $email;
            }
        } else {
            if ($this->otp->guestPhoneIsRegistered($phone)) {
                return back()->withErrors([
                    'buyer_phone' => 'This phone number belongs to a customer account. Please sign in to continue.',
                ])->withInput();
            }

            try {
                $this->otp->consumeVerified($phone, OtpService::PURPOSE_CHECKOUT, $data['otp_token'] ?? null);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }
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
                    'phone' => $phone,
                    'payment_method' => $data['payment_method'],
                ],
                $items,
                $customer
            );

            $order = $this->orders->pay(
                $order,
                $data['payment_method'],
                $phone,
                OrderService::allowsForceFail() && $request->boolean('force_fail')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        if ($order->status === 'paid') {
            return redirect()->route('checkout.confirmation', $order->order_number);
        }

        return redirect()
            ->route('checkout.failed', $order->order_number)
            ->with('error', 'Payment could not be completed.');
    }

    public function confirmation(string $orderNumber): View
    {
        $order = Order::query()
            ->with(['items.ticketType', 'items.tickets', 'event', 'payment'])
            ->where('order_number', $orderNumber)
            ->where('status', 'paid')
            ->firstOrFail();

        $tickets = $order->items
            ->flatMap(fn ($item) => $item->tickets)
            ->map(function ($ticket) {
                $payload = $this->qr->payload($ticket->ticket_code);
                $ticket->qr_image = 'https://api.qrserver.com/v1/create-qr-code/?size=96x96&data='.urlencode($payload);
                $ticket->ticket_url = $this->qr->publicUrl($ticket->ticket_code);

                return $ticket;
            });

        return view('checkout.confirmation', compact('order', 'tickets'));
    }

    public function failed(string $orderNumber): View
    {
        $order = Order::query()
            ->with('event')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('checkout.failed', compact('order'));
    }
}
