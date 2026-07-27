<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CityController extends Controller
{
    public function index(Request $request): View
    {
        $query = City::query()->ordered();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        $cities = $query->withCount('events')->paginate(20)->withQueryString();

        return view('admin.cities.index', compact('cities'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:cities,name'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $maxOrder = (int) City::query()->max('sort_order');

        City::query()->create([
            'name' => trim($data['name']),
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'City created.');
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('cities', 'name')->ignore($city->id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldName = $city->name;
        $newName = trim($data['name']);

        $city->update([
            'name' => $newName,
            'sort_order' => $data['sort_order'] ?? $city->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($oldName !== $newName) {
            Event::query()->where('city', $oldName)->update(['city' => $newName]);
        }

        return back()->with('success', 'City updated.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $eventsCount = Event::query()->where('city', $city->name)->count();

        if ($eventsCount > 0) {
            return back()->with('error', "Cannot delete \"{$city->name}\" — {$eventsCount} event(s) still use it. Deactivate it instead.");
        }

        $city->delete();

        return back()->with('success', 'City deleted.');
    }

    public function toggle(City $city): RedirectResponse
    {
        $city->update(['is_active' => ! $city->is_active]);

        return back()->with('success', $city->is_active ? 'City activated.' : 'City deactivated.');
    }
}
