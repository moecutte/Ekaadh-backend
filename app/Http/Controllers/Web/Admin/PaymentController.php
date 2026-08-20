<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\PaginatesFilteredLists;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use PaginatesFilteredLists;

    public function index(Request $request): View
    {
        $scope = $this->scope($request);

        $query = Payment::query();
        $this->applyScope($query, $scope);
        $this->applyFilters($query, $request);

        $stats = (clone $query)->toBase()->selectRaw("
            COUNT(*) as `count`,
            COALESCE(SUM(CASE WHEN payments.status = 'success' THEN payments.amount ELSE 0 END), 0) as success_volume,
            COALESCE(SUM(CASE WHEN payments.status = 'success' THEN 1 ELSE 0 END), 0) as success_count,
            COALESCE(SUM(CASE WHEN payments.status = 'failed' THEN 1 ELSE 0 END), 0) as failed,
            COALESCE(SUM(CASE WHEN payments.status = 'initiated' THEN 1 ELSE 0 END), 0) as initiated
        ")->first();

        $totals = [
            'count' => (int) ($stats->count ?? 0),
            'success_volume' => (float) ($stats->success_volume ?? 0),
            'success_count' => (int) ($stats->success_count ?? 0),
            'failed' => (int) ($stats->failed ?? 0),
            'initiated' => (int) ($stats->initiated ?? 0),
        ];

        $list = (clone $query)->select([
            'payments.id',
            'payments.order_id',
            'payments.provider',
            'payments.transaction_id',
            'payments.phone_number',
            'payments.amount',
            'payments.status',
            'payments.created_at',
            DB::raw($this->failureHintSql().' as failure_hint'),
        ])
            ->with([
                'order:id,order_number,buyer_name,event_id,status',
                'order.event:id,title',
            ])
            ->orderByDesc('payments.id');

        $payments = $this->paginateFiltered($list, $totals['count'], $request);

        $filtersActive = $this->filtersActive($request);
        $ops = $this->opsSnapshot();
        $scopes = $this->scopeLinks($request, $ops);

        return view('admin.payments.index', compact(
            'payments',
            'totals',
            'filtersActive',
            'ops',
            'scope',
            'scopes'
        ));
    }

    public function show(Payment $payment): View
    {
        $payment->load([
            'order.event.organizer',
            'order.items.tickets',
            'order.items.ticketType',
        ]);
        $payment->makeVisible('raw_response');

        return view('admin.payments.show', compact('payment'));
    }

    private function scope(Request $request): string
    {
        $scope = $request->string('scope')->toString();

        return in_array($scope, ['today', 'failed', 'stuck', 'mismatch'], true) ? $scope : '';
    }

    private function applyScope(Builder $query, string $scope): void
    {
        $today = now()->startOfDay();

        match ($scope) {
            'today' => $query->where('payments.created_at', '>=', $today),
            'failed' => $query->where('payments.status', 'failed')
                ->where('payments.created_at', '>=', now()->subHours(48)),
            'stuck' => $query->where('payments.status', 'initiated')
                ->where('payments.created_at', '<', now()->subMinutes(15)),
            'mismatch' => $this->applyMismatchScope($query),
            default => null,
        };
    }

    private function applyMismatchScope(Builder $query): void
    {
        $this->ensureOrderJoin($query);
        $query->where(function ($q) {
            $q->where(function ($inner) {
                $inner->where('payments.status', 'success')
                    ->where('orders.status', '!=', 'paid');
            })->orWhere(function ($inner) {
                $inner->where('payments.status', 'failed')
                    ->where('orders.status', 'paid');
            });
        });
    }

    /**
     * @return array<string, int|float>
     */
    private function opsSnapshot(): array
    {
        $today = now()->startOfDay();
        $row = Payment::query()
            ->where('created_at', '>=', $today)
            ->toBase()
            ->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END), 0) as success_count,
                COALESCE(SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END), 0) as success_volume,
                COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed,
                COALESCE(SUM(CASE WHEN status = 'initiated' THEN 1 ELSE 0 END), 0) as initiated
            ")
            ->first();

        $stuck = (int) Payment::query()
            ->where('status', 'initiated')
            ->where('created_at', '<', now()->subMinutes(15))
            ->count();

        $mismatch = (int) Payment::query()
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('status', 'success')
                        ->whereHas('order', fn ($o) => $o->where('status', '!=', 'paid'));
                })->orWhere(function ($inner) {
                    $inner->where('status', 'failed')
                        ->whereHas('order', fn ($o) => $o->where('status', 'paid'));
                });
            })
            ->count();

        $failed48 = (int) Payment::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(48))
            ->count();

        return [
            'today_total' => (int) ($row->total ?? 0),
            'today_success_count' => (int) ($row->success_count ?? 0),
            'today_success_volume' => (float) ($row->success_volume ?? 0),
            'today_failed' => (int) ($row->failed ?? 0),
            'today_initiated' => (int) ($row->initiated ?? 0),
            'stuck' => $stuck,
            'mismatch' => $mismatch,
            'failed48' => $failed48,
        ];
    }

    /**
     * @param  array<string, int|float>  $ops
     * @return list<array{key: string, label: string, url: string, active: bool, count: int|null}>
     */
    private function scopeLinks(Request $request, array $ops): array
    {
        $base = $request->except(['scope', 'page', 'status', 'date_from', 'date_to']);
        $active = $this->scope($request);

        return [
            ['key' => '', 'label' => 'All', 'url' => route('admin.payments.index', $base), 'active' => $active === '', 'count' => null],
            ['key' => 'today', 'label' => 'Today', 'url' => route('admin.payments.index', $base + ['scope' => 'today']), 'active' => $active === 'today', 'count' => $ops['today_total']],
            ['key' => 'failed', 'label' => 'Failed (48h)', 'url' => route('admin.payments.index', $base + ['scope' => 'failed']), 'active' => $active === 'failed', 'count' => $ops['failed48']],
            ['key' => 'stuck', 'label' => 'Stuck', 'url' => route('admin.payments.index', $base + ['scope' => 'stuck']), 'active' => $active === 'stuck', 'count' => $ops['stuck']],
            ['key' => 'mismatch', 'label' => 'Mismatches', 'url' => route('admin.payments.index', $base + ['scope' => 'mismatch']), 'active' => $active === 'mismatch', 'count' => $ops['mismatch']],
        ];
    }

    private function filtersActive(Request $request): bool
    {
        return collect($request->only([
            'q', 'status', 'provider', 'date_from', 'date_to', 'scope',
        ]))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    }

    private function failureHintSql(): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "json_extract(payments.raw_response, '$.user_message')"
            : "JSON_UNQUOTE(JSON_EXTRACT(payments.raw_response, '$.user_message'))";
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = $request->string('q')->trim()->toString()) {
            $this->ensureOrderJoin($query);
            $query->where(function ($q) use ($search) {
                $q->where('payments.transaction_id', 'like', $search.'%')
                    ->orWhere('payments.phone_number', 'like', '%'.$search.'%')
                    ->orWhere('orders.order_number', 'like', $search.'%')
                    ->orWhere('orders.buyer_name', 'like', '%'.$search.'%');
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('payments.status', $status);
        }

        if ($provider = $request->string('provider')->toString()) {
            $query->where('payments.provider', $provider);
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->where('payments.created_at', '>=', $from.' 00:00:00');
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->where('payments.created_at', '<=', $to.' 23:59:59');
        }
    }

    private function ensureOrderJoin(Builder $query): void
    {
        if ($this->queryHasJoin($query, 'orders')) {
            return;
        }

        $query->join('orders', 'orders.id', '=', 'payments.order_id');
    }
}
