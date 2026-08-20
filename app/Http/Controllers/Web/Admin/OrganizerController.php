<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\PaginatesFilteredLists;
use App\Models\Order;
use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use App\Models\Setting;
use App\Services\PanelNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizerController extends Controller
{
    use PaginatesFilteredLists;

    public function index(Request $request): View
    {
        $query = OrganizerProfile::query()->with(['user', 'package'])->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('approval_status', $status);
        }

        if ($packageId = $request->integer('package_id')) {
            $query->where('package_id', $packageId);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $organizers = $query->paginate($this->resolvePerPage($request))->withQueryString();
        $defaultRate = (float) Setting::getValue('default_commission_rate', 10);
        $packages = OrganizerPackage::query()->organizerPlans()->ordered()->get();

        return view('admin.organizers.index', compact('organizers', 'defaultRate', 'packages'));
    }

    public function show(OrganizerProfile $organizer): View
    {
        $organizer->load(['user', 'package', 'approver']);

        $events = $organizer->events()
            ->latest()
            ->paginate(10, ['*'], 'events_page')
            ->withQueryString()
            ->fragment('organizer-events');

        $payouts = $organizer->payouts()
            ->latest()
            ->paginate(8, ['*'], 'payouts_page')
            ->withQueryString()
            ->fragment('organizer-payouts');

        $eventIds = $organizer->events()->pluck('id');
        $defaultRate = (float) Setting::getValue('default_commission_rate', 10);
        $packages = OrganizerPackage::query()->organizerPlans()->ordered()->get();

        $stats = [
            'events' => $organizer->events()->count(),
            'published' => $organizer->events()->where('status', 'published')->count(),
            'gross' => $eventIds->isEmpty()
                ? 0.0
                : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->ticketSales()->sum('subtotal'),
            'commission' => $eventIds->isEmpty()
                ? 0.0
                : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->ticketSales()->sum('commission_amount'),
            'orders' => $eventIds->isEmpty()
                ? 0
                : Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->ticketSales()->count(),
        ];

        return view('admin.organizers.show', compact('organizer', 'defaultRate', 'stats', 'packages', 'events', 'payouts'));
    }

    public function approve(Request $request, OrganizerProfile $organizer): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'integer', Rule::exists('organizer_packages', 'id')->where('is_active', true)->where('kind', OrganizerPackage::KIND_ORGANIZER)],
        ]);

        $packageId = $data['package_id']
            ?? $organizer->package_id
            ?? OrganizerPackage::defaultPackage()?->id;

        $organizer->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => null,
            'package_id' => $packageId,
        ]);

        $organizer->loadMissing('user');

        if ($user = $organizer->user) {
            app(PanelNotifier::class)->toUser(
                $user,
                'Organizer account approved',
                'You can now create and manage public events on Ekaadh.',
                'organizer_approved',
                route('organizer.dashboard'),
                ['organizer_id' => (string) $organizer->id],
            );
        }

        return back()->with('success', "Approved {$organizer->business_name}.");
    }

    public function reject(Request $request, OrganizerProfile $organizer): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $organizer->update([
            'approval_status' => 'rejected',
            'approved_at' => null,
            'approved_by' => auth()->id(),
            'rejection_reason' => $data['rejection_reason'] ?? 'Application rejected by admin.',
        ]);

        $organizer->loadMissing('user');

        if ($user = $organizer->user) {
            $reason = $organizer->rejection_reason ?: 'Your organizer application was rejected.';
            app(PanelNotifier::class)->toUser(
                $user,
                'Organizer application rejected',
                $reason,
                'organizer_rejected',
                route('organizer.application.edit'),
                ['organizer_id' => (string) $organizer->id],
            );
        }

        return back()->with('success', "Rejected {$organizer->business_name}.");
    }

    public function updatePackage(Request $request, OrganizerProfile $organizer): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'integer', Rule::exists('organizer_packages', 'id')->where('kind', OrganizerPackage::KIND_ORGANIZER)],
        ]);

        $organizer->update([
            'package_id' => $data['package_id'] ?: null,
        ]);

        return back()->with('success', 'Organizer package updated.');
    }

    public function updateCommission(Request $request, OrganizerProfile $organizer): RedirectResponse
    {
        $data = $request->validate([
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $organizer->update([
            'commission_rate' => $data['commission_rate'] === null || $data['commission_rate'] === ''
                ? null
                : $data['commission_rate'],
        ]);

        return back()->with('success', 'Commission override updated.');
    }
}
