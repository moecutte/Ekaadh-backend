<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'order_item_id',
        'invitation_id',
        'event_id',
        'ticket_code',
        'holder_name',
        'ticket_type_name',
        'status',
        'checked_in_at',
        'checked_in_by',
        'reminder_24h_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'reminder_24h_sent_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(EventInvitation::class, 'invitation_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeForPublicEvents($query)
    {
        return $query->whereHas('event', fn ($q) => $q->where('is_private', false));
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
