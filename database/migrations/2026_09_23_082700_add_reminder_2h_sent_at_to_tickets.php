<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tickets', 'reminder_2h_sent_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->timestamp('reminder_2h_sent_at')->nullable()->after('reminder_24h_sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'reminder_2h_sent_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('reminder_2h_sent_at');
            });
        }
    }
};
