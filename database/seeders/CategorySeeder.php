<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        $now = now();

        $publicId = $this->ensureRoot('Public', 'public', 0, $now);
        $privateId = $this->ensureRoot('Private', 'private', 1, $now);

        // Flat legacy / public event categories become Public children.
        $publicDefaults = ['Music', 'Sports', 'Comedy', 'Tech', 'Food', 'Business', 'Culture', 'Education'];
        foreach ($publicDefaults as $index => $name) {
            $this->ensureChild($publicId, $name, Str::slug($name), false, $index + 1, $now);
        }

        Category::query()
            ->whereNull('parent_id')
            ->whereNotIn('id', [$publicId, $privateId])
            ->update(['parent_id' => $publicId]);

        $privateDefaults = [
            ['Aroos', true, 1],
            ['Meher', true, 2],
            ['Xaflad', false, 3],
            ['Casho', false, 4],
        ];

        foreach ($privateDefaults as [$name, $requiresCouple, $order]) {
            $this->ensureChild($privateId, $name, Str::slug($name), $requiresCouple, $order, $now);
        }
    }

    private function ensureRoot(string $name, string $slug, int $sortOrder, $now): int
    {
        $existing = Category::query()->where('slug', $slug)->whereNull('parent_id')->first();
        if ($existing) {
            $existing->update([
                'name' => $name,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);

            return (int) $existing->id;
        }

        return (int) Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => null,
            'requires_couple_names' => false,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->id;
    }

    private function ensureChild(
        int $parentId,
        string $name,
        string $slug,
        bool $requiresCouple,
        int $sortOrder,
        $now
    ): void {
        $existing = Category::query()
            ->where('parent_id', $parentId)
            ->where(function ($q) use ($slug, $name) {
                $q->where('slug', $slug)->orWhere('name', $name);
            })
            ->first();

        if ($existing) {
            $existing->update([
                'name' => $name,
                'slug' => $slug,
                'requires_couple_names' => $requiresCouple,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);

            return;
        }

        // Avoid unique name/slug collisions with Public siblings.
        $finalSlug = $slug;
        $i = 1;
        while (Category::query()->where('slug', $finalSlug)->exists()) {
            $finalSlug = $slug.'-'.$i++;
        }

        $finalName = $name;
        $n = 1;
        while (Category::query()->where('name', $finalName)->exists()) {
            $finalName = $name.' '.$n++;
        }

        Category::query()->create([
            'parent_id' => $parentId,
            'name' => $finalName,
            'slug' => $finalSlug,
            'requires_couple_names' => $requiresCouple,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
