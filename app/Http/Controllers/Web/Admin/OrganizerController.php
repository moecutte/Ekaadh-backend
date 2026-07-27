<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizerController extends Controller
{
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

        $organizers = $query->paginate(20)->withQueryString();
        $defaultRate = (float) Setting::getValue('default_commission_rate', 10);
        $packages = OrganizerPackage::query()->ordered()->get();

        return view('admin.organizers.index', compact('organizers', 'defaultRate', 'packages'));
    }

    public function show(OrganizerProfile $organizer): View
    {
        $organizer->load([
            'user',
            'package',
            'approver',
            'events' => fn ($q) => $q->latest()->limit(15),
            'payouts' => fn ($q) => $q->latest()->limit(10),
        ]);

        $eventIds = $organizer->events()->pluck('id');
        $defaultRate = (float) Setting::getValue('default_commission_rate', 10);
        $packages = OrganizerPackage::query()->ordered()->get();

        $stats = [
            'events' => $organizer->events()->count(),
            'published' => $organizer->events()->where('status', 'published')->count(),
            'gross' => $eventIds->isEmpty()
                ? 0.0
                : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->sum('subtotal'),
            'commission' => $eventIds->isEmpty()
                ? 0.0
                : (float) Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->sum('commission_amount'),
            'orders' => $eventIds->isEmpty()
                ? 0
                : Order::query()->whereIn('event_id', $eventIds)->where('status', 'paid')->count(),
        ];

        return view('admin.organizers.show', compact('organizer', 'defaultRate', 'stats', 'packages'));
    }

    public function approve(Request $request, OrganizerProfile $organizer): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'integer', Rule::exists('organizer_packages', 'id')->where('is_active', true)],
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

        return back()->with('success', "Rejected {$organizer->business_name}.");
    }

    public function updatePackage(Request $request, OrganizerProfile $organizer): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'integer', Rule::exists('organizer_packages', 'id')],
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
