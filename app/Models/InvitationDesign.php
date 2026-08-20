<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Setting;

class InvitationDesign extends Model
{
    protected $fillable = [
        'private_event_category_id',
        'name',
        'slug',
        'description',
        'tier',
        'ticket_price',
        'premium_surcharge',
        'render_mode',
        'blade_key',
        'graphic_path',
        'thumbnail_path',
        'accent',
        'accent_soft',
        'header_from',
        'header_to',
        'card_bg',
        'text_color',
        'muted_color',
        'border_color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'private_event_category_id' => 'integer',
            'ticket_price' => 'float',
            'premium_surcharge' => 'float',
        ];
    }

    public function unitPrice(): float
    {
        $base = $this->ticket_price !== null
            ? (float) $this->ticket_price
            : (float) Setting::getValue('private_ticket_price', 5);

        if ($this->isPremium()) {
            $extra = $this->premium_surcharge !== null
                ? (float) $this->premium_surcharge
                : (float) Setting::getValue('private_premium_design_surcharge', 2);

            return round($base + $extra, 2);
        }

        return round($base, 2);
    }

    protected static function booted(): void
    {
        static::saving(function (InvitationDesign $design) {
            if ($design->isDirty('name') || blank($design->slug)) {
                $base = Str::slug($design->name) ?: 'design';
                $slug = $base;
                $i = 1;
                while (
                    static::query()
                        ->where('slug', $slug)
                        ->when($design->exists, fn ($q) => $q->where('id', '!=', $design->id))
                        ->exists()
                ) {
                    $slug = $base.'-'.$i++;
                }
                $design->slug = $slug;
            }
            if (blank($design->blade_key)) {
                $design->blade_key = $design->slug;
            }
        });
    }

    public function fields(): HasMany
    {
        return $this->hasMany(InvitationDesignField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'private_event_category_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Couple names or title + venue + auto date parts for the HTML theme.
     */
    public function syncDefaultBuyerFields(): void
    {
        $this->loadMissing('category');
        $requiresCouple = (bool) $this->category?->requires_couple_names;

        $fields = $requiresCouple
            ? [
                ['field_key' => 'couple_name_1', 'label' => 'First name', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Amina', 'default_text' => 'Amina', 'sort_order' => 1],
                ['field_key' => 'couple_name_2', 'label' => 'Second name', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => true, 'placeholder' => 'e.g. Hassan', 'default_text' => 'Hassan', 'sort_order' => 2],
                ['field_key' => 'venue', 'label' => 'Venue', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'Venue name', 'default_text' => 'Grand Ballroom', 'sort_order' => 3],
                ['field_key' => 'date_month', 'label' => 'Month', 'field_type' => 'date_month', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 4],
                ['field_key' => 'date_day', 'label' => 'Day', 'field_type' => 'date_day', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 5],
                ['field_key' => 'date_year', 'label' => 'Year', 'field_type' => 'date_year', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 6],
                ['field_key' => 'date_time', 'label' => 'Time', 'field_type' => 'date_time', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 7],
            ]
            : [
                ['field_key' => 'title', 'label' => 'Event title', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'e.g. Family dinner', 'default_text' => 'Family Celebration', 'sort_order' => 1],
                ['field_key' => 'venue', 'label' => 'Venue', 'field_type' => 'text', 'is_required' => true, 'maps_to_couple' => false, 'placeholder' => 'Venue name', 'default_text' => 'Grand Ballroom', 'sort_order' => 2],
                ['field_key' => 'date_month', 'label' => 'Month', 'field_type' => 'date_month', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 3],
                ['field_key' => 'date_day', 'label' => 'Day', 'field_type' => 'date_day', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 4],
                ['field_key' => 'date_year', 'label' => 'Year', 'field_type' => 'date_year', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 5],
                ['field_key' => 'date_time', 'label' => 'Time', 'field_type' => 'date_time', 'is_required' => false, 'maps_to_couple' => false, 'sort_order' => 6],
            ];

        $keep = [];
        foreach ($fields as $field) {
            $keep[] = $field['field_key'];
            InvitationDesignField::query()->updateOrCreate(
                [
                    'invitation_design_id' => $this->id,
                    'field_key' => $field['field_key'],
                ],
                [
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'is_required' => $field['is_required'],
                    'placeholder' => $field['placeholder'] ?? null,
                    'default_text' => $field['default_text'] ?? null,
                    'maps_to_couple' => $field['maps_to_couple'],
                    'show_on_card' => true,
                    'sort_order' => $field['sort_order'],
                ]
            );
        }

        $this->fields()->whereNotIn('field_key', $keep)->delete();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForCategory(Builder $query, ?int $categoryId): Builder
    {
        if (! $categoryId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('private_event_category_id', $categoryId);
    }

    public function isPremium(): bool
    {
        return $this->tier === 'premium';
    }

    public function isOverlay(): bool
    {
        return $this->render_mode === 'overlay' && filled($this->graphic_path);
    }

    protected function graphicUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->graphic_path) {
                    return null;
                }
                if (str_starts_with($this->graphic_path, 'http')) {
                    return $this->graphic_path;
                }

                return asset(ltrim($this->graphic_path, '/'));
            },
        );
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $path = $this->thumbnail_path ?: $this->graphic_path;
                if (! $path) {
                    return null;
                }
                if (str_starts_with($path, 'http')) {
                    return $path;
                }

                return asset(ltrim($path, '/'));
            },
        );
    }

    /**
     * Display copy for built-in animated blade templates.
     *
     * @return array{ornament:string,badge:string,invite_line:string,request_line:string,footer_line:string,font_display:string,font_body:string}
     */
    public function bladeCopy(): array
    {
        $defaults = [
            'ornament' => '✦',
            'badge' => $this->name ?: 'Invitation',
            'invite_line' => 'You are invited',
            'request_line' => 'request the pleasure of your company',
            'footer_line' => 'Kindly present this invitation at the entrance',
            'font_display' => 'Great Vibes',
            'font_body' => 'Cormorant Garamond',
        ];

        $map = [
            'blush_petal' => [
                'ornament' => '❀',
                'badge' => 'Wedding Invitation',
                'invite_line' => 'Together with their families',
                'request_line' => 'request the honour of your presence',
            ],
            'velvet_gold' => [
                'ornament' => '❖',
                'badge' => 'Royal Wedding',
                'invite_line' => 'With joyous hearts',
                'request_line' => 'we invite you to celebrate our marriage',
                'font_display' => 'Cinzel',
            ],
            'sage_promise' => [
                'ornament' => '○',
                'badge' => 'Engagement',
                'invite_line' => 'A promise begins',
                'request_line' => 'please join us as we celebrate our engagement',
            ],
            'starlit_vow' => [
                'ornament' => '✦',
                'badge' => 'Engagement',
                'invite_line' => 'Written in the stars',
                'request_line' => 'join us beneath the evening sky',
                'font_display' => 'Tangerine',
                'font_body' => 'Playfair Display',
            ],
            'lantern_garden' => [
                'ornament' => '✧',
                'badge' => 'Ceremony',
                'invite_line' => 'You are invited',
                'request_line' => 'to share an evening of light and gathering',
                'font_display' => 'Playfair Display',
            ],
            'oasis_gala' => [
                'ornament' => '◈',
                'badge' => 'Evening Ceremony',
                'invite_line' => 'An evening of splendour',
                'request_line' => 'kindly join us for a night of celebration',
                'font_display' => 'Cinzel',
            ],
            'pearl_soiree' => [
                'ornament' => '◆',
                'badge' => 'Dinner Invitation',
                'invite_line' => 'An evening reserved',
                'request_line' => 'the pleasure of your company is requested',
                'font_display' => 'Playfair Display',
            ],
        ];

        $key = (string) $this->blade_key;

        return array_merge($defaults, $map[$key] ?? []);
    }

    /**
     * Catalog shape for create forms / API (compatible with old TicketDesigns keys).
     *
     * @return array<string, mixed>
     */
    public function toCatalogArray(): array
    {
        $copy = $this->bladeCopy();

        return [
            'id' => $this->slug,
            'invitation_design_id' => $this->id,
            'private_event_category_id' => $this->private_event_category_id,
            'name' => $this->name ?: ($this->category?->name ? $this->category->name.' design' : 'Design'),
            'category' => $this->tier,
            'label' => $this->isPremium() ? 'Premium' : 'Standard',
            'description' => $this->description,
            'ticket_price' => $this->ticket_price,
            'premium_surcharge' => $this->premium_surcharge,
            'unit_price' => $this->unitPrice(),
            'accent' => $this->accent ?? '#323891',
            'accent_soft' => $this->accent_soft ?? '#eef0f8',
            'header_from' => $this->header_from ?? '#0f1a2e',
            'header_to' => $this->header_to ?? '#323891',
            'card_bg' => $this->card_bg ?? '#ffffff',
            'text' => $this->text_color ?? '#0f1a2e',
            'muted' => $this->muted_color ?? '#64748b',
            'border' => $this->border_color ?? '#e2e8f0',
            'render_mode' => $this->render_mode,
            'blade_key' => $this->blade_key,
            'graphic_path' => $this->graphic_path,
            'graphic_url' => $this->graphic_url,
            'thumbnail_url' => $this->thumbnail_url,
            'fields' => $this->relationLoaded('fields')
                ? $this->fields->map->toCatalogArray()->values()->all()
                : [],
            'ornament' => $copy['ornament'],
            'badge' => $copy['badge'],
            'invite_line' => $copy['invite_line'],
            'request_line' => $copy['request_line'],
            'footer_line' => $copy['footer_line'],
            'font_display' => $copy['font_display'],
            'font_body' => $copy['font_body'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function activeCatalog(?string $tier = null, ?int $categoryId = null): array
    {
        $q = static::query()->active()->ordered()->with('fields');
        if ($tier) {
            $q->where('tier', $tier);
        }
        if ($categoryId !== null) {
            $q->forCategory($categoryId);
        }

        return $q->get()->map->toCatalogArray()->values()->all();
    }

    public static function defaultSlug(?int $categoryId = null): string
    {
        $q = static::query()->active()->ordered();
        if ($categoryId !== null) {
            $q->forCategory($categoryId);
        }

        return (string) ($q->value('slug') ?? '');
    }
}
