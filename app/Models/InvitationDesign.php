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
     * Catalog shape for create forms / API (compatible with old TicketDesigns keys).
     *
     * @return array<string, mixed>
     */
    public function toCatalogArray(): array
    {
        return [
            'id' => $this->slug,
            'invitation_design_id' => $this->id,
            'private_event_category_id' => $this->private_event_category_id,
            'name' => $this->name ?: ($this->category?->name ? $this->category->name.' design' : 'Design'),
            'category' => $this->tier,
            'label' => $this->isPremium() ? 'Premium' : 'Standard',
            'description' => null,
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
            'ornament' => '',
            'badge' => $this->name,
            'invite_line' => '',
            'request_line' => '',
            'footer_line' => 'Kindly present this invitation at the entrance',
            'font_display' => 'Great Vibes',
            'font_body' => 'Montserrat',
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
