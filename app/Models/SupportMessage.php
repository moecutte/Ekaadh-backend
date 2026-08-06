<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_ADMIN = 'admin';

    public const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'support_conversation_id',
        'sender_type',
        'sender_user_id',
        'support_faq_id',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function faq(): BelongsTo
    {
        return $this->belongsTo(SupportFaq::class, 'support_faq_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
