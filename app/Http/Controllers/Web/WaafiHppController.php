<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\WaafiPayGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaafiHppController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private WaafiPayGateway $waafi,
    ) {}

    public function success(Request $request): RedirectResponse
    {
        $order = $this->resolveOrder($request);
        if (! $order) {
            return redirect()
                ->route('events.index')
                ->with('error', __('ui.payment_confirming'));
        }

        $this->finalizeFromCallback($order, $request);

        $order = $order->fresh(['payment', 'event']);
        if ($order->status === 'paid') {
            return $this->ordersSuccessRedirect($order);
        }

        return redirect()
            ->route('checkout.pending', $order->order_number)
            ->with('success', __('ui.payment_confirming'));
    }

    public function failure(Request $request): RedirectResponse
    {
        $order = $this->resolveOrder($request);
        if (! $order) {
            return redirect()
                ->route('events.index')
                ->with('error', __('ui.payment_failed_generic'));
        }

        $this->finalizeFromCallback($order, $request);

        $order = $order->fresh(['payment', 'event']);
        if ($order->status === 'paid') {
            return $this->ordersSuccessRedirect($order);
        }

        return redirect()
            ->route('checkout.failed', $order->order_number)
            ->with('error', __('ui.payment_failed_generic'));
    }

    private function finalizeFromCallback(Order $order, Request $request): void
    {
        if ($order->status === 'paid') {
            return;
        }

        $reference = (string) (
            $request->input('referenceId')
            ?: $request->input('reference_id')
            ?: $order->payment_reference
            ?: $order->order_number
        );

        $transactionId = (string) (
            $request->input('transactionId')
            ?: $request->input('transaction_id')
            ?: $order->payment?->transaction_id
            ?: ''
        );

        Log::info('WaafiPay HPP callback', [
            'order' => $order->order_number,
            'reference' => $reference,
            'inputs' => $request->except(['cardNumber', 'cvv', 'card_number']),
        ]);

        $result = $this->waafi->inquireHpp($reference, $transactionId !== '' ? $transactionId : null);

        // Prefer charge_reference stored on payment when callback reference differs.
        $storedRef = (string) (($order->payment?->raw_response ?? [])['charge_reference'] ?? '');
        if (in_array($result['status'], ['unknown', 'failed'], true) && $storedRef !== '' && $storedRef !== $reference) {
            $result = $this->waafi->inquireHpp($storedRef, $transactionId !== '' ? $transactionId : null);
            $reference = $storedRef;
        }

        if (in_array($result['status'], ['pending', 'unknown'], true)) {
            // Still settling — leave pending for reconciler.
            return;
        }

        $this->orders->applyExternalGatewayResult(
            $order,
            (string) ($order->payment_method ?: 'waafipay_card'),
            $order->buyer_phone,
            $result
        );
    }

    private function resolveOrder(Request $request): ?Order
    {
        $candidates = array_filter([
            $request->input('referenceId'),
            $request->input('reference_id'),
            $request->input('invoiceId'),
            $request->input('invoice_id'),
            $request->query('referenceId'),
            $request->query('ref'),
        ], fn ($v) => filled($v));

        foreach ($candidates as $ref) {
            $ref = (string) $ref;
            $order = Order::query()
                ->where('order_number', $ref)
                ->orWhere('payment_reference', $ref)
                ->orWhere('payment_reference', 'like', $ref.'%')
                ->first();
            if ($order) {
                return $order;
            }

            // Retry refs like EKD-...-R2
            $base = preg_replace('/-R\d+$/', '', $ref) ?: $ref;
            $order = Order::query()->where('order_number', $base)->first();
            if ($order) {
                return $order;
            }
        }

        return null;
    }

    private function ordersSuccessRedirect(Order $order): RedirectResponse
    {
        if ($order->source === 'organizer_package' && $order->event_id) {
            return redirect()
                ->route('organizer.events.index')
                ->with('success', 'Payment received. Your free event is now live.');
        }

        if ($order->source === 'private_event' && $order->event_id) {
            return redirect()
                ->route('private-events.invitations.index', $order->event)
                ->with('success', 'Payment successful. Send invitations to your guests.');
        }

        return redirect()->route('checkout.confirmation', $order->order_number);
    }
}
