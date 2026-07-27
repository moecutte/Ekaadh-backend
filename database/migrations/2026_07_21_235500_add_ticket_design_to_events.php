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
            $table->string('ticket_design', 40)->nullable()->after('is_private');
        });

        if (! DB::table('settings')->where('key', 'private_premium_design_surcharge')->exists()) {
            DB::table('settings')->insert([
                'key' => 'private_premium_design_surcharge',
                'value' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'private_premium_design_surcharge')->delete();

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('ticket_design');
        });
    }
};
