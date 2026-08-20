<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $hidden = [
        'raw_response',
    ];

    protected $fillable = [
        'order_id',
        'provider',
        'transaction_id',
        'phone_number',
        'amount',
        'status',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isStuck(): bool
    {
        return $this->status === 'initiated'
            && $this->created_at
            && $this->created_at->lt(now()->subMinutes(15));
    }

    public function mismatchesOrder(): bool
    {
        $order = $this->order;
        if (! $order) {
            return $this->status === 'success';
        }

        if ($this->status === 'success' && $order->status !== 'paid') {
            return true;
        }

        if ($this->status === 'failed' && $order->status === 'paid') {
            return true;
        }

        return false;
    }

    public function failureMessage(): ?string
    {
        $hint = trim((string) ($this->attributes['failure_hint'] ?? ''));
        if ($hint !== '') {
            return \App\Support\PaymentMessage::forFailure(['user_message' => $hint], $hint);
        }

        if (! in_array($this->status, ['failed', 'initiated'], true)) {
            return null;
        }

        $raw = $this->raw_response;
        if (! is_array($raw) || $raw === []) {
            return $this->status === 'failed' ? 'Payment failed.' : 'Waiting for the customer to approve on their phone.';
        }

        return \App\Support\PaymentMessage::forFailure($raw);
    }
}
