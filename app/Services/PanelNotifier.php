<?php

namespace App\Services;

use App\Models\Event;
use App\Models\OrganizerProfile;
use App\Models\User;
use App\Notifications\PanelAlert;
use Throwable;

class PanelNotifier
{
    public function __construct(private PushNotificationService $push) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function toUser(User $user, string $title, string $body, string $kind, ?string $url = null, array $meta = []): void
    {
        try {
            $user->notify(new PanelAlert($title, $body, $kind, $url, $meta));
        } catch (Throwable $e) {
            report($e);
        }

        $pushData = [];
        foreach ($meta as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $pushData[(string) $key] = (string) $value;
            }
        }

        $this->push->sendToUser($user, $title, $body, $kind, $pushData);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function toAdmins(string $title, string $body, string $kind, ?string $url = null, array $meta = []): void
    {
        User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('status', 'active')
            ->get()
            ->each(fn (User $admin) => $this->toUser($admin, $title, $body, $kind, $url, $meta));
    }

    public function eventSubmittedForReview(Event $event): void
    {
        $event->loadMissing('organizer');
        $who = $event->organizer?->business_name ?: 'An organizer';

        $this->toAdmins(
            'Event ready for review',
            "{$who} submitted {$event->title}.",
            'event_review',
            route('admin.events.index', ['status' => 'pending_review', 'q' => $event->title]),
            ['event_id' => (string) $event->id],
        );
    }

    public function organizerApplicationSubmitted(OrganizerProfile $profile): void
    {
        $this->toAdmins(
            'New organizer application',
            ($profile->business_name ?: 'An organizer').' submitted an application for review.',
            'organizer_application',
            route('admin.organizers.show', $profile),
            ['organizer_id' => (string) $profile->id],
        );
    }
}
