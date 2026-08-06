<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportConversation extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'guest_token',
        'channel',
        'status',
        'customer_name',
        'customer_contact',
        'last_message_at',
        'admin_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'admin_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('last_message_at')->orderByDesc('id');
    }

    public function displayName(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->customer_name ?: 'Guest';
    }

    public function displayContact(): ?string
    {
        if ($this->user) {
            $email = $this->user->email;
            if ($email && ! str_ends_with(strtolower($email), '@ekaadh.local')) {
                return $email;
            }

            return $this->user->phone;
        }

        return $this->customer_contact;
    }

    public function markAdminRead(): void
    {
        $this->update(['admin_read_at' => now()]);
    }

    public function hasUnreadForAdmin(): bool
    {
        if (! $this->last_message_at) {
            return false;
        }

        return ! $this->admin_read_at || $this->admin_read_at->lt($this->last_message_at);
    }
}
