<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrganizerProfile;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(): View
    {
        $organizers = OrganizerProfile::query()
            ->with('user')
            ->where('approval_status', 'approved')
            ->orderBy('business_name')
            ->get()
            ->map(function (OrganizerProfile $org) {
                $eventIds = $org->events()->pluck('id');
                $gross = $eventIds->isEmpty() ? 0 : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->ticketSales()->sum('subtotal');
                $commission = $eventIds->isEmpty() ? 0 : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->ticketSales()->sum('commission_amount');
                $net = $gross - $commission;
                $paidOut = (float) Payout::query()->where('organizer_id', $org->id)->where('status', 'paid')->sum('net_payout');

                return [
                    'profile' => $org,
                    'events' => $eventIds->count(),
                    'gross' => $gross,
                    'pending' => max(0, $net - $paidOut),
                    'last_paid' => Payout::query()->where('organizer_id', $org->id)->where('status', 'paid')->latest('paid_at')->value('paid_at'),
                ];
            });

        $payouts = Payout::query()->with(['organizer', 'paidBy'])->latest()->paginate(20);

        return view('admin.payouts.index', compact('organizers', 'payouts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organizer_id' => ['required', 'exists:organizer_profiles,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $org = OrganizerProfile::query()->findOrFail($data['organizer_id']);
        $eventIds = $org->events()->pluck('id');

        $gross = $eventIds->isEmpty() ? 0 : (float) Order::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', 'paid')
            ->ticketSales()
            ->whereBetween('created_at', [$data['period_start'].' 00:00:00', $data['period_end'].' 23:59:59'])
            ->sum('subtotal');

        $commission = $eventIds->isEmpty() ? 0 : (float) Order::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', 'paid')
            ->ticketSales()
            ->whereBetween('created_at', [$data['period_start'].' 00:00:00', $data['period_end'].' 23:59:59'])
            ->sum('commission_amount');

        $alreadyPaid = (float) Payout::query()
            ->where('organizer_id', $org->id)
            ->where('status', 'paid')
            ->whereDate('period_start', '>=', $data['period_start'])
            ->whereDate('period_end', '<=', $data['period_end'])
            ->sum('net_payout');

        $net = max(0, $gross - $commission - $alreadyPaid);

        if ($net <= 0) {
            return back()->with('error', 'No payable balance for this period.');
        }

        Payout::query()->create([
            'organizer_id' => $org->id,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'gross_sales' => $gross,
            'commission_deducted' => $commission,
            'net_payout' => $net,
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->notifyOrganizerPayout($org, (float) $net);

        return back()->with('success', "Recorded payout of \${$net} to {$org->business_name}.");
    }

    public function markPaid(Payout $payout): RedirectResponse
    {
        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);

        $payout->loadMissing('organizer.user');
        if ($payout->organizer) {
            $this->notifyOrganizerPayout($payout->organizer, (float) $payout->net_payout);
        }

        return back()->with('success', 'Payout marked as paid.');
    }

    private function notifyOrganizerPayout(OrganizerProfile $org, float $net): void
    {
        $user = $org->user;
        if (! $user) {
            $org->loadMissing('user');
            $user = $org->user;
        }
        if (! $user) {
            return;
        }

        app(\App\Services\PanelNotifier::class)->toUser(
            $user,
            'Payout sent',
            '$'.number_format($net, 2).' was recorded as paid to your organizer account.',
            'payout_paid',
            route('organizer.earnings'),
            ['organizer_id' => (string) $org->id],
        );
    }
}
