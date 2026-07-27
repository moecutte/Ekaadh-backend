<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'business_phone',
        'commission_rate',
        'package_id',
        'approval_status',
        'documents',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(OrganizerPackage::class, 'package_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'organizer_id');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function effectiveCommissionRate(?float $platformDefault = null): float
    {
        if ($this->commission_rate !== null) {
            return (float) $this->commission_rate;
        }

        $this->loadMissing('package');

        if ($this->package?->commission_rate !== null) {
            return (float) $this->package->commission_rate;
        }

        return $platformDefault ?? (float) Setting::getValue('default_commission_rate', 10);
    }
}
