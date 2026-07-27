<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('private_event_categories')) {
            return;
        }

        $now = now();
        $desired = [
            ['Aroos', true, 1],
            ['Meher', true, 2],
            ['Xaflad', false, 3],
            ['Casho', false, 4],
        ];

        // Soft-deactivate old English seeds that are being replaced.
        DB::table('private_event_categories')
            ->whereIn('slug', ['wedding', 'dinner'])
            ->update(['is_active' => false, 'updated_at' => $now]);

        foreach ($desired as [$name, $requiresCouple, $order]) {
            $slug = Str::slug($name);
            $existing = DB::table('private_event_categories')->where('slug', $slug)->first();

            if ($existing) {
                DB::table('private_event_categories')->where('id', $existing->id)->update([
                    'name' => $name,
                    'requires_couple_names' => $requiresCouple,
                    'sort_order' => $order,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            } else {
                // Prefer renaming Wedding → Aroos, Dinner → Casho when unused names.
                $renameFrom = match ($slug) {
                    'aroos' => 'wedding',
                    'casho' => 'dinner',
                    default => null,
                };

                if ($renameFrom) {
                    $old = DB::table('private_event_categories')->where('slug', $renameFrom)->first();
                    if ($old) {
                        DB::table('private_event_categories')->where('id', $old->id)->update([
                            'name' => $name,
                            'slug' => $slug,
                            'requires_couple_names' => $requiresCouple,
                            'sort_order' => $order,
                            'is_active' => true,
                            'updated_at' => $now,
                        ]);
                        continue;
                    }
                }

                DB::table('private_event_categories')->insert([
                    'name' => $name,
                    'slug' => $slug,
                    'requires_couple_names' => $requiresCouple,
                    'sort_order' => $order,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
