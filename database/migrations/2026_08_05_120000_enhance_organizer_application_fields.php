<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizer_profiles', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('business_phone');
            $table->text('business_description')->nullable()->after('city');
            $table->string('id_number', 80)->nullable()->after('business_description');
        });

        // Convert documents string path → JSON object for typed uploads.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE organizer_profiles MODIFY documents JSON NULL');
        } else {
            Schema::table('organizer_profiles', function (Blueprint $table) {
                $table->json('documents')->nullable()->change();
            });
        }

        // Wrap any legacy plain-string paths into the new shape.
        $rows = DB::table('organizer_profiles')
            ->whereNotNull('documents')
            ->get(['id', 'documents']);

        foreach ($rows as $row) {
            $raw = $row->documents;
            if (! is_string($raw) || $raw === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                continue;
            }

            DB::table('organizer_profiles')->where('id', $row->id)->update([
                'documents' => json_encode([
                    'id_type' => 'national_id',
                    'id_front' => $raw,
                    'id_back' => null,
                    'business_license' => null,
                ]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('organizer_profiles', function (Blueprint $table) {
            $table->dropColumn(['city', 'business_description', 'id_number']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE organizer_profiles MODIFY documents VARCHAR(255) NULL');
        }
    }
};
