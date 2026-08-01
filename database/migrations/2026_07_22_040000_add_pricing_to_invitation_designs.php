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

        if (! Schema::hasColumn('invitation_designs', 'ticket_price')) {
            Schema::table('invitation_designs', function (Blueprint $table) {
                $table->decimal('ticket_price', 8, 2)->nullable()->after('tier');
            });
        }

        if (! Schema::hasColumn('invitation_designs', 'premium_surcharge')) {
            Schema::table('invitation_designs', function (Blueprint $table) {
                $table->decimal('premium_surcharge', 8, 2)->nullable()->after('ticket_price');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invitation_designs')) {
            return;
        }

        Schema::table('invitation_designs', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('invitation_designs', 'ticket_price')) {
                $columns[] = 'ticket_price';
            }

            if (Schema::hasColumn('invitation_designs', 'premium_surcharge')) {
                $columns[] = 'premium_surcharge';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
