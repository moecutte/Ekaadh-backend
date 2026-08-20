<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizerProfile extends Model
{
    public const ID_TYPES = [
        'national_id' => 'National ID',
        'passport' => 'Passport',
        'drivers_license' => "Driver's license",
    ];

    protected $fillable = [
        'user_id',
        'business_name',
        'business_phone',
        'city',
        'business_description',
        'id_number',
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
            'documents' => 'array',
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

    public function idTypeLabel(): ?string
    {
        $type = $this->documents['id_type'] ?? null;

        return $type ? (self::ID_TYPES[$type] ?? $type) : null;
    }

    public function documentPath(string $key): ?string
    {
        $path = $this->documents[$key] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function documentUrl(string $key): ?string
    {
        $path = $this->documentPath($key);

        return $path ? asset('storage/'.$path) : null;
    }

    public function hasIdentityDocuments(): bool
    {
        return (bool) $this->documentPath('id_front');
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

    public function avatarUrl(): ?string
    {
        $this->loadMissing('user');

        return $this->user?->avatar;
    }

    public function avatarInitials(): string
    {
        $this->loadMissing('user');

        if ($this->user) {
            return $this->user->initials();
        }

        return mb_strtoupper(mb_substr((string) ($this->business_name ?: '?'), 0, 1));
    }
}
