<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_designs', function (Blueprint $table) {
            $table->decimal('ticket_price', 8, 2)->nullable()->after('tier');
            $table->decimal('premium_surcharge', 8, 2)->nullable()->after('ticket_price');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_designs', function (Blueprint $table) {
            $table->dropColumn(['ticket_price', 'premium_surcharge']);
        });
    }
};
