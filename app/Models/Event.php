<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
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
        'ticket_design',
        'invitation_design_id',
        'private_event_category_id',
        'couple_name_1',
        'couple_name_2',
        'invitation_field_values',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_featured' => 'boolean',
            'is_private' => 'boolean',
            'invitation_field_values' => 'array',
        ];
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

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class);
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
}
