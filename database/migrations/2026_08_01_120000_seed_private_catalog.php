<?php

use Database\Seeders\CategorySeeder;
use Database\Seeders\InvitationDesignSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Ensure production gets private categories + built-in invitation designs
 * even when a previous deploy left those tables empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => CategorySeeder::class,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => InvitationDesignSeeder::class,
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Catalog seed is additive / idempotent; do not wipe admin data on rollback.
    }
};
