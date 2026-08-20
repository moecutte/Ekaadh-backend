<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'order_number',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'subtotal',
        'service_fee',
        'total_amount',
        'commission_amount',
        'status',
        'payment_method',
        'payment_reference',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(EventInvitation::class);
    }

    public function isInvitation(): bool
    {
        return $this->source === 'invitation';
    }

    /**
     * Real purchases only — complimentary invitation issues are invitees, not buyers.
     */
    public function scopeCommerce(Builder $query): Builder
    {
        return $query->where('source', '!=', 'invitation');
    }

    public function isPrivatePurchase(): bool
    {
        return $this->source === 'private_event';
    }

    public function channelLabel(): string
    {
        return match ($this->source) {
            'private_event' => 'Private event',
            'invitation' => 'Invitation',
            'organizer_package' => 'Free event package',
            default => 'Public tickets',
        };
    }

    public function isPackagePurchase(): bool
    {
        return $this->source === 'organizer_package';
    }

    /**
     * Ticket sales that pay out to organizers (excludes platform capacity / package fees).
     */
    public function scopeTicketSales(Builder $query): Builder
    {
        return $query->whereNotIn('source', ['invitation', 'private_event', 'organizer_package']);
    }

    public function organizerNet(): float
    {
        return max(0, (float) $this->subtotal - (float) $this->commission_amount);
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->buyer_phone) ?: '';

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }

    public function needsAttention(): bool
    {
        return in_array($this->status, ['pending', 'failed'], true);
    }
}
