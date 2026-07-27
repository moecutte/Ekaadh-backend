<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrganizerPackage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'commission_rate',
        'billing_type',
        'price',
        'max_events_per_year',
        'max_tickets_per_event',
        'features',
        'cta_label',
        'is_highlighted',
        'is_default',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'price' => 'decimal:2',
            'max_events_per_year' => 'integer',
            'max_tickets_per_event' => 'integer',
            'features' => 'array',
            'is_highlighted' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OrganizerPackage $package) {
            if ($package->isDirty('name') || blank($package->slug)) {
                $base = Str::slug($package->name) ?: 'package';
                $slug = $base;
                $i = 1;
                while (
                    static::query()
                        ->where('slug', $slug)
                        ->when($package->exists, fn ($q) => $q->where('id', '!=', $package->id))
                        ->exists()
                ) {
                    $slug = $base.'-'.$i++;
                }
                $package->slug = $slug;
            }

            if ($package->is_default) {
                static::query()
                    ->when($package->exists, fn ($q) => $q->where('id', '!=', $package->id))
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function organizers(): HasMany
    {
        return $this->hasMany(OrganizerProfile::class, 'package_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function displayPrice(): string
    {
        if ($this->billing_type === 'custom') {
            return 'Custom';
        }

        if ($this->price === null || (float) $this->price == 0.0) {
            return '$0';
        }

        $amount = (float) $this->price;

        return '$'.(fmod($amount, 1.0) === 0.0 ? number_format($amount, 0) : number_format($amount, 2));
    }

    public function displayPeriod(): string
    {
        return match ($this->billing_type) {
            'free' => 'forever',
            'per_event' => 'per event',
            'monthly' => '/month',
            'custom' => 'pricing',
            default => '',
        };
    }

    public static function defaultPackage(): ?self
    {
        return static::query()->active()->where('is_default', true)->first()
            ?? static::query()->active()->ordered()->first();
    }
}
