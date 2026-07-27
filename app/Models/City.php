<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class City extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (City $city) {
            if ($city->isDirty('name') || blank($city->slug)) {
                $base = Str::slug($city->name) ?: 'city';
                $slug = $base;
                $i = 1;
                while (
                    static::query()
                        ->where('slug', $slug)
                        ->when($city->exists, fn ($q) => $q->where('id', '!=', $city->id))
                        ->exists()
                ) {
                    $slug = $base.'-'.$i++;
                }
                $city->slug = $slug;
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'city', 'name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public static function activeNames(): array
    {
        return static::query()->active()->ordered()->pluck('name')->all();
    }
}
