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
                'kind' => OrganizerPackage::KIND_ORGANIZER,
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
                'kind' => OrganizerPackage::KIND_ORGANIZER,
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
                'kind' => OrganizerPackage::KIND_ORGANIZER,
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
            [
                'name' => 'Community 100',
                'slug' => 'community-100',
                'kind' => OrganizerPackage::KIND_FREE_EVENT,
                'description' => 'Free events with up to 100 complimentary tickets.',
                'commission_rate' => null,
                'billing_type' => 'per_event',
                'price' => 20.00,
                'max_events_per_year' => null,
                'min_tickets_per_event' => 1,
                'max_tickets_per_event' => 100,
                'features' => [
                    '1–100 free tickets',
                    'Guests claim tickets at no cost',
                    'Pay once per event',
                ],
                'cta_label' => null,
                'is_highlighted' => false,
                'is_default' => false,
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Standard 200',
                'slug' => 'standard-200',
                'kind' => OrganizerPackage::KIND_FREE_EVENT,
                'description' => 'Free events with 101–200 complimentary tickets.',
                'commission_rate' => null,
                'billing_type' => 'per_event',
                'price' => 40.00,
                'max_events_per_year' => null,
                'min_tickets_per_event' => 101,
                'max_tickets_per_event' => 200,
                'features' => [
                    '101–200 free tickets',
                    'Guests claim tickets at no cost',
                    'Pay once per event',
                ],
                'cta_label' => null,
                'is_highlighted' => true,
                'is_default' => false,
                'sort_order' => 11,
                'is_active' => true,
            ],
            [
                'name' => 'Large 500',
                'slug' => 'large-500',
                'kind' => OrganizerPackage::KIND_FREE_EVENT,
                'description' => 'Free events with 201–500 complimentary tickets.',
                'commission_rate' => null,
                'billing_type' => 'per_event',
                'price' => 90.00,
                'max_events_per_year' => null,
                'min_tickets_per_event' => 201,
                'max_tickets_per_event' => 500,
                'features' => [
                    '201–500 free tickets',
                    'Guests claim tickets at no cost',
                    'Pay once per event',
                ],
                'cta_label' => null,
                'is_highlighted' => false,
                'is_default' => false,
                'sort_order' => 12,
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
