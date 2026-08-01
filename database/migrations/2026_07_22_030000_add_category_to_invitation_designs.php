<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invitation_designs')) {
            return;
        }

        if (Schema::hasColumn('invitation_designs', 'private_event_category_id')) {
            return;
        }

        $hasLegacyCategories = Schema::hasTable('private_event_categories');

        Schema::table('invitation_designs', function (Blueprint $table) use ($hasLegacyCategories) {
            if ($hasLegacyCategories) {
                $table->foreignId('private_event_category_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('private_event_categories')
                    ->nullOnDelete();
            } else {
                $table->unsignedBigInteger('private_event_category_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invitation_designs')) {
            return;
        }

        if (! Schema::hasColumn('invitation_designs', 'private_event_category_id')) {
            return;
        }

        Schema::table('invitation_designs', function (Blueprint $table) {
            $table->dropColumn('private_event_category_id');
        });
    }
};
