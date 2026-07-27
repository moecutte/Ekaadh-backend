<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = [
        'organizer_id',
        'period_start',
        'period_end',
        'gross_sales',
        'commission_deducted',
        'net_payout',
        'status',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_sales' => 'decimal:2',
            'commission_deducted' => 'decimal:2',
            'net_payout' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(OrganizerProfile::class, 'organizer_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
