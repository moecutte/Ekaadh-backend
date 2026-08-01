<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InvitationDesign;
use App\Models\InvitationDesignField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class InvitationDesignSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('invitation_designs') || ! Schema::hasTable('categories')) {
            return;
        }

        $privateId = Category::privateRoot()?->id;
        if (! $privateId) {
            return;
        }

        $categories = Category::query()
            ->active()
            ->childrenOf($privateId)
            ->ordered()
            ->get()
            ->keyBy('slug');

        if ($categories->isEmpty()) {
            return;
        }

        $catalog = $this->catalog();

        foreach ($catalog as $row) {
            $category = $categories->get($row['category_slug']);
            if (! $category) {
                continue;
            }

            $design = InvitationDesign::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'private_event_category_id' => $category->id,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'tier' => $row['tier'],
                    'ticket_price' => $row['ticket_price'] ?? null,
                    'premium_surcharge' => $row['premium_surcharge'] ?? null,
                    'render_mode' => 'blade',
                    'blade_key' => $row['blade_key'],
                    'graphic_path' => null,
                    'thumbnail_path' => null,
                    'accent' => $row['accent'],
                    'accent_soft' => $row['accent_soft'],
                    'header_from' => $row['header_from'],
                    'header_to' => $row['header_to'],
                    'card_bg' => $row['card_bg'],
                    'text_color' => $row['text_color'],
                    'muted_color' => $row['muted_color'],
                    'border_color' => $row['border_color'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ]
            );

            $this->syncFields($design, $category->requires_couple_names);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            [
                'category_slug' => 'aroos',
                'slug' => 'aroos-classic',
                'name' => 'Aroos Classic',
                'description' => 'Elegant wedding invitation with classic serif typography.',
                'tier' => 'standard',
                'blade_key' => 'wedding',
                'sort_order' => 1,
                'accent' => '#8b5a6b',
                'accent_soft' => '#f7f0f2',
                'header_from' => '#3d2a32',
                'header_to' => '#8b5a6b',
                'card_bg' => '#fffaf8',
                'text_color' => '#2c2428',
                'muted_color' => '#7a6a70',
                'border_color' => '#e4d0d6',
            ],
            [
                'category_slug' => 'aroos',
                'slug' => 'aroos-royal-gold',
                'name' => 'Aroos Royal Gold',
                'description' => 'Premium gold-framed wedding invitation.',
                'tier' => 'premium',
                'blade_key' => 'royal_gold',
                'sort_order' => 2,
                'accent' => '#c5a059',
                'accent_soft' => '#fbf6eb',
                'header_from' => '#1f1a12',
                'header_to' => '#5c4a28',
                'card_bg' => '#fffdf7',
                'text_color' => '#2a2418',
                'muted_color' => '#7a6e55',
                'border_color' => '#d4bc7a',
            ],
            [
                'category_slug' => 'meher',
                'slug' => 'meher-formal',
                'name' => 'Meher Formal',
                'description' => 'Clean formal invitation for Meher ceremonies.',
                'tier' => 'standard',
                'blade_key' => 'formal',
                'sort_order' => 3,
                'accent' => '#4a5d73',
                'accent_soft' => '#eef2f6',
                'header_from' => '#1e293b',
                'header_to' => '#4a5d73',
                'card_bg' => '#ffffff',
                'text_color' => '#1e293b',
                'muted_color' => '#64748b',
                'border_color' => '#cbd5e1',
            ],
            [
                'category_slug' => 'meher',
                'slug' => 'meher-garden',
                'name' => 'Meher Garden',
                'description' => 'Soft romantic garden-style premium invitation.',
                'tier' => 'premium',
                'blade_key' => 'garden_romance',
                'sort_order' => 4,
                'accent' => '#6b8f71',
                'accent_soft' => '#f0f6f1',
                'header_from' => '#2d3f31',
                'header_to' => '#6b8f71',
                'card_bg' => '#fbfefb',
                'text_color' => '#243028',
                'muted_color' => '#6b7a6e',
                'border_color' => '#c5d6c8',
            ],
            [
                'category_slug' => 'xaflad',
                'slug' => 'xaflad-celebration',
                'name' => 'Xaflad Celebration',
                'description' => 'Bright celebration layout for parties and gatherings.',
                'tier' => 'standard',
                'blade_key' => 'celebration',
                'sort_order' => 5,
                'accent' => '#323891',
                'accent_soft' => '#eef0f8',
                'header_from' => '#0f1a2e',
                'header_to' => '#323891',
                'card_bg' => '#ffffff',
                'text_color' => '#0f1a2e',
                'muted_color' => '#64748b',
                'border_color' => '#e2e8f0',
            ],
            [
                'category_slug' => 'xaflad',
                'slug' => 'xaflad-midnight',
                'name' => 'Xaflad Midnight',
                'description' => 'Premium midnight gala look for evening events.',
                'tier' => 'premium',
                'blade_key' => 'midnight_gala',
                'sort_order' => 6,
                'accent' => '#a78bfa',
                'accent_soft' => '#1e1633',
                'header_from' => '#0b0618',
                'header_to' => '#2e1065',
                'card_bg' => '#120a24',
                'text_color' => '#f8fafc',
                'muted_color' => '#c4b5fd',
                'border_color' => '#4c1d95',
            ],
            [
                'category_slug' => 'casho',
                'slug' => 'casho-formal',
                'name' => 'Casho Formal',
                'description' => 'Refined dinner invitation layout.',
                'tier' => 'standard',
                'blade_key' => 'formal',
                'sort_order' => 7,
                'accent' => '#7c5c3b',
                'accent_soft' => '#f7f1ea',
                'header_from' => '#2c2118',
                'header_to' => '#7c5c3b',
                'card_bg' => '#fffaf5',
                'text_color' => '#2a2118',
                'muted_color' => '#7a6a58',
                'border_color' => '#e0d0be',
            ],
            [
                'category_slug' => 'casho',
                'slug' => 'casho-celebration',
                'name' => 'Casho Celebration',
                'description' => 'Warm premium dinner celebration invitation.',
                'tier' => 'premium',
                'blade_key' => 'celebration',
                'sort_order' => 8,
                'accent' => '#b45309',
                'accent_soft' => '#fff7ed',
                'header_from' => '#431407',
                'header_to' => '#b45309',
                'card_bg' => '#fffbeb',
                'text_color' => '#292524',
                'muted_color' => '#78716c',
                'border_color' => '#fcd34d',
            ],
        ];
    }

    private function syncFields(InvitationDesign $design, bool $requiresCouple): void
    {
        $fields = $requiresCouple
            ? [
                ['field_key' => 'couple_name_1', 'label' => 'First name', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Amina', 'sort_order' => 1],
                ['field_key' => 'couple_name_2', 'label' => 'Second name', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Hassan', 'sort_order' => 2],
                ['field_key' => 'venue', 'label' => 'Venue', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'Venue name', 'sort_order' => 3],
                ['field_key' => 'date_month', 'label' => 'Month', 'field_type' => 'date_month', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 4],
                ['field_key' => 'date_day', 'label' => 'Day', 'field_type' => 'date_day', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 5],
                ['field_key' => 'date_year', 'label' => 'Year', 'field_type' => 'date_year', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 6],
                ['field_key' => 'date_time', 'label' => 'Time', 'field_type' => 'date_time', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 7],
            ]
            : [
                ['field_key' => 'title', 'label' => 'Event title', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'e.g. Family dinner', 'sort_order' => 1],
                ['field_key' => 'venue', 'label' => 'Venue', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'Venue name', 'sort_order' => 2],
                ['field_key' => 'date_month', 'label' => 'Month', 'field_type' => 'date_month', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 3],
                ['field_key' => 'date_day', 'label' => 'Day', 'field_type' => 'date_day', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 4],
                ['field_key' => 'date_year', 'label' => 'Year', 'field_type' => 'date_year', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 5],
                ['field_key' => 'date_time', 'label' => 'Time', 'field_type' => 'date_time', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 6],
            ];

        foreach ($fields as $field) {
            InvitationDesignField::query()->updateOrCreate(
                [
                    'invitation_design_id' => $design->id,
                    'field_key' => $field['field_key'],
                ],
                [
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'is_required' => $field['is_required'],
                    'placeholder' => $field['placeholder'],
                    'default_text' => null,
                    'maps_to_couple' => $field['maps_to_couple'],
                    'show_on_card' => true,
                    'text_align' => 'center',
                    'sort_order' => $field['sort_order'],
                ]
            );
        }
    }
}
