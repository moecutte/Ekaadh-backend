<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizerUser = User::query()->updateOrCreate(
            ['email' => 'organizer@ekaadh.com'],
            [
                'name' => 'Horizon Events',
                'phone' => '+252630000010',
                'password' => 'password',
                'role' => User::ROLE_ORGANIZER,
                'status' => 'active',
            ]
        );

        $defaultPackageId = OrganizerPackage::defaultPackage()?->id;

        $profile = OrganizerProfile::query()->updateOrCreate(
            ['user_id' => $organizerUser->id],
            [
                'business_name' => 'Horizon Events',
                'business_phone' => '+252630000010',
                'commission_rate' => null,
                'package_id' => $defaultPackageId,
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]
        );

        $events = [
            [
                'title' => 'Book Hosting in Hargeisa — Beyond Horizons',
                'category' => 'Culture',
                'venue' => 'Hargeisa Cultural Centre (Behind Hargeisa Library)',
                'city' => 'Hargeisa',
                'event_date' => '2026-08-24',
                'event_time' => '16:00:00',
                'is_featured' => true,
                'cover_image' => 'images/events/book-hosting-hargeisa.jpg',
                'description' => "You are invited to a Book Hosting Event in Hargeisa celebrating Beyond Horizons: Stories of Resilience and Hope.\n\nCelebrating stories. Inspiring minds.\n\nWhat to expect:\n• Book Presentation\n• Author Discussion\n• Book Signing\n• Networking & Refreshments\n\nOrganized by Hargeisa Readers Club. Let's read. Let's grow. Let's inspire Hargeisa.",
                'tickets' => [
                    ['name' => 'General Admission', 'description' => 'Entry to the book hosting event', 'price' => 5, 'qty' => 200],
                    ['name' => 'VIP Reader', 'description' => 'Priority seating + signed copy', 'price' => 20, 'qty' => 40],
                ],
            ],
            [
                'title' => 'Tech Conference Hargeisa 2026',
                'category' => 'Tech',
                'venue' => 'Hargeisa Cultural Centre',
                'city' => 'Hargeisa',
                'event_date' => '2026-08-15',
                'event_time' => '09:00:00',
                'is_featured' => true,
                'cover_image' => 'images/events/tech-conference-hargeisa.jpg',
                'description' => "Part of Hargeisa Innovation Week 2026.\n\nInnovate. Connect. Transform.\n\nBringing together developers, entrepreneurs, investors and tech leaders to shape the future of Somalia.\n\nFeatures:\n• Keynote Speakers\n• Panel Discussions\n• Startup Showcase\n• Networking Opportunities\n• Exhibition & Tech Demos\n\n#InnovateHargeisa",
                'tickets' => [
                    ['name' => 'Standard Pass', 'description' => 'Full conference access (2 days)', 'price' => 25, 'qty' => 500],
                    ['name' => 'VIP Pass', 'description' => 'Includes lunch & networking lounge', 'price' => 75, 'qty' => 80],
                ],
            ],
            [
                'title' => 'Hargeisa Food Festival',
                'category' => 'Food',
                'venue' => 'Hargeisa Cultural Centre',
                'city' => 'Hargeisa',
                'event_date' => '2026-08-07',
                'event_time' => '10:00:00',
                'is_featured' => true,
                'cover_image' => 'images/events/hargeisa-food-festival.jpg',
                'description' => "A celebration of flavors, culture & community.\n\nEveryone is welcome!\n\nFeatures:\n• Local Cuisine\n• Live Cooking\n• Food Stalls\n• Family Fun\n• Live Music\n\n@hargeisafoodfestival",
                'tickets' => [
                    ['name' => 'General Admission', 'description' => 'Festival entry', 'price' => 5, 'qty' => 2000],
                    ['name' => 'Family Pack', 'description' => 'Entry for 4 people', 'price' => 15, 'qty' => 300],
                ],
            ],
            [
                'title' => 'Hargeisa Art Exhibition',
                'category' => 'Culture',
                'venue' => 'Hargeisa Cultural Centre Art Gallery',
                'city' => 'Hargeisa',
                'event_date' => '2026-08-21',
                'event_time' => '09:00:00',
                'is_featured' => false,
                'cover_image' => 'images/events/hargeisa-art-exhibition.jpg',
                'description' => "Express. Inspire. Celebrate.\n\nSupport local talent. Build creative community.\n\nFeatures:\n• Visual Arts\n• Photography\n• Sculpture\n• Live Painting\n• Workshops",
                'tickets' => [
                    ['name' => 'General Admission', 'description' => 'Gallery entry', 'price' => 8, 'qty' => 400],
                    ['name' => 'Workshop Pass', 'description' => 'Exhibition + workshop access', 'price' => 25, 'qty' => 60],
                ],
            ],
            [
                'title' => 'Hargeisa Youth Leadership Summit 2026',
                'category' => 'Education',
                'venue' => 'Hargeisa Cultural Centre Conference Hall',
                'city' => 'Hargeisa',
                'event_date' => '2026-08-14',
                'event_time' => '09:00:00',
                'is_featured' => true,
                'cover_image' => 'images/events/youth-leadership-summit.jpg',
                'description' => "Youth. Lead. Impact.\n\nEmpowering young minds to build a better future.\nBe the change. Lead today!\n\nFeatures:\n• Inspiring Speakers\n• Leadership Workshops\n• Skills Development\n• Networking & Mentorship\n• Youth Panel\n\n#LeadHargeisa",
                'tickets' => [
                    ['name' => 'Youth Pass', 'description' => 'Full summit access (ages 16–30)', 'price' => 10, 'qty' => 300],
                    ['name' => 'Mentor Pass', 'description' => 'Summit access + mentorship lounge', 'price' => 30, 'qty' => 50],
                ],
            ],
        ];

        foreach ($events as $data) {
            $tickets = $data['tickets'];
            unset($data['tickets']);

            $slug = Str::slug($data['title']);
            $event = Event::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($data, [
                    'organizer_id' => $profile->id,
                    'slug' => $slug,
                    'address' => $data['venue'].', '.$data['city'],
                    'status' => 'published',
                ])
            );

            foreach ($tickets as $ticket) {
                TicketType::query()->updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'name' => $ticket['name'],
                    ],
                    [
                        'description' => $ticket['description'],
                        'price' => $ticket['price'],
                        'quantity_available' => $ticket['qty'],
                        'quantity_sold' => 0,
                        'max_per_order' => 10,
                    ]
                );
            }
        }
    }
}
