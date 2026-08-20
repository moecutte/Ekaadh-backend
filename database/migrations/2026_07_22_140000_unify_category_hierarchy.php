<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'parent_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('categories', 'requires_couple_names')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('requires_couple_names')->default(false)->after('slug');
            });
        }

        $now = now();

        $publicId = DB::table('categories')->where('slug', 'public')->whereNull('parent_id')->value('id');
        if (! $publicId) {
            $publicId = DB::table('categories')->insertGetId([
                'name' => 'Public',
                'slug' => 'public',
                'parent_id' => null,
                'requires_couple_names' => false,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $privateId = DB::table('categories')->where('slug', 'private')->whereNull('parent_id')->value('id');
        if (! $privateId) {
            $privateId = DB::table('categories')->insertGetId([
                'name' => 'Private',
                'slug' => 'private',
                'parent_id' => null,
                'requires_couple_names' => false,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Existing flat categories become Public children (exclude roots).
        DB::table('categories')
            ->whereNull('parent_id')
            ->whereNotIn('id', [$publicId, $privateId])
            ->update(['parent_id' => $publicId]);

        $map = [];

        if (Schema::hasTable('private_event_categories')) {
            $rows = DB::table('private_event_categories')->orderBy('sort_order')->orderBy('id')->get();

            foreach ($rows as $row) {
                // Prefer an already-migrated child with the same slug under Private.
                $existing = DB::table('categories')
                    ->where('parent_id', $privateId)
                    ->where(function ($q) use ($row) {
                        $q->where('slug', $row->slug)->orWhere('name', $row->name);
                    })
                    ->first();

                if ($existing) {
                    $map[(int) $row->id] = (int) $existing->id;
                    continue;
                }

                $slug = $row->slug ?: ('private-'.$row->id);
                $baseSlug = $slug;
                $i = 1;
                while (DB::table('categories')->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$i++;
                }

                $name = $row->name;
                $baseName = $name;
                $n = 1;
                while (DB::table('categories')->where('name', $name)->exists()) {
                    $name = $baseName.' '.$n++;
                }

                $newId = DB::table('categories')->insertGetId([
                    'parent_id' => $privateId,
                    'name' => $name,
                    'slug' => $slug,
                    'requires_couple_names' => (bool) ($row->requires_couple_names ?? false),
                    'sort_order' => (int) ($row->sort_order ?? 0),
                    'is_active' => (bool) ($row->is_active ?? true),
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $now,
                ]);

                $map[(int) $row->id] = $newId;
            }
        }

        // Point FKs at unified categories (Private children), not private_event_categories.
        $this->repointForeignKeys($map);
    }

    /**
     * @param  array<int, int>  $map
     */
    private function repointForeignKeys(array $map): void
    {
        $this->dropFkIfExists('events', 'events_private_event_category_id_foreign');
        $this->dropFkIfExists('invitation_designs', 'invitation_designs_private_event_category_id_foreign');

        if ($map !== []) {
            if (Schema::hasColumn('events', 'private_event_category_id')) {
                foreach ($map as $oldId => $newId) {
                    DB::table('events')
                        ->where('private_event_category_id', $oldId)
                        ->update(['private_event_category_id' => $newId]);
                }
            }

            if (Schema::hasColumn('invitation_designs', 'private_event_category_id')) {
                foreach ($map as $oldId => $newId) {
                    DB::table('invitation_designs')
                        ->where('private_event_category_id', $oldId)
                        ->update(['private_event_category_id' => $newId]);
                }
            }
        }

        if (Schema::hasColumn('events', 'private_event_category_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->foreign('private_event_category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('invitation_designs', 'private_event_category_id')) {
            Schema::table('invitation_designs', function (Blueprint $table) {
                $table->foreign('private_event_category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            });
        }
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        if (! Schema::hasTable($table)) {
            return;
        }

        $db = DB::getDatabaseName();
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $blueprint) use ($constraint) {
                $blueprint->dropForeign($constraint);
            });
        }
    }

    public function down(): void
    {
        $this->dropFkIfExists('events', 'events_private_event_category_id_foreign');
        $this->dropFkIfExists('invitation_designs', 'invitation_designs_private_event_category_id_foreign');

        if (Schema::hasColumn('events', 'private_event_category_id') && Schema::hasTable('private_event_categories')) {
            Schema::table('events', function (Blueprint $table) {
                $table->foreign('private_event_category_id')
                    ->references('id')
                    ->on('private_event_categories')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('invitation_designs', 'private_event_category_id') && Schema::hasTable('private_event_categories')) {
            Schema::table('invitation_designs', function (Blueprint $table) {
                $table->foreign('private_event_category_id')
                    ->references('id')
                    ->on('private_event_categories')
                    ->nullOnDelete();
            });
        }

        $publicId = DB::table('categories')->where('slug', 'public')->whereNull('parent_id')->value('id');
        $privateId = DB::table('categories')->where('slug', 'private')->whereNull('parent_id')->value('id');

        if ($privateId) {
            DB::table('categories')->where('parent_id', $privateId)->delete();
        }

        if ($publicId) {
            DB::table('categories')->where('parent_id', $publicId)->update(['parent_id' => null]);
        }

        DB::table('categories')->whereIn('slug', ['public', 'private'])->whereNull('parent_id')->delete();

        if (Schema::hasColumn('categories', 'parent_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
            });
        }

        if (Schema::hasColumn('categories', 'requires_couple_names')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('requires_couple_names');
            });
        }
    }
};
