<?php

namespace App\Models;

use App\Support\PublicAsset;
use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSpeaker extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'role',
        'bio',
        'photo',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (EventSpeaker $speaker) {
            PublicUpload::delete($speaker->getRawOriginal('photo'));
        });
    }

    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PublicAsset::url($value),
        );
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
