<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove previously seeded invitation designs so admins upload their own.
     */
    public function up(): void
    {
        if (! Schema::hasTable('invitation_designs')) {
            return;
        }

        if (Schema::hasColumn('events', 'invitation_design_id')) {
            DB::table('events')->update(['invitation_design_id' => null]);
        }

        if (Schema::hasTable('invitation_design_fields')) {
            DB::table('invitation_design_fields')->delete();
        }

        DB::table('invitation_designs')->delete();
    }

    public function down(): void
    {
        // Designs are admin-uploaded; nothing to restore.
    }
};
