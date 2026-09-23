<?php

use App\Models\OrganizerPackage;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Setting::setValue('default_commission_rate', '5');
        Setting::setValue('free_ticket_organizer_fee', '0.25');

        if (Schema::hasTable('organizer_packages')) {
            OrganizerPackage::query()
                ->where('kind', OrganizerPackage::KIND_FREE_EVENT)
                ->update(['is_active' => false]);

            OrganizerPackage::query()
                ->where('kind', OrganizerPackage::KIND_ORGANIZER)
                ->update(['commission_rate' => 5]);
        }

        if (Schema::hasTable('organizer_profiles') && Schema::hasColumn('organizer_profiles', 'commission_rate')) {
            // Clear per-organizer overrides so priced public events use the 5% platform rate.
            DB::table('organizer_profiles')->update(['commission_rate' => null]);
        }

        if (Schema::hasTable('events')) {
            DB::table('events')
                ->where('pricing_type', 'free')
                ->where('is_private', false)
                ->update([
                    'package_id' => null,
                    'package_paid_at' => null,
                ]);
        }
    }

    public function down(): void
    {
        Setting::setValue('default_commission_rate', '10');
        Setting::setValue('free_ticket_organizer_fee', '0.25');

        if (Schema::hasTable('organizer_packages')) {
            OrganizerPackage::query()
                ->where('kind', OrganizerPackage::KIND_FREE_EVENT)
                ->update(['is_active' => true]);
        }
    }
};
