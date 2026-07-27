<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaults = [
            'Hargeisa',
            'Mogadishu',
            'Borama',
            'Berbera',
            'Burao',
            'Gabiley',
            'Las Anod',
            'Erigavo',
            'Bosaso',
            'Garowe',
        ];

        $existing = DB::table('events')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->pluck('city')
            ->all();

        $names = collect(array_merge($defaults, $existing))
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique(fn ($n) => mb_strtolower($n))
            ->values();

        $now = now();
        foreach ($names as $index => $name) {
            DB::table('cities')->insert([
                'name' => $name,
                'slug' => Str::slug($name) ?: 'city-'.($index + 1),
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
