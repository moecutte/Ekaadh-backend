<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizerPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizerPackageController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrganizerPackage::query()->ordered();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        $packages = $query->withCount('organizers')->paginate(20)->withQueryString();

        return view('admin.packages.index', compact('packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $maxOrder = (int) OrganizerPackage::query()->max('sort_order');

        OrganizerPackage::query()->create([
            ...$data,
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 1),
            'is_active' => $request->boolean('is_active', true),
            'is_highlighted' => $request->boolean('is_highlighted'),
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Pricing package created.');
    }

    public function update(Request $request, OrganizerPackage $package): RedirectResponse
    {
        $data = $this->validated($request, $package);

        $package->update([
            ...$data,
            'sort_order' => $data['sort_order'] ?? $package->sort_order,
            'is_active' => $request->boolean('is_active'),
            'is_highlighted' => $request->boolean('is_highlighted'),
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Pricing package updated.');
    }

    public function destroy(OrganizerPackage $package): RedirectResponse
    {
        $organizersCount = $package->organizers()->count();

        if ($organizersCount > 0) {
            return back()->with('error', "Cannot delete \"{$package->name}\" — {$organizersCount} organizer(s) still use it. Deactivate it instead.");
        }

        if ($package->is_default) {
            return back()->with('error', 'Cannot delete the default package. Set another package as default first.');
        }

        $package->delete();

        return back()->with('success', 'Pricing package deleted.');
    }

    public function toggle(OrganizerPackage $package): RedirectResponse
    {
        if ($package->is_default && $package->is_active) {
            return back()->with('error', 'Cannot deactivate the default package. Set another package as default first.');
        }

        $package->update(['is_active' => ! $package->is_active]);

        return back()->with('success', $package->is_active ? 'Package activated.' : 'Package deactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?OrganizerPackage $package = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('organizer_packages', 'name')->ignore($package?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'billing_type' => ['required', Rule::in(['free', 'per_event', 'monthly', 'custom'])],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'max_events_per_year' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'max_tickets_per_event' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'features_text' => ['nullable', 'string', 'max:5000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'is_highlighted' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $features = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        unset($data['features_text']);

        $data['features'] = $features;
        $data['commission_rate'] = ($data['commission_rate'] === null || $data['commission_rate'] === '')
            ? null
            : $data['commission_rate'];

        if ($data['billing_type'] === 'custom') {
            $data['price'] = null;
        } elseif ($data['price'] === null || $data['price'] === '') {
            $data['price'] = 0;
        }

        return $data;
    }
}
