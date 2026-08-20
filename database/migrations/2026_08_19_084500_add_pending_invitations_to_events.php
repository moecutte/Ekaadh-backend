<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('events', 'pending_invitations')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $column = $table->json('pending_invitations')->nullable();
            if (Schema::hasColumn('events', 'invitation_field_values')) {
                $column->after('invitation_field_values');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('pending_invitations');
        });
    }
};
