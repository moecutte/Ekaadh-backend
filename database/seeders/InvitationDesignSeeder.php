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
                    'render_mode' => $row['render_mode'] ?? 'blade',
                    'blade_key' => $row['blade_key'],
                    'graphic_path' => $row['graphic_path'] ?? null,
                    'thumbnail_path' => $row['thumbnail_path'] ?? null,
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
            [
                'category_slug' => 'aroos',
                'slug' => 'aroos-blush-petal',
                'name' => 'Aroos Blush Petal',
                'description' => 'Animated ivory-and-rose wedding card with drifting petals.',
                'tier' => 'standard',
                'blade_key' => 'blush_petal',
                'render_mode' => 'blade',
                'graphic_path' => null,
                'thumbnail_path' => 'invitation-designs/blush-petal.jpg',
                'sort_order' => 9,
                'accent' => '#c48b96',
                'accent_soft' => '#fbf3f4',
                'header_from' => '#8b5a6b',
                'header_to' => '#d4a5b0',
                'card_bg' => '#fffaf8',
                'text_color' => '#3d2a32',
                'muted_color' => '#8a6f76',
                'border_color' => '#e8cfd4',
            ],
            [
                'category_slug' => 'aroos',
                'slug' => 'aroos-velvet-gold',
                'name' => 'Aroos Velvet Gold',
                'description' => 'Premium burgundy velvet wedding invite with gold shimmer.',
                'tier' => 'premium',
                'blade_key' => 'velvet_gold',
                'render_mode' => 'blade',
                'graphic_path' => null,
                'thumbnail_path' => 'invitation-designs/velvet-gold.jpg',
                'sort_order' => 10,
                'accent' => '#d4af37',
                'accent_soft' => '#3a1c24',
                'header_from' => '#1a0b10',
                'header_to' => '#5c2432',
                'card_bg' => '#1c0f14',
                'text_color' => '#f8eec8',
                'muted_color' => '#c4b08a',
                'border_color' => '#c5a059',
            ],
            [
                'category_slug' => 'meher',
                'slug' => 'meher-sage-promise',
                'name' => 'Meher Sage Promise',
                'description' => 'Botanical engagement card with a slowly turning ring.',
                'tier' => 'standard',
                'blade_key' => 'sage_promise',
                'render_mode' => 'blade',
                'graphic_path' => null,
                'thumbnail_path' => 'invitation-designs/sage-promise.jpg',
                'sort_order' => 11,
                'accent' => '#6b8f71',
                'accent_soft' => '#f3f7f2',
                'header_from' => '#2d3f31',
                'header_to' => '#6b8f71',
                'card_bg' => '#fbfefb',
                'text_color' => '#243028',
                'muted_color' => '#6b7a6e',
                'border_color' => '#c5d6c8',
            ],
            [
                'category_slug' => 'meher',
                'slug' => 'meher-starlit-vow',
                'name' => 'Meher Starlit Vow',
                'description' => 'Premium night-sky engagement invite with drifting stars.',
                'tier' => 'premium',
                'blade_key' => 'starlit_vow',
                'render_mode' => 'blade',
                'graphic_path' => null,
                'thumbnail_path' => 'invitation-designs/starlit-vow.jpg',
                'sort_order' => 12,
                'accent' => '#e8d48b',
                'accent_soft' => '#15203c',
                'header_from' => '#070b18',
                'header_to' => '#1b2a58',
                'card_bg' => '#0a1020',
                'text_color' => '#f8fafc',
                'muted_color' => '#c4b5a0',
                'border_color' => '#3b4d7a',
            ],
            [
                'category_slug' => 'xaflad',
                'slug' => 'xaflad-lantern-garden',
                'name' => 'Xaflad Lantern Garden',
                'description' => 'Warm ceremony card with rising lanterns and geometric sides.',
                'tier' => 'standard',
                'blade_key' => 'lantern_garden',
                'render_mode' => 'blade',
                'graphic_path' => null,
                'thumbnail_path' => 'invitation-designs/lantern-garden.jpg',
                'sort_order' => 13,
                'accent' => '#c2410c',
                'accent_soft' => '#fff7ed',
                'header_from' => '#3b2416',
                'header_to' => '#b45309',
                'card_bg' => '#fffaf5',
                'text_color' => '#2c2118',
                'muted_color' => '#7c6a58',
                'border_color' => '#e7d2b8',
            ],
            [
                'category_slug' => 'xaflad',
                'slug' => 'xaflad-oasis-gala',
                'name' => 'Xaflad Oasis Gala',
                'description' => 'Premium teal-and-gold evening ceremony with rotating geometry.',
                'tier' => 'premium',
                'blade_key' => 'oasis_gala',
                'render_mode' => 'blade',
                'graphic_path' => null,
                'thumbnail_path' => 'invitation-designs/oasis-gala.jpg',
                'sort_order' => 14,
                'accent' => '#d4af37',
                'accent_soft' => '#123834',
                'header_from' => '#04201c',
                'header_to' => '#0f766e',
                'card_bg' => '#062422',
                'text_color' => '#f5edd4',
                'muted_color' => '#c5b894',
                'border_color' => '#b7a056',
            ],
            [
                'category_slug' => 'casho',
                'slug' => 'casho-pearl-soiree',
                'name' => 'Casho Pearl Soiree',
                'description' => 'Premium champagne dinner invite with foil shimmer and deco corners.',
                'tier' => 'premium',
                'blade_key' => 'pearl_soiree',
                'render_mode' => 'blade',
                'graphic_path' => null,
                'thumbnail_path' => 'invitation-designs/pearl-soiree.jpg',
                'sort_order' => 15,
                'accent' => '#b0894b',
                'accent_soft' => '#f7f0e4',
                'header_from' => '#6b5428',
                'header_to' => '#d4b483',
                'card_bg' => '#fbf6ec',
                'text_color' => '#2a2418',
                'muted_color' => '#7a6e55',
                'border_color' => '#d4bc7a',
            ],
        ];
    }

    private function syncFields(InvitationDesign $design, bool $requiresCouple): void
    {
        $isOverlay = ($design->render_mode === 'overlay') && filled($design->graphic_path);
        $fields = $isOverlay
            ? $this->overlayFields($design, $requiresCouple)
            : $this->bladeFields($requiresCouple);

        $keep = [];
        foreach ($fields as $field) {
            $keep[] = $field['field_key'];
            InvitationDesignField::query()->updateOrCreate(
                [
                    'invitation_design_id' => $design->id,
                    'field_key' => $field['field_key'],
                ],
                [
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'is_required' => $field['is_required'],
                    'placeholder' => $field['placeholder'] ?? null,
                    'default_text' => $field['default_text'] ?? null,
                    'maps_to_couple' => $field['maps_to_couple'],
                    'show_on_card' => $field['show_on_card'] ?? true,
                    'pos_x' => $field['pos_x'] ?? null,
                    'pos_y' => $field['pos_y'] ?? null,
                    'box_width' => $field['box_width'] ?? null,
                    'font_size' => $field['font_size'] ?? null,
                    'font_family' => $field['font_family'] ?? null,
                    'font_weight' => $field['font_weight'] ?? null,
                    'font_style' => $field['font_style'] ?? 'normal',
                    'color' => $field['color'] ?? null,
                    'text_align' => $field['text_align'] ?? 'center',
                    'sort_order' => $field['sort_order'],
                ]
            );
        }

        $design->fields()->whereNotIn('field_key', $keep)->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bladeFields(bool $requiresCouple): array
    {
        return $requiresCouple
            ? [
                ['field_key' => 'couple_name_1', 'label' => 'First name', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Amina', 'default_text' => 'Amina', 'sort_order' => 1],
                ['field_key' => 'couple_name_2', 'label' => 'Second name', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Hassan', 'default_text' => 'Hassan', 'sort_order' => 2],
                ['field_key' => 'venue', 'label' => 'Venue', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'Venue name', 'default_text' => 'Grand Ballroom', 'sort_order' => 3],
                ['field_key' => 'date_month', 'label' => 'Month', 'field_type' => 'date_month', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 4],
                ['field_key' => 'date_day', 'label' => 'Day', 'field_type' => 'date_day', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 5],
                ['field_key' => 'date_year', 'label' => 'Year', 'field_type' => 'date_year', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 6],
                ['field_key' => 'date_time', 'label' => 'Time', 'field_type' => 'date_time', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 7],
            ]
            : [
                ['field_key' => 'title', 'label' => 'Event title', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'e.g. Family dinner', 'default_text' => 'Family Celebration', 'sort_order' => 1],
                ['field_key' => 'venue', 'label' => 'Venue', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'Venue name', 'default_text' => 'Grand Ballroom', 'sort_order' => 2],
                ['field_key' => 'date_month', 'label' => 'Month', 'field_type' => 'date_month', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 3],
                ['field_key' => 'date_day', 'label' => 'Day', 'field_type' => 'date_day', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 4],
                ['field_key' => 'date_year', 'label' => 'Year', 'field_type' => 'date_year', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 5],
                ['field_key' => 'date_time', 'label' => 'Time', 'field_type' => 'date_time', 'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null, 'sort_order' => 6],
            ];
    }

    /**
     * Positioned overlay slots so admin live preview and customer fill-in match uploaded graphics.
     *
     * @return list<array<string, mixed>>
     */
    private function overlayFields(InvitationDesign $design, bool $requiresCouple): array
    {
        $accent = $design->accent ?: '#8b5a6b';
        $text = $design->text_color ?: '#3d2a32';
        $muted = $design->muted_color ?: '#7a6a70';
        $key = (string) $design->blade_key;

        $theme = match ($key) {
            'velvet_gold' => [
                'name_font' => 'Great Vibes', 'name_size' => 34, 'name_y' => 40,
                'body_font' => 'Cormorant Garamond', 'title_font' => 'Cinzel', 'title_y' => 40,
                'date_y' => 56, 'venue_y' => 66, 'qr_y' => 76,
            ],
            'sage_promise' => [
                'name_font' => 'Great Vibes', 'name_size' => 32, 'name_y' => 38,
                'body_font' => 'Cormorant Garamond', 'title_font' => 'Cormorant Garamond', 'title_y' => 38,
                'date_y' => 54, 'venue_y' => 65, 'qr_y' => 76,
            ],
            'starlit_vow' => [
                'name_font' => 'Great Vibes', 'name_size' => 34, 'name_y' => 34,
                'body_font' => 'Playfair Display', 'title_font' => 'Playfair Display', 'title_y' => 34,
                'date_y' => 51, 'venue_y' => 62, 'qr_y' => 74,
            ],
            'lantern_garden' => [
                'name_font' => 'Playfair Display', 'name_size' => 26, 'name_y' => 34,
                'body_font' => 'Cormorant Garamond', 'title_font' => 'Playfair Display', 'title_y' => 34,
                'date_y' => 48, 'venue_y' => 60, 'qr_y' => 73,
            ],
            'oasis_gala' => [
                'name_font' => 'Cinzel', 'name_size' => 22, 'name_y' => 42,
                'body_font' => 'Cormorant Garamond', 'title_font' => 'Cinzel', 'title_y' => 42,
                'date_y' => 54, 'venue_y' => 64, 'qr_y' => 75,
            ],
            'pearl_soiree' => [
                'name_font' => 'Great Vibes', 'name_size' => 32, 'name_y' => 34,
                'body_font' => 'Cormorant Garamond', 'title_font' => 'Playfair Display', 'title_y' => 34,
                'date_y' => 48, 'venue_y' => 60, 'qr_y' => 73,
            ],
            default => [
                'name_font' => 'Great Vibes', 'name_size' => 32, 'name_y' => 38,
                'body_font' => 'Cormorant Garamond', 'title_font' => 'Cormorant Garamond', 'title_y' => 38,
                'date_y' => 54, 'venue_y' => 65, 'qr_y' => 76,
            ],
        };

        $body = [
            'font_family' => $theme['body_font'],
            'font_weight' => '400',
            'font_style' => 'normal',
            'color' => $muted,
            'text_align' => 'center',
            'box_width' => 22.0,
        ];

        $fields = $requiresCouple
            ? [
                array_merge($body, [
                    'field_key' => 'couple_name_1', 'label' => 'First name', 'field_type' => 'text',
                    'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Amina',
                    'default_text' => 'Amina', 'sort_order' => 1, 'pos_x' => 12.0, 'pos_y' => $theme['name_y'],
                    'box_width' => 76.0, 'font_size' => $theme['name_size'], 'font_family' => $theme['name_font'],
                    'color' => $accent, 'font_weight' => '400',
                ]),
                array_merge($body, [
                    'field_key' => 'couple_name_2', 'label' => 'Second name', 'field_type' => 'text',
                    'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Hassan',
                    'default_text' => 'Hassan', 'sort_order' => 2, 'pos_x' => 12.0, 'pos_y' => $theme['name_y'] + 8,
                    'box_width' => 76.0, 'font_size' => $theme['name_size'], 'font_family' => $theme['name_font'],
                    'color' => $accent, 'font_weight' => '400',
                ]),
            ]
            : [
                array_merge($body, [
                    'field_key' => 'title', 'label' => 'Event title', 'field_type' => 'text',
                    'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'e.g. Family dinner',
                    'default_text' => 'Family Celebration', 'sort_order' => 1, 'pos_x' => 12.0, 'pos_y' => $theme['title_y'],
                    'box_width' => 76.0, 'font_size' => 24, 'font_family' => $theme['title_font'],
                    'color' => $text, 'font_weight' => '600',
                ]),
            ];

        $dateY = $theme['date_y'];
        $fields[] = array_merge($body, [
            'field_key' => 'date_month', 'label' => 'Month', 'field_type' => 'date_month',
            'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null,
            'default_text' => 'Jan', 'sort_order' => 3, 'pos_x' => 16.0, 'pos_y' => $dateY,
            'font_size' => 13, 'font_family' => 'Cinzel', 'font_weight' => '600',
        ]);
        $fields[] = array_merge($body, [
            'field_key' => 'date_day', 'label' => 'Day', 'field_type' => 'date_day',
            'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null,
            'default_text' => '15', 'sort_order' => 4, 'pos_x' => 39.0, 'pos_y' => $dateY - 1.2,
            'font_size' => 22, 'font_family' => 'Cinzel', 'font_weight' => '700', 'color' => $text,
        ]);
        $fields[] = array_merge($body, [
            'field_key' => 'date_year', 'label' => 'Year', 'field_type' => 'date_year',
            'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null,
            'default_text' => (string) now()->year, 'sort_order' => 5, 'pos_x' => 62.0, 'pos_y' => $dateY,
            'font_size' => 13, 'font_family' => 'Cinzel', 'font_weight' => '600',
        ]);
        $fields[] = array_merge($body, [
            'field_key' => 'date_time', 'label' => 'Time', 'field_type' => 'date_time',
            'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null,
            'default_text' => '6:00 PM', 'sort_order' => 6, 'pos_x' => 22.0, 'pos_y' => $dateY + 7,
            'box_width' => 56.0, 'font_size' => 13, 'font_style' => 'italic',
        ]);
        $fields[] = array_merge($body, [
            'field_key' => 'venue', 'label' => 'Venue', 'field_type' => 'text',
            'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'Venue name',
            'default_text' => 'Grand Ballroom', 'sort_order' => 7, 'pos_x' => 12.0, 'pos_y' => $theme['venue_y'],
            'box_width' => 76.0, 'font_size' => 15, 'color' => $text, 'font_weight' => '600',
        ]);
        $fields[] = [
            'field_key' => 'qr', 'label' => 'QR code', 'field_type' => 'qr',
            'is_required' => false, 'maps_to_couple' => false, 'placeholder' => null,
            'default_text' => null, 'sort_order' => 8, 'pos_x' => 37.0, 'pos_y' => $theme['qr_y'],
            'box_width' => 26.0, 'font_size' => 10, 'font_family' => 'Montserrat',
            'font_weight' => '400', 'font_style' => 'normal', 'color' => $muted, 'text_align' => 'center',
            'show_on_card' => true,
        ];

        return $fields;
    }
}
