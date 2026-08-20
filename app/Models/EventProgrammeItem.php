<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventProgrammeItem extends Model
{
    protected $fillable = [
        'event_id',
        'starts_at',
        'ends_at',
        'title',
        'description',
        'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function timeRangeLabel(): string
    {
        $start = self::formatClock($this->starts_at);
        $end = self::formatClock($this->ends_at);

        return $end ? $start.' – '.$end : $start;
    }

    public static function formatClock(mixed $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        return date('g:i A', strtotime((string) $time));
    }

    public static function clockValue(mixed $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        return substr((string) $time, 0, 5);
    }
}
