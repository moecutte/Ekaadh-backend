<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
}
