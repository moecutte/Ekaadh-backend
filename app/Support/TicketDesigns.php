<?php

namespace App\Support;

use App\Models\InvitationDesign;
use Illuminate\Support\Facades\Schema;

class TicketDesigns
{
    public const STANDARD = 'standard';

    public const PREMIUM = 'premium';

    /**
     * Active invitation designs from admin uploads.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(?int $categoryId = null): array
    {
        if (! Schema::hasTable('invitation_designs')) {
            return [];
        }

        $out = [];
        foreach (InvitationDesign::activeCatalog(null, $categoryId) as $row) {
            $out[$row['id']] = $row;
        }

        return $out;
    }

    public static function ids(?int $categoryId = null): array
    {
        return array_keys(self::all($categoryId));
    }

    public static function standard(?int $categoryId = null): array
    {
        return array_values(array_filter(self::all($categoryId), fn ($d) => ($d['category'] ?? '') === self::STANDARD));
    }

    public static function premium(?int $categoryId = null): array
    {
        return array_values(array_filter(self::all($categoryId), fn ($d) => ($d['category'] ?? '') === self::PREMIUM));
    }

    public static function get(?string $id, ?int $categoryId = null): ?array
    {
        if (! $id) {
            return null;
        }

        return self::all($categoryId)[$id] ?? null;
    }

    public static function getByInvitationDesignId(?int $id): ?array
    {
        if (! $id || ! Schema::hasTable('invitation_designs')) {
            return null;
        }

        $design = InvitationDesign::query()->with('fields')->find($id);

        return $design?->toCatalogArray();
    }

    public static function isPremium(?string $id): bool
    {
        $design = self::get($id);

        return $design && ($design['category'] ?? '') === self::PREMIUM;
    }

    public static function defaultId(?int $categoryId = null): string
    {
        if (! Schema::hasTable('invitation_designs')) {
            return '';
        }

        return InvitationDesign::defaultSlug($categoryId);
    }

    public static function publicDefault(): array
    {
        return [
            'id' => 'public',
            'name' => 'Ekaadh Classic',
            'category' => self::STANDARD,
            'label' => 'Public',
            'description' => 'Standard public event ticket.',
            'accent' => '#323891',
            'accent_soft' => '#eef0f8',
            'header_from' => '#0f1a2e',
            'header_to' => '#323891',
            'card_bg' => '#ffffff',
            'text' => '#0f1a2e',
            'muted' => '#64748b',
            'border' => '#e2e8f0',
            'ornament' => '',
            'badge' => 'Admit one',
            'invite_line' => '',
            'request_line' => '',
            'footer_line' => 'Show your QR at the entrance',
            'font_display' => 'Plus Jakarta Sans',
            'font_body' => 'Plus Jakarta Sans',
            'render_mode' => 'blade',
            'fields' => [],
        ];
    }

    public static function resolveForEvent(?\App\Models\Event $event): array
    {
        if ($event?->is_private) {
            if ($event->invitation_design_id) {
                $fromDb = self::getByInvitationDesignId((int) $event->invitation_design_id);
                if ($fromDb) {
                    $fromDb['field_values'] = $event->invitation_field_values ?? [];

                    return $fromDb;
                }
            }

            $categoryId = $event->private_event_category_id ? (int) $event->private_event_category_id : null;
            $design = self::get($event->ticket_design, $categoryId) ?? self::get(self::defaultId($categoryId), $categoryId);
            if ($design) {
                $design['field_values'] = $event->invitation_field_values ?? [];

                return $design;
            }

            return array_merge(self::publicDefault(), [
                'field_values' => $event->invitation_field_values ?? [],
            ]);
        }

        return self::publicDefault();
    }

    public static function templateView(array $design): string
    {
        if (($design['render_mode'] ?? '') === 'overlay' && ! empty($design['graphic_url'])) {
            return 'tickets.templates.overlay';
        }

        $id = $design['blade_key'] ?? $design['id'] ?? 'public';
        $path = resource_path("views/tickets/templates/{$id}.blade.php");

        return is_file($path) ? "tickets.templates.{$id}" : 'tickets.templates.public';
    }
}
