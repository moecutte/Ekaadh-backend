<?php

namespace App\Models;

use App\Support\PublicAsset;
use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventGalleryImage extends Model
{
    protected $fillable = [
        'event_id',
        'path',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (EventGalleryImage $image) {
            PublicUpload::delete($image->getRawOriginal('path'));
        });
    }

    protected function path(): Attribute
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
