<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_designs', function (Blueprint $table) {
            $table->foreignId('private_event_category_id')
                ->nullable()
                ->after('id')
                ->constrained('private_event_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invitation_designs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('private_event_category_id');
        });
    }
};
