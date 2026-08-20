<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizerPackage;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizerPackageController extends Controller
{
    public const FRONT_VISIBILITY_KEY = 'show_organizer_packages_on_front';

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

        if ($kind = $request->string('kind')->toString()) {
            if (in_array($kind, [OrganizerPackage::KIND_ORGANIZER, OrganizerPackage::KIND_FREE_EVENT], true)) {
                $query->where('kind', $kind);
            }
        }

        $packages = $query->withCount(['organizers', 'events'])->paginate(20)->withQueryString();
        $showOnFront = filter_var(
            Setting::getValue(self::FRONT_VISIBILITY_KEY, '0'),
            FILTER_VALIDATE_BOOLEAN
        );

        return view('admin.packages.index', compact('packages', 'showOnFront'));
    }

    public function updateFrontVisibility(Request $request): RedirectResponse
    {
        $show = $request->boolean('show_on_front');
        Setting::setValue(self::FRONT_VISIBILITY_KEY, $show ? '1' : '0');

        return back()->with(
            'success',
            $show
                ? 'Pricing packages are now visible on the Create Event web page.'
                : 'Pricing packages are hidden from the Create Event web page.'
        );
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
        $eventsCount = $package->events()->count();

        if ($organizersCount > 0) {
            return back()->with('error', "Cannot delete \"{$package->name}\" — {$organizersCount} organizer(s) still use it. Deactivate it instead.");
        }

        if ($eventsCount > 0) {
            return back()->with('error', "Cannot delete \"{$package->name}\" — {$eventsCount} event(s) still use it. Deactivate it instead.");
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
            'kind' => ['required', Rule::in([OrganizerPackage::KIND_ORGANIZER, OrganizerPackage::KIND_FREE_EVENT])],
            'description' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'billing_type' => ['required', Rule::in(['free', 'per_event', 'monthly', 'custom'])],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'max_events_per_year' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'min_tickets_per_event' => ['nullable', 'integer', 'min:1', 'max:1000000'],
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
        $data['commission_rate'] = $this->nullableValue($data['commission_rate'] ?? null);
        $data['price'] = $this->nullableValue($data['price'] ?? null);
        $data['max_events_per_year'] = $this->nullableValue($data['max_events_per_year'] ?? null);
        $data['min_tickets_per_event'] = $this->nullableValue($data['min_tickets_per_event'] ?? null);
        $data['max_tickets_per_event'] = $this->nullableValue($data['max_tickets_per_event'] ?? null);
        $data['cta_label'] = $this->nullableValue($data['cta_label'] ?? null);

        if ($data['kind'] === OrganizerPackage::KIND_FREE_EVENT) {
            $data['billing_type'] = $data['billing_type'] ?? 'per_event';
            $data['commission_rate'] = null;
            $data['max_events_per_year'] = null;
            $data['is_default'] = false;
            $data['cta_label'] = null;
            if ($data['max_tickets_per_event'] === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'max_tickets_per_event' => ['Set a max ticket count for free-event packages.'],
                ]);
            }
            if ($data['price'] === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'price' => ['Set the package price organizers will pay.'],
                ]);
            }
            $min = $data['min_tickets_per_event'] !== null
                ? (int) $data['min_tickets_per_event']
                : null;
            $max = (int) $data['max_tickets_per_event'];
            if ($min && $min > $max) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'min_tickets_per_event' => ['Min tickets cannot be greater than max tickets.'],
                ]);
            }
        } else {
            $data['min_tickets_per_event'] = null;
        }

        if (($data['billing_type'] ?? null) === 'custom') {
            $data['price'] = null;
        } elseif ($data['price'] === null) {
            $data['price'] = 0;
        }

        return $data;
    }

    private function nullableValue(mixed $value): mixed
    {
        return $value === null || $value === '' ? null : $value;
    }
}
