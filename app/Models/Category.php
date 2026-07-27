<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    public const SLUG_PUBLIC = 'public';

    public const SLUG_PRIVATE = 'private';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'requires_couple_names',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_couple_names' => 'boolean',
            'sort_order' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if ($category->isDirty('name') || blank($category->slug)) {
                $base = Str::slug($category->name) ?: 'category';
                $slug = $base;
                $i = 1;
                while (
                    static::query()
                        ->where('slug', $slug)
                        ->when($category->exists, fn ($q) => $q->where('id', '!=', $category->id))
                        ->exists()
                ) {
                    $slug = $base.'-'.$i++;
                }
                $category->slug = $slug;
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'category', 'name');
    }

    public function privateEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'private_event_category_id');
    }

    public function invitationDesigns(): HasMany
    {
        return $this->hasMany(InvitationDesign::class, 'private_event_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildrenOf(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function isPublicRoot(): bool
    {
        return $this->isRoot() && $this->slug === self::SLUG_PUBLIC;
    }

    public function isPrivateRoot(): bool
    {
        return $this->isRoot() && $this->slug === self::SLUG_PRIVATE;
    }

    public function isPrivateChild(): bool
    {
        return ! $this->isRoot() && $this->parent?->slug === self::SLUG_PRIVATE;
    }

    public static function publicRoot(): ?self
    {
        return static::query()->roots()->where('slug', self::SLUG_PUBLIC)->first();
    }

    public static function privateRoot(): ?self
    {
        return static::query()->roots()->where('slug', self::SLUG_PRIVATE)->first();
    }

    /**
     * @return list<string>
     */
    public static function activeNames(): array
    {
        $publicId = static::publicRoot()?->id;
        if (! $publicId) {
            return [];
        }

        return static::query()
            ->active()
            ->ordered()
            ->childrenOf($publicId)
            ->pluck('name')
            ->all();
    }

    /**
     * Options for private-event create / invite designs.
     *
     * @return list<array{id:int,name:string,slug:string,requires_couple_names:bool}>
     */
    public static function activeOptionsForPrivate(): array
    {
        $privateId = static::privateRoot()?->id;
        if (! $privateId) {
            return [];
        }

        return static::query()
            ->active()
            ->ordered()
            ->childrenOf($privateId)
            ->get(['id', 'name', 'slug', 'requires_couple_names'])
            ->map(fn (self $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'requires_couple_names' => (bool) $c->requires_couple_names,
            ])
            ->all();
    }

    /**
     * @return list<int>
     */
    public static function activePrivateChildIds(): array
    {
        return collect(static::activeOptionsForPrivate())->pluck('id')->all();
    }
}
