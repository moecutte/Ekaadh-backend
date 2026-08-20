<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizer_packages', function (Blueprint $table) {
            $table->string('kind', 20)->default('organizer')->after('slug');
            $table->unsignedInteger('min_tickets_per_event')->nullable()->after('max_events_per_year');
            $table->index('kind');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('pricing_type', 20)->default('paid')->after('is_private');
            $table->foreignId('package_id')
                ->nullable()
                ->after('pricing_type')
                ->constrained('organizer_packages')
                ->nullOnDelete();
            $table->timestamp('package_paid_at')->nullable()->after('package_id');
            $table->index('pricing_type');
        });

        $now = now();
        $packages = [
            [
                'name' => 'Community 100',
                'slug' => 'community-100',
                'kind' => 'free_event',
                'description' => 'Free events with up to 100 complimentary tickets.',
                'commission_rate' => null,
                'billing_type' => 'per_event',
                'price' => 20.00,
                'max_events_per_year' => null,
                'min_tickets_per_event' => 1,
                'max_tickets_per_event' => 100,
                'features' => json_encode([
                    '1–100 free tickets',
                    'Guests claim tickets at no cost',
                    'Pay once per event',
                ]),
                'cta_label' => 'Choose 100 tickets',
                'is_highlighted' => false,
                'is_default' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'Standard 200',
                'slug' => 'standard-200',
                'kind' => 'free_event',
                'description' => 'Free events with 101–200 complimentary tickets.',
                'commission_rate' => null,
                'billing_type' => 'per_event',
                'price' => 40.00,
                'max_events_per_year' => null,
                'min_tickets_per_event' => 101,
                'max_tickets_per_event' => 200,
                'features' => json_encode([
                    '101–200 free tickets',
                    'Guests claim tickets at no cost',
                    'Pay once per event',
                ]),
                'cta_label' => 'Choose 200 tickets',
                'is_highlighted' => true,
                'is_default' => false,
                'sort_order' => 11,
            ],
            [
                'name' => 'Large 500',
                'slug' => 'large-500',
                'kind' => 'free_event',
                'description' => 'Free events with 201–500 complimentary tickets.',
                'commission_rate' => null,
                'billing_type' => 'per_event',
                'price' => 90.00,
                'max_events_per_year' => null,
                'min_tickets_per_event' => 201,
                'max_tickets_per_event' => 500,
                'features' => json_encode([
                    '201–500 free tickets',
                    'Guests claim tickets at no cost',
                    'Pay once per event',
                ]),
                'cta_label' => 'Choose 500 tickets',
                'is_highlighted' => false,
                'is_default' => false,
                'sort_order' => 12,
            ],
        ];

        foreach ($packages as $package) {
            $exists = DB::table('organizer_packages')->where('slug', $package['slug'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('organizer_packages')->insert([
                ...$package,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn(['pricing_type', 'package_paid_at']);
        });

        Schema::table('organizer_packages', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'min_tickets_per_event']);
        });
    }
};
