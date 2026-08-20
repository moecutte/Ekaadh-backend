<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrganizerPackage extends Model
{
    public const KIND_ORGANIZER = 'organizer';

    public const KIND_FREE_EVENT = 'free_event';

    protected $fillable = [
        'name',
        'slug',
        'kind',
        'description',
        'commission_rate',
        'billing_type',
        'price',
        'max_events_per_year',
        'min_tickets_per_event',
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
            'min_tickets_per_event' => 'integer',
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

            if ($package->kind !== self::KIND_ORGANIZER) {
                $package->is_default = false;
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

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'package_id');
    }

    public function isFreeEventPackage(): bool
    {
        return $this->kind === self::KIND_FREE_EVENT;
    }

    public function chargeAmount(): float
    {
        return (float) ($this->price ?? 0);
    }

    public function ticketRangeLabel(): string
    {
        $min = $this->min_tickets_per_event;
        $max = $this->max_tickets_per_event;

        if ($min && $max) {
            return number_format($min).'–'.number_format($max).' tickets';
        }

        if ($max) {
            return 'Up to '.number_format($max).' tickets';
        }

        if ($min) {
            return number_format($min).'+ tickets';
        }

        return 'Unlimited tickets';
    }

    public function allowsTicketCount(int $count): bool
    {
        if ($this->min_tickets_per_event && $count < $this->min_tickets_per_event) {
            return false;
        }

        if ($this->max_tickets_per_event && $count > $this->max_tickets_per_event) {
            return false;
        }

        return true;
    }

    public function ticketLimitError(int $count): ?string
    {
        if ($this->allowsTicketCount($count)) {
            return null;
        }

        return "The {$this->name} package covers {$this->ticketRangeLabel()}. Adjust ticket quantity to match.";
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeOrganizerPlans(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('kind', self::KIND_ORGANIZER)->orWhereNull('kind');
        });
    }

    public function scopeFreeEventPlans(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_FREE_EVENT);
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
        return static::query()->active()->organizerPlans()->where('is_default', true)->first()
            ?? static::query()->active()->organizerPlans()->ordered()->first();
    }
}
