<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standard private tickets $0.80; premium designs $1.00 total ($0.20 surcharge).
        Setting::setValue('private_ticket_price', '0.8');
        Setting::setValue('private_premium_design_surcharge', '0.2');

        if (Schema::hasTable('invitation_designs')) {
            // Clear per-design overrides so platform defaults apply.
            DB::table('invitation_designs')->update([
                'ticket_price' => null,
                'premium_surcharge' => null,
            ]);
        }
    }

    public function down(): void
    {
        Setting::setValue('private_ticket_price', '5');
        Setting::setValue('private_premium_design_surcharge', '2');
    }
};
