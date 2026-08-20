<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile
                            {--minutes=2 : Only look at initiated payments older than this many minutes}
                            {--expire-minutes=30 : Fail still-pending charges older than this after inquiry}';

    protected $description = 'Ask WaafiPay about initiated/timed-out charges and issue tickets if they succeeded';

    public function handle(OrderService $orders): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $expireMinutes = max(1, (int) $this->option('expire-minutes'));

        $pending = Order::query()
            ->with('payment')
            ->where('status', 'pending')
            ->whereHas('payment', function ($q) use ($minutes) {
                $q->where('status', 'initiated')
                    ->where('updated_at', '<=', now()->subMinutes($minutes));
            })
            ->orderBy('id')
            ->limit(50)
            ->get();

        $paid = 0;
        $failed = 0;
        $unchanged = 0;

        foreach ($pending as $order) {
            $fresh = $orders->reconcile($order);
            if ($fresh->status === 'pending') {
                $fresh = $orders->expireStaleInitiated($fresh, $expireMinutes);
            }
            match ($fresh->status) {
                'paid' => $paid++,
                'failed' => $failed++,
                default => $unchanged++,
            };
        }

        $this->info("Reconciled {$pending->count()} orders ({$paid} paid, {$failed} failed, {$unchanged} still pending).");

        return self::SUCCESS;
    }
}
