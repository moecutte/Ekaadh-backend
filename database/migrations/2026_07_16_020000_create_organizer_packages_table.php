<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->string('billing_type', 20)->default('free');
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('max_events_per_year')->nullable();
            $table->unsignedInteger('max_tickets_per_event')->nullable();
            $table->json('features')->nullable();
            $table->string('cta_label', 80)->nullable();
            $table->boolean('is_highlighted')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('organizer_profiles', function (Blueprint $table) {
            $table->foreignId('package_id')
                ->nullable()
                ->after('commission_rate')
                ->constrained('organizer_packages')
                ->nullOnDelete();
        });

        $now = now();
        $packages = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'For small community events and first-time organisers.',
                'commission_rate' => 12.00,
                'billing_type' => 'free',
                'price' => 0,
                'max_events_per_year' => 3,
                'max_tickets_per_event' => 200,
                'features' => json_encode([
                    'Up to 3 free events per year',
                    'Up to 200 tickets per event',
                    'Zaad & eDahab payouts',
                    'Basic attendee check-in',
                    'Email support',
                ]),
                'cta_label' => 'Get Started Free',
                'is_highlighted' => false,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For professional organisers who need full control and analytics.',
                'commission_rate' => 10.00,
                'billing_type' => 'per_event',
                'price' => 29.00,
                'max_events_per_year' => null,
                'max_tickets_per_event' => null,
                'features' => json_encode([
                    'Unlimited events',
                    'Unlimited ticket capacity',
                    'Priority listing on homepage',
                    'Real-time sales dashboard',
                    'Custom ticket types & pricing',
                    'Branded confirmation messages',
                    'Priority support',
                ]),
                'cta_label' => 'Start Pro Trial',
                'is_highlighted' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For festivals, stadiums, and large-scale recurring events.',
                'commission_rate' => 7.00,
                'billing_type' => 'custom',
                'price' => null,
                'max_events_per_year' => null,
                'max_tickets_per_event' => null,
                'features' => json_encode([
                    'Everything in Pro',
                    'Dedicated account manager',
                    'White-label ticket pages',
                    'API access & integrations',
                    'On-site scanning equipment',
                    'Revenue share negotiation',
                    'SLA guarantee',
                ]),
                'cta_label' => 'Contact Sales',
                'is_highlighted' => false,
                'is_default' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            DB::table('organizer_packages')->insert([
                ...$package,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $freeId = DB::table('organizer_packages')->where('slug', 'free')->value('id');
        if ($freeId) {
            DB::table('organizer_profiles')->whereNull('package_id')->update(['package_id' => $freeId]);
        }
    }

    public function down(): void
    {
        Schema::table('organizer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });

        Schema::dropIfExists('organizer_packages');
    }
};
