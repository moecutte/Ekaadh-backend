<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $publicRoot = Category::publicRoot();
        $privateRoot = Category::privateRoot();

        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();

        $filterChildren = function ($query) use ($search, $status) {
            if ($search !== '') {
                $query->where('name', 'like', "%{$search}%");
            }
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        };

        $publicChildren = collect();
        $privateChildren = collect();

        if ($publicRoot) {
            $q = Category::query()
                ->childrenOf($publicRoot->id)
                ->ordered()
                ->withCount(['events', 'invitationDesigns', 'privateEvents']);
            $filterChildren($q);
            $publicChildren = $q->get();
        }

        if ($privateRoot) {
            $q = Category::query()
                ->childrenOf($privateRoot->id)
                ->ordered()
                ->withCount(['events', 'invitationDesigns', 'privateEvents']);
            $filterChildren($q);
            $privateChildren = $q->get();
        }

        return view('admin.categories.index', [
            'publicRoot' => $publicRoot,
            'privateRoot' => $privateRoot,
            'publicChildren' => $publicChildren,
            'privateChildren' => $privateChildren,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $publicId = Category::publicRoot()?->id;
        $privateId = Category::privateRoot()?->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'parent_id' => ['required', 'integer', Rule::in(array_filter([$publicId, $privateId]))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'requires_couple_names' => ['nullable', 'boolean'],
        ]);

        $parentId = (int) $data['parent_id'];
        $isPrivate = $privateId && $parentId === (int) $privateId;
        $maxOrder = (int) Category::query()->where('parent_id', $parentId)->max('sort_order');

        Category::query()->create([
            'parent_id' => $parentId,
            'name' => trim($data['name']),
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 1),
            'is_active' => $request->boolean('is_active', true),
            'requires_couple_names' => $isPrivate && $request->boolean('requires_couple_names'),
        ]);

        return back()->with('success', 'Subcategory created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->assertEditable($category);

        $publicId = Category::publicRoot()?->id;
        $privateId = Category::privateRoot()?->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'parent_id' => ['required', 'integer', Rule::in(array_filter([$publicId, $privateId]))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'requires_couple_names' => ['nullable', 'boolean'],
        ]);

        $oldName = $category->name;
        $newName = trim($data['name']);
        $parentId = (int) $data['parent_id'];
        $isPrivate = $privateId && $parentId === (int) $privateId;

        $category->update([
            'parent_id' => $parentId,
            'name' => $newName,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'is_active' => $request->boolean('is_active'),
            'requires_couple_names' => $isPrivate && $request->boolean('requires_couple_names'),
        ]);

        if ($oldName !== $newName && ! $isPrivate) {
            Event::query()->where('category', $oldName)->update(['category' => $newName]);
        }

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->assertEditable($category);

        $publicEvents = Event::query()->where('category', $category->name)->count();
        $privateEvents = $category->privateEvents()->count();
        $designs = $category->invitationDesigns()->count();

        if ($publicEvents + $privateEvents + $designs > 0) {
            return back()->with(
                'error',
                "Cannot delete \"{$category->name}\" — it is still used by events or invite designs. Deactivate it instead."
            );
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function toggle(Category $category): RedirectResponse
    {
        $this->assertEditable($category);

        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', $category->is_active ? 'Category activated.' : 'Category deactivated.');
    }

    private function assertEditable(Category $category): void
    {
        if ($category->isRoot()) {
            throw ValidationException::withMessages([
                'category' => ['Public and Private parent categories cannot be edited or deleted.'],
            ]);
        }
    }
}
