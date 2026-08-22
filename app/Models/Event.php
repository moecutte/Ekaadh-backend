<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    public const MAX_COMPLIMENTARY_GUESTS = 15;

    protected $fillable = [
        'organizer_id',
        'owner_user_id',
        'title',
        'slug',
        'description',
        'category',
        'venue',
        'address',
        'city',
        'event_date',
        'event_time',
        'cover_image',
        'is_featured',
        'is_private',
        'pricing_type',
        'package_id',
        'package_paid_at',
        'ticket_design',
        'invitation_design_id',
        'private_event_category_id',
        'couple_name_1',
        'couple_name_2',
        'invitation_field_values',
        'pending_invitations',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_featured' => 'boolean',
            'is_private' => 'boolean',
            'package_paid_at' => 'datetime',
            'invitation_field_values' => 'array',
            'pending_invitations' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Event $event) {
            $event->speakers()->get()->each->delete();
            $event->galleryImages()->get()->each->delete();
        });
    }

    /**
     * Resolve relative cover paths (e.g. images/events/foo.jpg) to a public URL.
     * Absolute http(s) URLs are returned unchanged.
     */
    protected function coverImage(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (! $value) {
                    return null;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                return asset(ltrim($value, '/'));
            },
        );
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(OrganizerProfile::class, 'organizer_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(OrganizerPackage::class, 'package_id');
    }

    public function isFreeEvent(): bool
    {
        return $this->pricing_type === 'free';
    }

    public function packageIsPaid(): bool
    {
        if (! $this->isFreeEvent()) {
            return true;
        }

        $price = (float) ($this->package?->price ?? 0);
        if ($price <= 0) {
            return true;
        }

        return $this->package_paid_at !== null;
    }

    public function needsPackagePayment(): bool
    {
        return $this->isFreeEvent() && ! $this->packageIsPaid();
    }

    public function pricingIsLocked(): bool
    {
        if ($this->isFreeEvent() && $this->package_paid_at) {
            return true;
        }

        return Order::query()
            ->where('event_id', $this->id)
            ->where('status', 'paid')
            ->ticketSales()
            ->exists();
    }

    public function pendingInviteCount(): int
    {
        $guests = is_array($this->pending_invitations) ? ($this->pending_invitations['guests'] ?? []) : [];

        return count($guests);
    }

    public function hasPendingInvitations(): bool
    {
        return $this->pendingInviteCount() > 0;
    }

    public function activeComplimentaryGuestCount(): int
    {
        return $this->invitations()->where('status', 'active')->count();
    }

    public function complimentaryGuestSlotsLeft(): int
    {
        if ($this->is_private) {
            return PHP_INT_MAX;
        }

        return max(0, self::MAX_COMPLIMENTARY_GUESTS - $this->activeComplimentaryGuestCount());
    }

    public function inviteHostName(): string
    {
        if ($this->is_private) {
            return $this->owner?->name ?: 'Host';
        }

        return $this->organizer?->business_name
            ?: $this->organizer?->user?->name
            ?: 'Organizer';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function privateEventCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'private_event_category_id');
    }

    public function invitationDesign(): BelongsTo
    {
        return $this->belongsTo(InvitationDesign::class, 'invitation_design_id');
    }

    public function coupleDisplayName(): ?string
    {
        $a = trim((string) $this->couple_name_1);
        $b = trim((string) $this->couple_name_2);
        if ($a === '' || $b === '') {
            $values = $this->invitation_field_values ?? [];
            $a = trim((string) ($values['couple_name_1'] ?? $a));
            $b = trim((string) ($values['couple_name_2'] ?? $b));
        }
        if ($a === '' || $b === '') {
            return null;
        }

        return $a.' & '.$b;
    }

    public function isManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->owner_user_id && $this->owner_user_id === $user->id) {
            return true;
        }

        if ($user->isOrganizer() && $this->organizer_id && $user->organizerProfile?->id === $this->organizer_id) {
            return true;
        }

        return false;
    }

    public function isCustomerOwned(): bool
    {
        return $this->is_private && $this->owner_user_id !== null;
    }

    public function isExpired(): bool
    {
        if (! $this->event_date) {
            return false;
        }

        return $this->event_date->copy()->startOfDay()->lt(now()->startOfDay());
    }

    public function isUpcoming(): bool
    {
        return ! $this->isExpired();
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pendingPrivateOrder(): HasOne
    {
        return $this->hasOne(Order::class)->ofMany(
            ['id' => 'max'],
            function ($query) {
                $query->where('source', 'private_event')->where('status', 'pending');
            }
        );
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class);
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(EventSpeaker::class)->orderBy('sort_order')->orderBy('id');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(EventGalleryImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function programmeItems(): HasMany
    {
        return $this->hasMany(EventProgrammeItem::class)->orderBy('sort_order')->orderBy('starts_at')->orderBy('id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** Public browse / purchase listings (excludes private invite-only events). */
    public function scopePublicListing($query)
    {
        return $query->where('status', 'published')->where('is_private', false);
    }

    public function scopeUpcoming($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('event_date')
                ->orWhereDate('event_date', '>=', now()->toDateString());
        });
    }

    public function scopePast($query)
    {
        return $query->whereNotNull('event_date')
            ->whereDate('event_date', '<', now()->toDateString());
    }

    public static function listingWhen(?string $when): string
    {
        return strtolower(trim((string) $when)) === 'past' ? 'past' : 'upcoming';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->newQuery();
        if ($field) {
            return $query->where($field, $value)->firstOrFail();
        }

        if (ctype_digit((string) $value)) {
            return $query->whereKey($value)->firstOrFail();
        }

        return $query->where('slug', $value)->firstOrFail();
    }
}
