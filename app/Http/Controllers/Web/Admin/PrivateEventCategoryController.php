<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateEventCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrivateEventCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = PrivateEventCategory::query()->ordered();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        $categories = $query->withCount('events')->paginate(20)->withQueryString();

        return view('admin.private-event-categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:private_event_categories,name'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'requires_couple_names' => ['nullable', 'boolean'],
        ]);

        $maxOrder = (int) PrivateEventCategory::query()->max('sort_order');

        PrivateEventCategory::query()->create([
            'name' => trim($data['name']),
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 1),
            'is_active' => $request->boolean('is_active', true),
            'requires_couple_names' => $request->boolean('requires_couple_names'),
        ]);

        return back()->with('success', 'Private event category created.');
    }

    public function update(Request $request, PrivateEventCategory $privateEventCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('private_event_categories', 'name')->ignore($privateEventCategory->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'requires_couple_names' => ['nullable', 'boolean'],
        ]);

        $privateEventCategory->update([
            'name' => trim($data['name']),
            'sort_order' => $data['sort_order'] ?? $privateEventCategory->sort_order,
            'is_active' => $request->boolean('is_active'),
            'requires_couple_names' => $request->boolean('requires_couple_names'),
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(PrivateEventCategory $privateEventCategory): RedirectResponse
    {
        $eventsCount = $privateEventCategory->events()->count();

        if ($eventsCount > 0) {
            return back()->with(
                'error',
                "Cannot delete \"{$privateEventCategory->name}\" — {$eventsCount} event(s) still use it. Deactivate it instead."
            );
        }

        $privateEventCategory->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function toggle(PrivateEventCategory $privateEventCategory): RedirectResponse
    {
        $privateEventCategory->update(['is_active' => ! $privateEventCategory->is_active]);

        return back()->with(
            'success',
            $privateEventCategory->is_active ? 'Category activated.' : 'Category deactivated.'
        );
    }
}
