<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();
        $search = $request->string('q')->trim()->toString();

        $customers = collect();

        if ($type === '' || $type === 'user') {
            $customers = $customers->merge($this->registeredCustomers($search));
        }

        if ($type === '' || $type === 'guest') {
            $customers = $customers->merge($this->guestCustomers($search));
        }

        $customers = $customers
            ->sortByDesc(fn ($row) => $row->sort_at?->timestamp ?? 0)
            ->values();

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $customers->forPage($page, $perPage)->values(),
            $customers->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $totals = [
            'users' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
            'guests' => (int) Order::query()
                ->whereNull('user_id')
                ->selectRaw('COUNT(DISTINCT buyer_phone) as aggregate')
                ->value('aggregate'),
            'active' => User::query()
                ->where('role', User::ROLE_CUSTOMER)
                ->where('status', 'active')
                ->count(),
        ];

        return view('admin.customers.index', [
            'customers' => $paginator,
            'totals' => $totals,
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function registeredCustomers(string $search): Collection
    {
        $query = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->withCount('orders')
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(fn (User $user) => (object) [
            'type' => 'user',
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'orders_count' => $user->orders_count,
            'sort_at' => $user->created_at,
            'joined_label' => $user->created_at?->format('M j, Y'),
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function guestCustomers(string $search): Collection
    {
        $registeredPhones = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->whereNotNull('phone')
            ->pluck('phone')
            ->all();

        $query = Order::query()
            ->whereNull('user_id')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('buyer_name', 'like', "%{$search}%")
                    ->orWhere('buyer_email', 'like', "%{$search}%")
                    ->orWhere('buyer_phone', 'like', "%{$search}%");
            });
        }

        if ($registeredPhones !== []) {
            $query->whereNotIn('buyer_phone', $registeredPhones);
        }

        return $query
            ->get(['buyer_name', 'buyer_email', 'buyer_phone', 'created_at'])
            ->groupBy('buyer_phone')
            ->map(function (Collection $orders) {
                $latest = $orders->first();
                $earliest = $orders->last();

                return (object) [
                    'type' => 'guest',
                    'name' => $latest->buyer_name,
                    'email' => $latest->buyer_email,
                    'phone' => $latest->buyer_phone,
                    'status' => null,
                    'orders_count' => $orders->count(),
                    'sort_at' => $latest->created_at,
                    'joined_label' => $earliest->created_at?->format('M j, Y'),
                ];
            })
            ->values();
    }
}
