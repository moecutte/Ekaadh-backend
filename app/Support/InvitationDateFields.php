<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Throwable;

/**
 * Design field types auto-filled from the buyer's event date/time.
 */
class InvitationDateFields
{
    public const TYPE_MONTH = 'date_month';

    public const TYPE_DAY = 'date_day';

    public const TYPE_YEAR = 'date_year';

    public const TYPE_TIME = 'date_time';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_MONTH,
        self::TYPE_DAY,
        self::TYPE_YEAR,
        self::TYPE_TIME,
    ];

    public static function isAutoType(?string $type): bool
    {
        return in_array((string) $type, self::TYPES, true);
    }

    /**
     * @return array<string, string> field_type => sample default for admin preview
     */
    public static function sampleDefaults(): array
    {
        return [
            self::TYPE_MONTH => 'Jan',
            self::TYPE_DAY => '15',
            self::TYPE_YEAR => (string) now()->year,
            self::TYPE_TIME => '6:00 PM',
        ];
    }

    /**
     * @return array<string, string> field_type => suggested field_key
     */
    public static function suggestedKeys(): array
    {
        return [
            self::TYPE_MONTH => 'date_month',
            self::TYPE_DAY => 'date_day',
            self::TYPE_YEAR => 'date_year',
            self::TYPE_TIME => 'date_time',
        ];
    }

    /**
     * @return array<string, string> field_type => suggested label
     */
    public static function suggestedLabels(): array
    {
        return [
            self::TYPE_MONTH => 'Month',
            self::TYPE_DAY => 'Day',
            self::TYPE_YEAR => 'Year',
            self::TYPE_TIME => 'Time',
        ];
    }

    public static function formatMonth(DateTimeInterface|string|null $date): string
    {
        $carbon = self::parseDate($date);

        return $carbon?->format('M') ?? '';
    }

    public static function formatDay(DateTimeInterface|string|null $date): string
    {
        $carbon = self::parseDate($date);

        return $carbon?->format('j') ?? '';
    }

    public static function formatYear(DateTimeInterface|string|null $date): string
    {
        $carbon = self::parseDate($date);

        return $carbon?->format('Y') ?? '';
    }

    public static function formatTime(mixed $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        try {
            if ($time instanceof DateTimeInterface) {
                return Carbon::instance($time)->format('g:i A');
            }

            $raw = trim((string) $time);
            if ($raw === '') {
                return '';
            }

            // Already a friendly time like "6:00 PM"
            if (preg_match('/[ap]m/i', $raw)) {
                return Carbon::parse($raw)->format('g:i A');
            }

            return Carbon::parse($raw)->format('g:i A');
        } catch (Throwable) {
            return trim((string) $time);
        }
    }

    /**
     * Fill auto date/time design fields from an event date + time.
     *
     * @param  iterable<int, object|array<string, mixed>>  $fields
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    public static function applyToValues(iterable $fields, array $values, mixed $eventDate, mixed $eventTime): array
    {
        foreach ($fields as $field) {
            $type = is_array($field)
                ? (string) ($field['field_type'] ?? '')
                : (string) ($field->field_type ?? '');
            $key = is_array($field)
                ? (string) ($field['field_key'] ?? '')
                : (string) ($field->field_key ?? '');

            if ($key === '') {
                continue;
            }

            $label = strtolower(is_array($field)
                ? (string) ($field['label'] ?? '')
                : (string) ($field->label ?? ''));
            $default = trim(is_array($field)
                ? (string) ($field['default_text'] ?? '')
                : (string) ($field->default_text ?? ''));
            $looksLikeTime = self::isAutoType($type) === false
                && (
                    str_contains(strtolower($key), 'time')
                    || $label === 'time'
                    || str_contains($label, 'time')
                    || (bool) preg_match('/^\d{1,2}:\d{2}(\s*[ap]m)?$/i', $default)
                );

            if (! self::isAutoType($type) && ! $looksLikeTime) {
                continue;
            }

            $resolvedType = self::isAutoType($type) ? $type : self::TYPE_TIME;

            $formatted = match ($resolvedType) {
                self::TYPE_MONTH => self::formatMonth($eventDate),
                self::TYPE_DAY => self::formatDay($eventDate),
                self::TYPE_YEAR => self::formatYear($eventDate),
                self::TYPE_TIME => self::formatTime($eventTime !== null && $eventTime !== '' ? $eventTime : $eventDate),
                default => '',
            };

            if ($formatted !== '') {
                $values[$key] = $formatted;
            }
        }

        return $values;
    }

    private static function parseDate(DateTimeInterface|string|null $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            if ($date instanceof Carbon) {
                return $date;
            }
            if ($date instanceof DateTimeInterface) {
                return Carbon::instance($date);
            }

            return Carbon::parse((string) $date);
        } catch (Throwable) {
            return null;
        }
    }
}
