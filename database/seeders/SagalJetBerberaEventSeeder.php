<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventGalleryImage;
use App\Models\EventSpeaker;
use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use App\Models\TicketType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SagalJetBerberaEventSeeder extends Seeder
{
    public function run(): void
    {
        $profile = OrganizerProfile::query()->where('approval_status', 'approved')->orderBy('id')->first();
        if (! $profile) {
            $this->command?->warn('No approved organizer — skipped SagalJet events.');

            return;
        }

        $package = OrganizerPackage::query()->active()->freeEventPlans()->ordered()->first();

        foreach ($this->events() as $data) {
            $this->importEvent($profile, $package, $data);
        }

        $featuredSlugs = collect($this->events())
            ->filter(fn (array $event) => ! empty($event['is_featured']))
            ->pluck('slug')
            ->all();

        Event::query()
            ->where('is_featured', true)
            ->when($featuredSlugs !== [], fn ($q) => $q->whereNotIn('slug', $featuredSlugs))
            ->update(['is_featured' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importEvent(OrganizerProfile $profile, ?OrganizerPackage $package, array $data): void
    {
        $stored = $this->storeImages($data['slug'], $data['images']);
        $cover = $stored[0] ?? ($data['images'][0] ?? null);
        $source = 'https://www.sagaljet.net/events/'.$data['slug'];

        $event = Event::query()->updateOrCreate(
            ['slug' => $data['slug']],
            [
                'organizer_id' => $profile->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => trim($data['description']."\n\nSource: {$source}"),
                'category' => $data['category'],
                'venue' => $data['venue'],
                'address' => $data['venue'].', '.$data['city'],
                'city' => $data['city'],
                'event_date' => $data['event_date'],
                'event_time' => $data['event_time'] ?? '09:00:00',
                'cover_image' => $cover,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'is_private' => false,
                'pricing_type' => 'free',
                'package_id' => $package?->id,
                'package_paid_at' => now(),
                'status' => 'published',
            ]
        );

        TicketType::query()->updateOrCreate(
            ['event_id' => $event->id, 'name' => 'General Admission'],
            [
                'description' => 'Free entry',
                'price' => 0,
                'quantity_available' => 300,
                'quantity_sold' => 0,
                'max_per_order' => 5,
            ]
        );

        foreach ($data['speakers'] as $i => $speaker) {
            EventSpeaker::query()->updateOrCreate(
                ['event_id' => $event->id, 'name' => $speaker['name']],
                [
                    'role' => $speaker['role'] ?? null,
                    'bio' => $speaker['bio'] ?? null,
                    'sort_order' => $i,
                ]
            );
        }

        $event->galleryImages()->delete();
        foreach ($stored as $i => $path) {
            EventGalleryImage::query()->create([
                'event_id' => $event->id,
                'path' => $path,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(): array
    {
        return [
            [
                'slug' => 'xafladda-soo-bandhigidda-qorshaha-guud-ee-magaalada-berbera',
                'title' => 'Xafladda Soo Bandhigidda Qorshaha Guud ee Magaalada Berbera',
                'category' => 'Business',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2026-02-09',
                'description' => "Munaasibaddan iyada ah waxa lagu soo bandhigay Qorshaha Horumarineed ee Magaalada Berbera (Development Master Plan) — qorshe casri ah oo loogu talo galay magaalada Berbera 2025 ilaa 2035.\n\nMunaasibadda waxa goob joog ahaa wasiiro, maayar ku-xigeenka caasimada Hargeisa, iyo marti sharaf ballaadhan.\n\nClient: UN-Habitat\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/5c670abb-acb3-44fc-8548-42e2b7beacc0.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/f0b7cc62-5b9e-4c65-a4c9-6c1b5a0879bd.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/10a93b04-1c39-44a1-aa96-6b4d63d55a15.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/3d357724-9b32-4350-93ea-080a26821cb5.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/b09bf31a-a670-4e0f-8f7f-f46cfc1d447f.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/a1d5f8b0-dfdb-4e3a-b125-ca5043f7fa4a.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'Maxamed Cali Aw Cabdi',
                        'role' => 'Madaxweyne ku-xigeenka JSL · ku-simaha Madaxweynaha',
                        'bio' => 'Wuxuu xafladda ka hadlay isagoo bogaadiyey diyaarinta qorshayaasha horumarinta magaalooyinka, gaar ahaan Qorshaha Guud ee Magaalada Berbera 2025–2035.',
                    ],
                    [
                        'name' => 'UN-Habitat',
                        'role' => 'Client',
                        'bio' => 'Hay’adda Qaramada Midoobay ee degaannada ayaa ah macmiilka munaasibadda.',
                    ],
                ],
            ],
            [
                'slug' => 'e-maalgeli',
                'title' => 'E-Maalgeli',
                'category' => 'Business',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2026-02-24',
                'description' => "Xafladda E-Maalgeli oo ay soo qabanqaabisay Darasalaam Bank. Munaasibad ganacsi oo ku saabsan maalgashiga dhijitaalka ah, ayadoo SagalJet ay ka qayb qaadatay qurxinta iyo bandhigga.\n\nClient: Darasalaam Bank\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/6806111a-1890-4d72-9aac-a97ea99adfc8.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/32b6e0fb-a620-40b2-80ca-71936afe7647.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/f9e5c09b-8425-4f1d-96cb-1cf72951fe3c.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/700e3c45-7f1d-4256-ba68-bd0e472d1a53.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/4293c2bb-fa21-412d-ae43-22b6f0a985e9.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/cf7febfa-8cf1-458b-a67d-a3955ecf848d.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'Darasalaam Bank',
                        'role' => 'Client',
                        'bio' => 'Bangiga Darasalaam ayaa ah macmiilka xafladda E-Maalgeli.',
                    ],
                ],
            ],
            [
                'slug' => 'sanaabil-honor-mobile-event',
                'title' => 'Sanaabil Honor Mobile Event',
                'category' => 'Tech',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2026-02-07',
                'description' => "Xafladda Sanaabil Honor Mobile Event oo lagu soo bandhigay telefoonnada Honor. Munaasibad tiknoolaji ah oo ka dhacday Hargeisa, ayadoo SagalJet ay ka qayb qaadatay qurxinta iyo bandhigga.\n\nClient: Sanaabil\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/79cd1d96-ff05-4745-af61-4929af5eea1b.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/f551e0cf-8e14-437d-8138-11626ab3082d.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/727e7266-149f-4c2a-a350-88c3b9b11c43.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/ae77e81f-f159-4864-9b25-a3ee88c12dfc.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/e55ed0ed-108d-4c07-ae6b-91ad82a97895.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/55ccacd7-d230-4bfb-8a41-7b9d21ff98f4.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'Sanaabil',
                        'role' => 'Client',
                        'bio' => 'Sanaabil ayaa ah macmiilka xafladda Honor Mobile.',
                    ],
                ],
            ],
            [
                'slug' => 'somaliland-football-awards',
                'title' => 'Somaliland Football Awards',
                'category' => 'Sports',
                'city' => 'Berbera',
                'venue' => 'Berbera',
                'event_date' => '2026-02-05',
                'description' => "Abaalmarinta Kubadda Cagta Somaliland oo ka dhacday Berbera. Wasaaradda Ciyaaraha ayaa soo qabanqaabisay, SagalJet-na waxay ka qayb qaadatay qurxinta iyo bandhigga xafladda.\n\nClient: Wasaaradda Ciyaaraha\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/ef77ec1f-16c9-4bb9-80ac-1b0a59993fae.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/7acc4a68-a0c2-4ba9-96f7-2baf10871632.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/7e14f586-fabc-495d-ae82-45066351c148.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/45ba76d4-9c1b-4f16-97e3-dd409bbd55a3.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/499c44a1-4726-4dc7-b958-32ad2b403e1e.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/3f8b597f-473e-44c1-9608-2f271113deef.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'Wasaaradda Ciyaaraha',
                        'role' => 'Client',
                        'bio' => 'Wasaaradda Ciyaaraha ayaa ah soo-qabanqaabiyaha Abaalmarinta Kubadda Cagta Somaliland.',
                    ],
                ],
            ],
            [
                'slug' => 'dabbaaldegga-aqoonsiga-somaliland',
                'title' => 'Dabbaaldegga Aqoonsiga Somaliland',
                'category' => 'Culture',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2025-12-27',
                'description' => "Dabbaaldegga Aqoonsiga Somaliland oo ka dhacday Hargeisa. Dawladda Hoose ee Hargeisa ayaa soo qabanqaabisay, SagalJet-na waxay ka qayb qaadatay qurxinta iyo bandhigga xafladda.\n\nClient: Dawladda Hoose ee Hargeisa\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/e79126b3-ca94-41a0-b14a-1ac81e5d7d0a.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/23acc1c8-4c40-4474-a66f-608ebb8fdfcf.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/705793aa-522d-4bd8-8239-c221b3fbbcd9.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'Dawladda Hoose ee Hargeisa',
                        'role' => 'Client',
                        'bio' => 'Dawladda Hoose ee Hargeisa ayaa ah soo-qabanqaabiyaha dabbaaldegga.',
                    ],
                ],
            ],
            [
                'slug' => 'xuska-maalinta-hablaha',
                'title' => 'Xuska Maalinta Hablaha',
                'category' => 'Culture',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2025-10-14',
                'description' => "Xuska Maalinta Hablaha oo ka dhacday Hargeisa. Wasaaradda Shaqada, Arrimaha Bulshada iyo Qoyska Somaliland ayaa soo qabanqaabisay, SagalJet-na waxay ka qayb qaadatay qurxinta iyo bandhigga.\n\nClient: Wasaaradda Shaqada, Arrimaha Bulshada iyo Qoyska Somaliland\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/e23411c8-55fd-4c48-9e5b-98c82e96d9ef.jpg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/a986c07e-7f2e-40a1-a88d-4ee592b4679a.jpg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/dd6dbfd1-9153-44f1-a8cb-7611a9aa2c1e.jpg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/79fa7a0a-9d61-4757-9921-9cfe10332db7.jpg',
                ],
                'speakers' => [
                    [
                        'name' => 'Wasaaradda Shaqada, Arrimaha Bulshada iyo Qoyska',
                        'role' => 'Client',
                        'bio' => 'Wasaaradda Shaqada, Arrimaha Bulshada iyo Qoyska Somaliland ayaa ah soo-qabanqaabiyaha xuska.',
                    ],
                ],
            ],
            [
                'slug' => 'machadka-diblomaasiyadda-somaliland',
                'title' => 'Machadka Diblomaasiyadda Somaliland',
                'category' => 'Education',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2026-09-13',
                'is_featured' => true,
                'description' => "Xafladda Machadka Diblomaasiyadda Somaliland oo ka dhacaysa Hargeisa. Machadka ayaa soo qabanqaabinaya, SagalJet-na waxay ka qayb qaadataa qurxinta iyo bandhigga.\n\nClient: Machadka Diblomaasiyadda Somaliland\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/b2a68d17-dc20-4499-bc18-bf347a9ace23.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/af9c5762-34d6-4703-bf9a-6de577d3da0a.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/f2a0a4cb-f1cd-4378-9c46-37c2a1c96316.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/53a22312-fe31-4c8e-8483-a2d8f99a5fb6.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/6488b40e-987f-409c-849d-1785f2a7119c.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/311d4811-5e38-4b2c-b1eb-3da63324acf1.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'Machadka Diblomaasiyadda Somaliland',
                        'role' => 'Client',
                        'bio' => 'Machadka Diblomaasiyadda Somaliland ayaa ah soo-qabanqaabiyaha xafladda.',
                    ],
                ],
            ],
            [
                'slug' => 'sonyo',
                'title' => 'SONYO',
                'category' => 'Education',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2026-09-06',
                'is_featured' => true,
                'description' => "Xafladda SONYO (Somaliland National Youth Organization) oo ka dhacaysa Hargeisa. Ururka dhalinyarada ayaa soo qabanqaabinaya, SagalJet-na waxay ka qayb qaadataa qurxinta iyo bandhigga.\n\nClient: SONYO\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/2dbcc46d-93a0-4a85-9976-61a05b14a662.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/f62532f1-05ae-45eb-b097-f73a687fe0bd.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'SONYO',
                        'role' => 'Client',
                        'bio' => 'Somaliland National Youth Organization ayaa ah soo-qabanqaabiyaha xafladda.',
                    ],
                ],
            ],
            [
                'slug' => 'xafladda-furitaanka-xarunta-labaad-ee-waddani',
                'title' => 'Xafladda Furitaanka Xarunta Labaad ee Waddani',
                'category' => 'Culture',
                'city' => 'Hargeisa',
                'venue' => 'Hargeisa',
                'event_date' => '2026-09-20',
                'is_featured' => true,
                'description' => "Xafladda furitaanka xarunta labaad ee Xisbiga Waddani oo ka dhacaysa Hargeisa. Xisbiga Waddani ayaa soo qabanqaabinaya, SagalJet-na waxay ka qayb qaadataa qurxinta iyo bandhigga.\n\nClient: Xisbiga Waddani\nEvent branding: SagalJet",
                'images' => [
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/5de4a407-eb92-4e81-bfd6-8730cf2df3a4.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/cc725429-1cbb-41b6-8fa8-561fc4c785a4.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/ffb82129-5edc-4d5a-b44a-863bb71821a8.jpeg',
                    'https://pub-255caa31667c4b7eb78f0da0a128f54e.r2.dev/3900df49-bf83-4bbb-8c9e-5e9a29ee2d55.jpeg',
                ],
                'speakers' => [
                    [
                        'name' => 'Xisbiga Waddani',
                        'role' => 'Client',
                        'bio' => 'Xisbiga Waddani ayaa ah soo-qabanqaabiyaha xafladda furitaanka xarunta labaad.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    private function storeImages(string $slug, array $urls): array
    {
        $directory = public_path('images/events/gallery');
        File::ensureDirectoryExists($directory, 0775);

        $paths = [];
        foreach ($urls as $i => $url) {
            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION)) ?: 'jpeg';
            $filename = $slug.'-'.($i + 1).'.'.$extension;
            $relative = 'images/events/gallery/'.$filename;
            $full = public_path($relative);

            if (! File::isFile($full)) {
                try {
                    $response = Http::timeout(60)->get($url);
                    if ($response->successful()) {
                        File::put($full, $response->body());
                    }
                } catch (\Throwable) {
                    // Fall back to the remote URL if download fails.
                }
            }

            $paths[] = File::isFile($full) ? $relative : $url;
        }

        return $paths;
    }
}
