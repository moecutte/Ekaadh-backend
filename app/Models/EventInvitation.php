<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventInvitation extends Model
{
    protected $fillable = [
        'event_id',
        'order_id',
        'ticket_type_id',
        'guest_name',
        'guest_phone',
        'quantity',
        'token',
        'status',
        'sms_status',
        'whatsapp_status',
        'last_sent_at',
        'opened_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'invitation_id');
    }

    public function publicUrl(): string
    {
        return url('/i/'.$this->token);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
