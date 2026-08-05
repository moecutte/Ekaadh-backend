<?php

namespace Database\Seeders;

use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OrganizerPackageSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('organizer_packages')) {
            return;
        }

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
                'features' => [
                    'Up to 3 free events per year',
                    'Up to 200 tickets per event',
                    'Zaad & eDahab payouts',
                    'Basic attendee check-in',
                    'Email support',
                ],
                'cta_label' => 'Get Started Free',
                'is_highlighted' => false,
                'is_default' => true,
                'sort_order' => 1,
                'is_active' => true,
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
                'features' => [
                    'Unlimited events',
                    'Unlimited ticket capacity',
                    'Priority listing on homepage',
                    'Real-time sales dashboard',
                    'Custom ticket types & pricing',
                    'Branded confirmation messages',
                    'Priority support',
                ],
                'cta_label' => 'Start Pro Trial',
                'is_highlighted' => true,
                'is_default' => false,
                'sort_order' => 2,
                'is_active' => true,
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
                'features' => [
                    'Everything in Pro',
                    'Dedicated account manager',
                    'White-label ticket pages',
                    'API access & integrations',
                    'On-site scanning equipment',
                    'Revenue share negotiation',
                    'SLA guarantee',
                ],
                'cta_label' => 'Contact Sales',
                'is_highlighted' => false,
                'is_default' => false,
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            OrganizerPackage::query()->updateOrCreate(
                ['slug' => $package['slug']],
                $package
            );
        }

        $defaultId = OrganizerPackage::defaultPackage()?->id;
        if ($defaultId) {
            OrganizerProfile::query()
                ->whereNull('package_id')
                ->update(['package_id' => $defaultId]);
        }
    }
}
