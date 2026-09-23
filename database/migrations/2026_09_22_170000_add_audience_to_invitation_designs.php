<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_designs', function (Blueprint $table) {
            if (! Schema::hasColumn('invitation_designs', 'audience')) {
                $table->string('audience', 40)->default('private_invitation')->after('private_event_category_id');
                $table->index('audience');
            }
            if (! Schema::hasColumn('invitation_designs', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
        });

        DB::table('invitation_designs')
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', '');
            })
            ->update(['audience' => 'private_invitation']);

        $existsPublic = DB::table('invitation_designs')->where('audience', 'public_event')->exists();
        if (! $existsPublic) {
            DB::table('invitation_designs')->insert([
                'private_event_category_id' => null,
                'audience' => 'public_event',
                'name' => 'Ekaadh Classic',
                'slug' => 'public-ekaadh-classic',
                'description' => 'Default ticket design for public events.',
                'tier' => 'standard',
                'render_mode' => 'blade',
                'blade_key' => 'public',
                'accent' => '#323891',
                'accent_soft' => '#eef0f8',
                'header_from' => '#0f1a2e',
                'header_to' => '#323891',
                'card_bg' => '#ffffff',
                'text_color' => '#0f1a2e',
                'muted_color' => '#64748b',
                'border_color' => '#e2e8f0',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('invitation_designs', function (Blueprint $table) {
            if (Schema::hasColumn('invitation_designs', 'is_default')) {
                $table->dropColumn('is_default');
            }
            if (Schema::hasColumn('invitation_designs', 'audience')) {
                $table->dropIndex(['audience']);
                $table->dropColumn('audience');
            }
        });
    }
};
