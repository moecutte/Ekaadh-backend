<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TicketDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverPaidOrderTickets implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $orderId) {}

    public function handle(TicketDeliveryService $delivery): void
    {
        $order = Order::query()->find($this->orderId);
        if (! $order || $order->status !== 'paid') {
            return;
        }

        $delivery->sendForOrder($order);
    }
}
