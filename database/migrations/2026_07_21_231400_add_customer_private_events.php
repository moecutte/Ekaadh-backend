<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('organizer_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Make organizer_id nullable (MySQL).
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('events', function (Blueprint $table) {
                $table->unsignedBigInteger('organizer_id')->nullable()->change();
            });
        } else {
            DB::statement('ALTER TABLE events MODIFY organizer_id BIGINT UNSIGNED NULL');
        }

        Schema::table('events', function (Blueprint $table) {
            $table->foreign('organizer_id')
                ->references('id')
                ->on('organizer_profiles')
                ->nullOnDelete();
        });

        $now = now();
        foreach (
            [
                'private_ticket_price' => '5',
                'private_ticket_max' => '500',
            ] as $key => $value
        ) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['private_ticket_price', 'private_ticket_max'])->delete();

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropColumn('owner_user_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
        });

        DB::statement('ALTER TABLE events MODIFY organizer_id BIGINT UNSIGNED NOT NULL');

        Schema::table('events', function (Blueprint $table) {
            $table->foreign('organizer_id')
                ->references('id')
                ->on('organizer_profiles')
                ->cascadeOnDelete();
        });
    }
};
